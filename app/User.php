<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'username', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public static function generateNewKeyPair() {
      $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
      );

      $res = openssl_pkey_new($config);

      openssl_pkey_export($res, $privKey);
      $pubKey = openssl_pkey_get_details($res);
      $pubKey = $pubKey["key"];

      return [
        'public' => $pubKey,
        'private' => $privKey
      ];
    }

    public function resetKeyPair() {
      $key = User::generateNewKeyPair();

      $this->public_key = $key['public'];
      $this->private_key = $key['private'];
    }


    public function activities() {
      return $this->hasMany('\App\Activity');
    }

    public function posts() {
      return $this->hasMany('\App\Post');
    }

    public function followers() {
      return $this->belongsToMany('\App\Profile', 'followers')->using('App\Follower');
    }

    public function following() {
      return $this->belongsToMany('\App\Profile', 'following')->using('App\Following');
    }

    public function follows(Profile $profile) {
      return (bool)Following::where('user_id', $this->id)
        ->where('profile_id', $profile->id)
        ->where('pending', false)
        ->count();
    }

    public function actorURL() {
      if($this->external_domain) {
        $actor = 'https://' . $this->external_domain . '/.well-known/user.json';
      } else {
        $actor = env('APP_URL') . '/' . $this->username;
      }

      return $actor;
    }

    public function inboxPath() {
      return '/' . $this->username . '/inbox';
    }

    public function outboxPath() {
      return '/' . $this->username . '/outbox';
    }

    public function featuredPath() {
      return '/' . $this->username . '/featured';
    }

    public function toActivityStreamsObject() {
      $profile = [
        "@context" => [
          "https://www.w3.org/ns/activitystreams",
          "https://w3id.org/security/v1",
        ],
        "id" => $this->actorURL(),
        "type" => "Person",
        "preferredUsername" => $this->username,
        "url" => $this->actorURL(),
        "icon" => [
          "type" => "Image",
          "mediaType" => "image/jpeg",
          "url" => env('APP_URL')."/storage/images/".$this->username.".jpg",
        ],
        "inbox" => env('APP_URL').$this->inboxPath(),
        "outbox" => env('APP_URL').$this->outboxPath(),
        "featured" => env('APP_URL').$this->featuredPath(),
        "publicKey" => [
          "id" => $this->actorURL(),
          "owner" => $this->actorURL(),
          "publicKeyPem" => $this->public_key
        ]
      ];

      if($this->photo) {
        $profile['icon']['url'] = $this->photo;
      }

      if($this->banner) {
        $profile['image'] = [
          'type' => 'image',
          'mediaType' => 'image/jpeg',
          'url' => $this->banner,
        ];
      }

      if($this->external_domain) {
        // Override some of the properties
        $profile['url'] = 'https://' . $this->external_domain;
      }

      return $profile;
    }
}
