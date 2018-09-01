<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User;
use Request;

class UserController extends BaseController
{

  public function get($username) {

    $user = User::where('username', $username)->first();

    if(!$user) {
      return response()->json([
        'error' => 'not_found'
      ], 404);
    }

    // Switch on Accept header
    if(request()->wantsJson()) {
      return response()->json([
        "@context" => [
          "https://www.w3.org/ns/activitystreams",
          "https://w3id.org/security/v1"
        ],
        "id" => env('APP_URL')."/".$user->username,
        "type" => "Person",
        "preferredUsername" => $user->username,
        "url" => env('APP_URL').'/'.$user->username,
        "icon" => [
          "type" => "Image",
          "mediaType" => "image/jpeg",
          "url" => env('APP_URL')."/images/".$user->username.".jpg",
        ],
        // "image" => [
        //   "type" => "Image",
        //   "mediaType" => "image/jpeg",
        //   "url" => env('APP_URL')."/images/cover-photo.jpg",
        // ],
        "inbox" => env('APP_URL').$user->inboxPath(),
        "outbox" => env('APP_URL').$user->outboxPath(),
        "publicKey" => [
          "id" => env('APP_URL').'/'.$user->username.'#key',
          "owner" => env('APP_URL')."/".$user->username,
          "publicKeyPem" => $user->public_key
        ]
      ])->header('Content-type', 'application/activity+json');
    } else {
      if($user->external_domain) {
        return redirect('https://' . $user->external_domain . '/');
      } else {
        return view('profile', [
        ]);
      }
    }

  }

}
