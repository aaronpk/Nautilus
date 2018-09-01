<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Inbox extends Model {

  protected $table = 'inbox';

  protected $fillable = ['type'];

  public function user() {
    return $this->belongsTo('\App\User');
  }

  public function profile() {
    return $this->belongsTo('\App\Profile');
  }


}
