<?php
namespace App\Jobs\ActivityPub;
use App\Jobs\ActivityPubHandler;
use App\Inbox, App\Following, App\Activity, App\User;
use Log;

class Accept extends ActivityPubHandler
{
  public function handle()
  {
    Log::info('Handling Accept '.$this->_data->id);

    $data = json_decode($this->_data->data, true);

    if(!isset($data['object'])) {
      Log::error('No object was found in the Accept request');
      return;
    }

    if(!is_array($data['object'])) {
      Log::error('The "object" of the Accept request was not an object');
      return;
    }

    if(isset($data['object']['type']) && $data['object']['type'] == 'Follow') {

      $verify = $this->verifyObjectHost($data['object']['actor']);
      if(!$verify) {
        Log::error('Received an Accept Follow request for an object not on this website');
        return;
      }

      $following = Following::where('user_id', $this->_data->user_id)
        ->where('profile_id', $this->_data->profile_id)
        ->first();

      if($following) {
        Log::info('Profile '.$this->_data->profile_id.' accepted follow from '.$this->_user->username);
        $following->pending = false;
        $following->confirmed_at = date('Y-m-d H:i:s');
        $following->save();
      }

      return;
    }

  }
}
