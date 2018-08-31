<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Inbox;
use Log;

class ActivityPubHandler implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  protected $_data;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($inboxID)
  {
    $this->_data = Inbox::where('id', $inboxID)->first();
  }

}
