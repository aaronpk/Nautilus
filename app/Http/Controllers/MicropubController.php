<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\User, App\Token, App\Post;
use DateTime, DateTimeZone;
use Request, Event;

class MicropubController extends BaseController
{
  private $_user;

  private function _verifyToken() {

    if(Request::input('access_token')) {
      $token = Request::input('access_token');
    } else {
      // First validate the access token
      if(!Request::header('authorization')) {
        return $this->_error('unauthorized', 'No authorization header was present in the request', 401);
      }

      if(!preg_match('/^Bearer (.+)/', Request::header('authorization'), $match)) {
        return $this->_error('invalid_authorization', 'The authorization header was invalid', 400);
      }

      $token = $match[1];
    }

    $token = Token::where('token', $token)->first();
    if(!$token) {
      return $this->_error('invalid_access_token', 'The access token provided was not valid', 401);
    }

    $this->_user = $token->user;

    return $token;
  }

  public function post() {
    $token = $this->_verifyToken();

    if(get_class($token) != 'App\Token') {
      return $token; // error response
    }

    $request = \p3k\Micropub\Request::create(Request::all());

    if($request->error) {
      return $this->_error($request->error, $request->error_description, 400);
    }

    switch($request->action) {
      case 'create':

        $post = new Post();
        $post->user_id = $this->_user->id;

        $xray = new \p3k\XRay();
        $parsed = $xray->parse(env('APP_URL'), json_encode(['items'=>[$request->toMF2()]]));

        if(isset($parsed['data']))
          $post->raw = json_encode($parsed['data'], JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
        else
          $post->raw = json_encode($parsed, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);

        if(isset($request->properties['published'])) {
          try {
            $published = new DateTime($request->properties['published'][0]);
          } catch(\Exception $e) {
            return $this->_error('invalid_published_date', 'The published date provided was not valid', 400);
          }
        }
        else {
          $published = new DateTime();
          $published->setTimeZone(new DateTimeZone($this->_user->default_timezone));
        }

        $post->published = $published->format('Y-m-d H:i:s');
        $post->tz_offset = $published->format('Z');

        $post->save();

        Event::fire(new \App\Events\PostCreated($post));

        return response()->json([
          'url' => $post->fullURL()
        ], 201)->header('Location', $post->fullURL());

      case 'update':
      case 'delete':
      default:
        return response()->json([
          'error' => 'unsupported'
        ], 400);
    }
  }

  public function get() {


    return response()->json([
      'token' => []
    ]);
  }

  protected function _error($type, $description, $code=400) {
    return response()->json(['error' => $type, 'error_description' => $description], $code)
      ->header('Access-Control-Allow-Origin', '*')
      ->header('Access-Control-Allow-Headers', 'Authorization');
  }

}
