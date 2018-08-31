<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Profile extends Model {

  public function activities() {
    $this->hasMany('\App\Activity');
  }

  public function openssl_public_key() {
    return openssl_pkey_get_public($this->public_key);
  }

  public static function create($uri) {
    if(preg_match('/.+@.+/', $uri))
      return Profile::createFromWebfinger($uri);
    else
      return Profile::createFromURL($uri);
  }

  public static function createFromURL($url) {
    Log::debug('Fetching profile '.$url);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: application/activity+json, application/json'
    ]);
    $response = curl_exec($ch);
    if($response) {
      $data = json_decode($response, true);
      if($data) {
        // Check that this looks like an activitypub profile
        if(isset($data['id']) && isset($data['type']) && in_array($data['type'], ['Person','Service'])) {
          return self::createFromProfile($data);
        }
      }
    }

    return false;
  }

  public static function createFromWebfinger($uri) {
    if(!preg_match('/(.+)@(.+)/', $uri, $match)) {
      return false;
    }
    $host = $match[2];

    Log::debug('Making Webfinger request for '.$uri);
    $ch = curl_init('http://'.$host.'/.well-known/webfinger?resource=acct:'.$uri);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Accept: application/activity+json, application/json'
    ]);
    $response = curl_exec($ch);
    if($response) {
      $data = json_decode($response, true);
      if($data) {
        if(isset($data['links'])) {
          foreach($data['links'] as $link) {
            if(isset($link['href']) && isset($link['rel']) && $link['rel'] == 'self'
              && isset($link['type']) && $link['type'] == 'application/activity+json') {
              return self::createFromURL($link['href']);
            }
          }
        }
      }
    }

    return false;
  }

  public static function createFromProfile($data) {
    $profile = Profile::where('url', $data['id'])->first();
    if(!$profile) {
      $profile = new Profile();
      $profile->url = $data['id'];
    }

    if(isset($data['inbox']))
      $profile->inbox = $data['inbox'];

    if(isset($data['endpoints']['sharedInbox']))
      $profile->shared_inbox = ['endpoints']['sharedInbox'];

    if(isset($data['preferredUsername']))
      $profile->username = $data['preferredUsername'];

    if(isset($data['name']))
      $profile->username = $data['name'];

    if(isset($data['icon']['url']))
      $profile->photo = $data['icon']['url'];

    if(isset($data['publicKey']) && isset($data['publicKey']['id']) && isset($data['publicKey']['publicKeyPem'])) {
      $profile->keyid = $data['publicKey']['id'];
      $profile->public_key = $data['publicKey']['publicKeyPem'];
    }

    $profile->data = json_encode($data, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES);
    $profile->save();
    return $profile;
  }

}
