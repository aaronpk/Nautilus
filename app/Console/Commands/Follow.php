<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User, App\Profile, App\Activity, App\Following;
use App\Jobs\DeliverActivity;
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
        $this->error('Could not find inbox for '.$profile->profileid);
        return;
      }

      $followActivity = new Activity();
      $followActivity->type = 'Follow';
      $followActivity->user_id = $user->id;
      $followActivity->setData([
        'object' => $profile->profileid
      ]);
      $followActivity->save();

      $following = new Following();
      $following->user_id = $user->id;
      $following->profile_id = $profile->id;
      $following->pending = true;
      $following->save();

      DeliverActivity::dispatch($followActivity, $profile->inbox);
    }
}
