<?php

namespace App\Listeners;

use App\Events\TaskAssignedNotificationEvent;
use Pusher;

class TaskAssignedNotificationListener
{
    public function __construct()
    {
        //
    }
    public function handle(TaskAssignedNotificationEvent $event)
    {
        $options = [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => true,
        ];
        $pusher = new Pusher\Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );
        $pusher->trigger($event->data->channel, $event->data->event, $event->data);
    }
}
