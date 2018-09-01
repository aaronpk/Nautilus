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
      $profile = [
        "@context" => [
          "https://www.w3.org/ns/activitystreams",
          "https://w3id.org/security/v1"
        ],
        "id" => $user->actorURL(),
        "type" => "Person",
        "preferredUsername" => $user->username,
        "url" => $user->actorURL(),
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
          "id" => $user->actorURL(),
          "owner" => $user->actorURL(),
          "publicKeyPem" => $user->public_key
        ]
      ];

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
        return view('profile', [
        ]);
      }
    }

  }

}
