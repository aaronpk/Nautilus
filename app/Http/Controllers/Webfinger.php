<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User;
use Request;

class Webfinger extends BaseController
{
  public function webfinger() {
    $resource = Request::input('resource');

    if(!preg_match('/^acct:(.+)@.+$/', $resource, $match)) {
      return response()->json([
        'error' => 'invalid_resource'
      ], 400);
    }

    $username = $match[1];

    $user = User::where('username', $username)->first();

    if(!$user) {
      return response()->json([
        'error' => 'not_found'
      ], 404);
    }

    return response()->json([
      'subject' => 'acct:aaronpk@'.parse_url(env('APP_URL'), PHP_URL_HOST),
      'aliases' => [
        env('APP_URL').'/aaronpk',
      ],
      'links' => [
        [
          'rel' => 'self',
          'type' => 'application/activity+json',
          'href' => env('APP_URL').'/aaronpk'
        ]
      ]
    ]);
  }
}
