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

    $config = array(
      "digest_alg" => "sha512",
      "private_key_bits" => 4096,
      "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );

    $res = openssl_pkey_new($config);

    openssl_pkey_export($res, $privKey);
    $pubKey = openssl_pkey_get_details($res);
    $pubKey = $pubKey["key"];

    $user->public_key = $pubKey;
    $user->private_key = $privKey;

    $user->save();

  }
}
