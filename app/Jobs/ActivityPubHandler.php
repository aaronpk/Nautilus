<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Inbox, App\User;
use Log;

class ActivityPubHandler implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $_data;
  protected $_user;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($inboxID)
  {
    $this->_data = Inbox::where('id', $inboxID)->first();
    $this->_user = User::where('id', $this->_data->user_id)->first();
  }

  protected function verifyObjectHost($object) {
    if(!\p3k\url\host_matches($object, env('APP_URL'))) {
      // Check if this is a follow request to a hosted account
      if(parse_url($object, PHP_URL_PATH) == '/.well-known/user.json') {
        $host = parse_url($object, PHP_URL_HOST);
        if($this->_user->external_domain != $host) {
          return false;
        }
      } else {
        return false;
      }
    }

    return true;
  }

}
