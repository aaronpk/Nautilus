<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Activity, App\User;
use Log;

class DeliverActivity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $activity;
    private $inbox;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Activity $activity, $inbox)
    {
        $this->activity = $activity;
        $this->inbox = $inbox;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Delivering activity '.$this->activity->id.' to inbox '.$this->inbox);

        $payload = $this->activity->toJSON();

        $user = $this->activity->user;

        Log::info($payload);
        $headers = $this->activity->sign($user, $this->inbox);

        $ch = curl_init($this->inbox);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        Log::info('Inbox response: '.$response);

    }
}
