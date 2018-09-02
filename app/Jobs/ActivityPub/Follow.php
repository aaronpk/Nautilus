<?php
namespace App\Jobs\ActivityPub;
use App\Jobs\ActivityPubHandler;
use App\Inbox, App\Follower, App\Activity, App\User, App\Profile;
use Log;

class Follow extends ActivityPubHandler
{
  public function handle()
  {
    Log::info('Handling Follow request '.$this->_data->id);

    $data = json_decode($this->_data->data, true);

    if(!isset($data['object'])) {
      Log::error('No object was found in the Follow request');
      return;
    }

    if(!is_string($data['object'])) {
      Log::error('The object of the Follow request was not a string');
      return;
    }

    $profile = Profile::where('id', $this->_data->profile_id)->first();

    $verify = $this->verifyObjectHost($data['object']);
    if(!$verify) {
      Log::error('Received a Follow request for an object not on this website');
      return;
    }

    $acceptFollow = true;

    if($this->_user->locked) {
      // Check if the user who requested to follow is already followed by this user
      if(!$this->_user->follows($profile)) {
        Log::warning('Received a Follow request from someone not in the whitelist: '.$profile->url);
        $acceptFollow = false;
      }
    }

    if($acceptFollow) {
      // Insert the follower record
      $follower = Follower::where('user_id', $this->_data->user_id)
        ->where('profile_id', $this->_data->profile_id)
        ->first();
      if(!$follower) {
        $follower = new Follower();
        $follower->user_id = $this->_data->user_id;
        $follower->profile_id = $this->_data->profile_id;
      }
      $follower->save();
    }

    // Send back the Accept/Reject

    $object = array_intersect_key($data, array_flip(['id','type','actor','object']));

    $responseActivity = new Activity();
    $responseActivity->type = ($acceptFollow ? 'Accept' : 'Reject');
    $responseActivity->user_id = $this->_data->user_id;
    $responseActivity->setData([
      'object' => $object
    ]);
    $responseActivity->save();

    $payload = $responseActivity->toJSON();

    Log::info($payload);
    $headers = $responseActivity->sign($this->_user, $this->_data->profile->inbox);

    $ch = curl_init($this->_data->profile->inbox);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    Log::info('Inbox response: '.$response);

  }
}
