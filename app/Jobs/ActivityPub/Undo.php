<?php
namespace App\Jobs\ActivityPub;
use App\Jobs\ActivityPubHandler;
use App\Inbox, App\Follower, App\Activity, App\User;
use Log;

class Undo extends ActivityPubHandler
{
  public function handle()
  {
    Log::info('Handling undo request '.$this->_data->id);

    $data = json_decode($this->_data->data, true);

    if(!isset($data['object'])) {
      Log::error('No object was found in the Undo request');
      return;
    }

    if(!is_array($data['object'])) {
      Log::error('The "object" of the Undo request was not an object');
      return;
    }

    if(isset($data['object']['type']) && $data['object']['type'] == 'Follow') {

      if(!\p3k\url\host_matches($data['object']['object'], env('APP_URL'))) {
        // Check if this is a follow request to a hosted account
        if(parse_url($data['object']['object'], PHP_URL_PATH) == '/.well-known/user.json') {
          $host = parse_url($data['object']['object'], PHP_URL_HOST);
          $user = User::where('id', $this->_data->user_id)->first();
          if($user->external_domain != $host) {
            Log::error('Received an Undo Follow request for an external URL not hosted by this website');
            return;
          }
        } else {
          Log::error('Received a Undo Follow request for an object not on this website');
          return;
        }
      }

      $follower = Follower::where('user_id', $this->_data->user_id)
        ->where('profile_id', $this->_data->profile_id)
        ->first();

      if($follower) {
        Log::info('Profile '.$this->_data->profile_id.' unfollowed user '.$this->_data->user_id);
        $follower->delete();
      }

      return;
    }

    if(isset($data['object']['type']) && $data['object']['type'] == 'Create') {

    }

  }
}
