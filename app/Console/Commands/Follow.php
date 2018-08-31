<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User, App\Profile, App\Activity;
use Log;

class Follow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:follow {username} {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Follow a remote user from the given account';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $username = $this->argument('username');
      $url = $this->argument('url');

      $user = User::where('username', $username)->first();

      if(!$user) {
        $this->error('User not found: '.$username);
        return;
      }

      $this->info("Fetching $url");

      $profile = Profile::create($url);

      if(!$profile) {
        $this->error('Failed to fetch profile '.$url);
        return;
      }

      if(!$profile->inbox && !$profile->shared_inbox) {
        $this->error('Could not find inbox for '.$profile->url);
        return;
      }

      $followActivity = new Activity();
      $followActivity->type = 'Follow';
      $followActivity->user_id = $user->id;
      $followActivity->setData([
        'object' => $profile->url
      ]);
      $followActivity->save();

      $payload = $followActivity->toJSON();

      $this->info($payload);
      $headers = $followActivity->sign($user, $profile->inbox);

      $ch = curl_init($profile->inbox);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
      curl_setopt($ch, CURLOPT_HEADER, true);
      $response = curl_exec($ch);
      $this->info('Inbox response: '.$response);

    }
}
