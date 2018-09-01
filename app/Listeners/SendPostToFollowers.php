<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\PostCreated;
use App\Jobs\DeliverActivity;
use App\Activity;
use Log;

class SendPostToFollowers implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PostCreated $event)
    {
        $post = $event->post;
        $user = $post->user;

        Log::info('Post '.$post->fullURL().' was created, delivering to followers');

        $inboxes = [];
        foreach($user->followers as $follower) {
          if($follower->shared_inbox) {
            $inboxes[] = $follower->shared_inbox;
          } elseif($follower->inbox) {
            $inboxes[] = $follower->inbox;
          }
        }
        $inboxes = array_unique($inboxes);

        $activity = new Activity();
        $activity->type = 'Create';
        $activity->user_id = $user->id;
        $activity->setData([
          'object' => $post->toActivityStreamsObject(),
        ]);
        $activity->save();

        foreach($inboxes as $inbox) {
          Log::info('Delivering to inbox '.$inbox);
          DeliverActivity::dispatch($activity, $inbox);
        }
    }
}
