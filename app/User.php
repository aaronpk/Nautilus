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
      $this->hasMany('\App\Activity');
    }
}
