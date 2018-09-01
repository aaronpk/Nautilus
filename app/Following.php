<?php
namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Following extends Pivot {

  protected $table = 'following';

  public function profile() {
    return $this->belongsTo('\App\Profile');
  }

  public function user() {
    return $this->belongsTo('\App\User');
  }

}
