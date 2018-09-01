<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use Log;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {username} {email} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user account';

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
      $email = $this->argument('email');
      $name = $this->argument('name');

      $user = new User();
      $user->username = $username;
      $user->email = $email;
      $user->name = $name;
      $user->password = 'new';
      $user->default_timezone = 'America/Los_Angeles';
      $user->resetKeyPair();
      $user->save();

      $this->info("Created user");
    }
}
