<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Token, App\User, App\Inbox;

class ProcessInboxItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbox:process {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess something in the inbox';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $item = Inbox::where('id', $this->argument('id'))->first();

      if(!$item)
        return $this->error('Item not found');

      $class = '\App\Jobs\ActivityPub\\'.$item->type;
      if(class_exists($class))
        $class::dispatch($item->id);
      else
        Log::error('Activity not supported: '.$item->type);
    }
}
