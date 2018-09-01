<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\PostCreated' => [
          'App\Listeners\SendPostToFollowers',
        ],
        'App\Events\PostUpdated' => [
          'App\Listeners\SendPostUpdatedToFollowers',
        ],
        'App\Events\PostDeleted' => [
          'App\Listeners\SendPostDeletedToFollowers',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
