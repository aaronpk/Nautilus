<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User, App\Profile, App\Inbox;
use Request;
use App\ActivityPub\HTTPSignature;

class ActivityPubController extends BaseController
{
  private $user;

  private function loadUser($username) {
    $user = User::where('username', $username)->first();

    if(!$user) {
      return response()->json([
        'error' => 'not_found'
      ], 404);
    }

    $this->user = $user;

    return true;
  }

  public function postInbox($username) {
    $check = $this->loadUser($username);
    if($check !== true)
      return $check;

    $body = Request::instance()->getContent();

    // Record in the DB even if it fails
    $inbox = new Inbox();
    $inbox->user_id = $this->user->id;
    $inbox->profile_id = 0;
    $inbox->type = Request::input('type', '');
    $inbox->verified = false;
    $inbox->data = $body;
    $inbox->signature = Request::header('signature', '');
    $inbox->headers = '';
    $inbox->save();

    if(!Request::header('signature')) {
      return response()->json([
        'error' => 'Missing Signature header'
      ], 400);
    }

    // Extract the signature properties
    $signatureData = HTTPSignature::parseSignatureHeader(Request::header('signature'));

    if(isset($signatureData['error']))
      return response()->json($signatureData, 400);

    // Check if we already know the key for this user
    $profile = Profile::where('keyid', $signatureData['keyId'])->first();
    if(!$profile) {
      $profile = Profile::createFromURL($signatureData['keyId']);

      if(!$profile) {
        return response()->json([
          'error' => 'Failed to fetch profile for key "'.$signatureData['keyId'].'"'
        ], 400);
      }

      // Check that the keyId found at the profile matches the one in the signature
      if($profile->keyid != $signatureData['keyId']) {
        return response()->json([
          'error' => 'Public key on profile did not match the keyId in the signature',
          'signatureKeyId' => $signatureData['keyId'],
          'profileKeyId' => $profile->keyid,
        ], 400);
      }

      $keyExisted = false;
    } else {
      $keyExisted = true;
    }

    $pkey = $profile->openssl_public_key();
    if(!$pkey) {
      return response()->json([
        'error' => 'Error reading public key'
      ]);
    }

    $inputHeaders = Request::instance()->headers->all();

    list($verified, $headers) = HTTPSignature::verify($pkey, $signatureData, $inputHeaders, $this->user->inboxPath(), $body);

    // If the signature fails verification the first time, fetch the key and try again
    if($verified !== 1 && $keyExisted) {
      $profile->keyid = '';
      $profile->public_key = '';
      $profile->save();

      $profile = Profile::createFromURL($signatureData['keyId']);

      $pkey = $profile->openssl_public_key();
      if(!$pkey) {
        return response()->json([
          'error' => 'Error reading public key'
        ]);
      }

      list($verified, $headers) = HTTPSignature::verify($pkey, $signatureData, $inputHeaders, $this->user->inboxPath(), $body);
    }

    $inbox->headers = $headers;
    $inbox->verified = $verified;
    $inbox->profile_id = $profile->id;

    if($verified !== 1) {
      $inbox->save();
      return response()->json([
        'error' => 'Invalid signature',
        'headers' => $headers,
      ], 400);
    }

    // HTTP signature checked out, make sure the "actor" of the activity matches that of the signature

    if(!Request::input('actor')) {
      $inbox->verified = 0;
      $inbox->save();

      return response()->json([
        'error' => 'Request was missing an actor property'
      ], 400);
    }

    if(is_string(Request::input('actor'))) {
      $actor = Request::input('actor');
    } elseif(is_string(Request::input('actor.id'))) {
      $actor = Request::input('actor.id');
    } else {
      $inbox->verified = 0;
      $inbox->save();

      return response()->json([
        'error' => 'The actor provided could not be parsed'
      ], 400);
    }

    $inbox->actor = $actor;
    $inbox->save();

    // Pass off to a handler based on the type of activity received
    $class = '\App\Jobs\ActivityPub\\'.$inbox->type;
    if(class_exists($class))
      $class::dispatch($inbox->id);
    else
      return response()->json('not supported: '.$inbox->type, 201);

    return response()->json('accepted', 202);
  }

  public function getOutbox($username) {
    $check = $this->loadUser($username);
    if($check !== true)
      return $check;

    return response()->json([
      '@context' => [
         'https://www.w3.org/ns/activitystreams'
      ],
      'id' => env('APP_URL').'/'.$this->user->username.'/outbox',
      'type' => 'OrderedCollection',
      'totalItems' => 1,
    ]);
  }

}
