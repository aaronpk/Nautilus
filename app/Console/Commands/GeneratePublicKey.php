<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;

class GeneratePublicKey extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'user:generate_key {username}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Generate a new public private keypair for a user';

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
    $user = User::where('username', $this->argument('username'))->first();

    if(!$user)
      $this->error('User not found');

    $user->resetKeyPair();
    $user->save();
  }
}
