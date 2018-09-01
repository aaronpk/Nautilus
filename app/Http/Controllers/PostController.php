<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User, App\Post;
use Request;

class PostController extends BaseController
{

  public function post_permalink($username, $post_id) {

    $user = User::where('username', $username)->first();

    if(!$user) {
      return response()->json([
        'error' => 'not_found'
      ], 404);
    }

    $post = Post::where('user_id', $user->id)
      ->where('id', $post_id)->first();

    if(!$post) {
      return response()->json([
        'error' => 'not_found'
      ], 404);
    }


    // Switch on Accept header
    #if(request()->wantsJson()) {
      return response()->json($post->toActivityStreamsObject())
        ->header('Content-type', 'application/activity+json');
    #} else {
    #  return view('profile', [
    #  ]);
    #}

  }

}
