<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User;
use Request;

class UserController extends BaseController
{

  public function get_json($username) {
    return $this->get($username, 'json');
  }

  public function get($username, $format=false) {

    $user = User::where('username', $username)->first();

    if(!$user) {
      return response()->json([
        'error' => 'not_found'
      ], 404);
    }

    // Switch on Accept header
    if((!$format && request()->wantsJson()) || $format == 'json') {
      $profile = [
        "@context" => [
          "https://www.w3.org/ns/activitystreams",
          "https://w3id.org/security/v1",
        ],
        "id" => $user->actorURL(),
        "type" => "Person",
        "preferredUsername" => $user->username,
        "url" => $user->actorURL(),
        "icon" => [
          "type" => "Image",
          "mediaType" => "image/jpeg",
          "url" => env('APP_URL')."/storage/images/".$user->username.".jpg",
        ],
        "inbox" => env('APP_URL').$user->inboxPath(),
        "outbox" => env('APP_URL').$user->outboxPath(),
        "featured" => env('APP_URL').$user->featuredPath(),
        "publicKey" => [
          "id" => $user->actorURL(),
          "owner" => $user->actorURL(),
          "publicKeyPem" => $user->public_key
        ]
      ];

      if($user->photo) {
        $profile['icon']['url'] = $user->photo;
      }

      if($user->banner) {
        $profile['image'] = [
          'type' => 'image',
          'mediaType' => 'image/jpeg',
          'url' => $user->banner,
        ];
      }

      if($user->external_domain) {
        // Override some of the properties
        $profile['url'] = 'https://' . $user->external_domain;

        // Add the Webfinger bits to this response
        $profile['---webfinger---'] = '---webfinger---';
        $profile['subject'] = 'acct:' . $user->username . '@' . $user->external_domain;
        $profile['links'] = [
          [
            'rel' => 'self',
            'type' => 'application/activity+json',
            'href' => 'https://' . $user->external_domain . '/.well-known/user.json',
          ]
        ];
      }

      return response()->json($profile)->header('Content-type', 'application/activity+json');
    } else {
      if($user->external_domain) {
        return redirect('https://' . $user->external_domain . '/');
      } else {
        return $this->get($username, 'json');
      }
    }

  }

}
