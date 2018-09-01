<?php
namespace App\Jobs\ActivityPub;
use App\Jobs\ActivityPubHandler;
use App\Inbox, App\Follower, App\Activity, App\User;
use Log;

class Follow extends ActivityPubHandler
{
  public function handle()
  {
    Log::info('Handling follow request '.$this->_data->id);

    $data = json_decode($this->_data->data, true);

    if(!isset($data['object'])) {
      Log::error('No object was found in the Follow request');
      return;
    }

    if(!is_string($data['object'])) {
      Log::error('The object of the Follow request was not a string');
      return;
    }

    if(!\p3k\url\host_matches($data['object'], env('APP_URL'))) {
      // Check if this is a follow request to a hosted account
      if(parse_url($data['object'], PHP_URL_PATH) == '/.well-known/user.json') {
        $host = parse_url($data['object'], PHP_URL_HOST);
        $user = User::where('id', $this->_data->user_id)->first();
        if($user->external_domain != $host) {
          Log::error('Received a Follow request for an external URL not hosted by this website');
          return;
        }
      } else {
        Log::error('Received a Follow request for an object not on this website');
        return;
      }
    }

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

    // Send back the Accept

    $object = array_intersect_key($data, array_flip(['id','type','actor','object']));

    $acceptActivity = new Activity();
    $acceptActivity->type = 'Accept';
    $acceptActivity->user_id = $this->_data->user_id;
    $acceptActivity->setData([
      'object' => $object
    ]);
    $acceptActivity->save();

    $payload = $acceptActivity->toJSON();

    Log::info($payload);
    $headers = $acceptActivity->sign($this->_data->user, $this->_data->profile->inbox);

    $ch = curl_init($this->_data->profile->inbox);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    Log::info('Inbox response: '.$response);

  }
}
