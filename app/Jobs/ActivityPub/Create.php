<?php
namespace App\Jobs\ActivityPub;
use App\Jobs\ActivityPubHandler;
use App\Inbox;
use Log;

class Create extends ActivityPubHandler
{
  public function handle()
  {
    Log::info('Handling create '.$this->_data->id);

  }
}
