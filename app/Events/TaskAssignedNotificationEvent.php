<?php

namespace App\Events;
use Illuminate\Queue\SerializesModels;
use App\Models\TaskNotificationModel;
class TaskAssignedNotificationEvent extends Event
{
    use SerializesModels;

    public $data;
    public function __construct(TaskNotificationModel $data)
    {
        $this->data = $data;
    }  

}
