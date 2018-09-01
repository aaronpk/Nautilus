<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Token, App\User;

class GenerateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:generate {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new access token';

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
        return $this->error('User not found');

      $token = new Token();

      $token->user_id = $user->id;
      $token->token = str_random(32);
      $token->scope = 'create update delete media';
      $token->client_id = env('APP_URL');
      $token->save();

      $this->info($token->token);
    }
}
