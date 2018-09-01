<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User, App\Profile, App\Activity;
use App\Jobs\DeliverActivity;
use Log;

class SendProfileUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitypub:update_profile {username} {inbox}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a profile update to an inbox';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $username = $this->argument('username');
      $inbox = $this->argument('inbox');

      $user = User::where('username', $username)->first();

      if(!$user) {
        $this->error('User not found: '.$username);
        return;
      }

      $activity = new Activity();
      $activity->type = 'Update';
      $activity->user_id = $user->id;
      $activity->setData([
        'object' => $user->actorURL()
      ]);
      $activity->save();

      DeliverActivity::dispatch($activity, $inbox);
    }
}
