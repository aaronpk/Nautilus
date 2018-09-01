<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DateTime, DateTimeZone;

class Post extends Model {

  public function user() {
    return $this->belongsTo('\App\User');
  }

  public function fullURL() {
    return env('APP_URL') . '/' . $this->user->username . '/' . $this->id;
  }

  public function published() {
    $tz = \p3k\date\tz_seconds_to_timezone($this->tz_offset);
    return new DateTime($this->published, $tz);
  }

  public function data() {
    return json_decode($this->raw, true);
  }

  public function toActivityStreamsObject() {
    $data = $this->data();

    $type = $data['post-type'] == 'article' ? 'Article' : 'Note';

    $object = [
      'id' => $this->fullURL(),
      'url' => $this->fullURL(),
      'type' => $type,
      'published' => $this->published()->format('c'),
      'attributedTo' => $this->user->actorURL(),
      'to' => ['https://www.w3.org/ns/activitystreams#Public'],
    ];

    if($this->user->external_domain) {
      $object['id'] = 'https://' . $this->user->external_domain . '/myactivity.stream/' . $this->id;
    }

    if(isset($data['url']))
      $object['url'] = $data['url'];

    if(isset($data['content']))
      $object['content'] = $data['content']['text'];

    if(isset($data['name']))
      $object['name'] = $data['name'];

    // TODO: tags and mentions


    // TODO: photos and videos


    return $object;
  }

  // public function profile() {
  //   return $this->belongsTo('\App\Profile');
  // }

}
