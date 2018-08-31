<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model {

  protected $fillable = ['type'];

  public function user() {
    return $this->belongsTo('\App\User');
  }

  public function profile() {
    return $this->belongsTo('\App\Profile');
  }

  public function setData($array) {
    $this->data = json_encode($array, JSON_UNESCAPED_SLASHES+JSON_PRETTY_PRINT);
  }

  public function toJSON($pretty=false) {
    $json = [
      '@context' => 'https://www.w3.org/ns/activitystreams',
      'id' => env('APP_URL').'/activity/'.$this->id,
      'type' => $this->type,
    ];
    if($this->user_id)
      $json['actor'] => env('APP_URL').'/'.$this->user->username;
    elseif($this->profile_id)
      $json['actor'] = $this->profile->url;

    $data = json_decode($this->data, true);
    $json = array_merge($json, $data);

    return json_encode($json, ($pretty ? JSON_PRETTY_PRINT : 0)+JSON_UNESCAPED_SLASHES);
  }

  public function sign(User &$user, $url) {
    $body = $this->toJSON();

    $headers = ActivityPub\HTTPSignature::sign($user, $url, $body);

    return $headers;
  }
}
