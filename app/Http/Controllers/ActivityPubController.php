<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User;
use Request;

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
