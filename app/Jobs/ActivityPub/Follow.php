<?php
namespace App\Jobs\ActivityPub;
use App\Jobs\ActivityPubHandler;
use App\Inbox;
use Log;

class Follow extends ActivityPubHandler
{
  public function handle()
  {
    Log::info('Handling inbox item '.$this->_data->id);

  }
}
