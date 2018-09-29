<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\TwillioSMS;
use App\Models\OutgoingMessage;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Notifier
{
    use DispatchesJobs;
    public $user;

    public function __construct(User $user_obj)
    {
        $this->user = $user_obj;
    }

    public function sendSMS($message, $name = 'n/a')
    {
        $job = new TwillioSMS($this->user, $message, $name, $this);
        $this->dispatch($job);
    }

    public function logNotification($type, $name, $data)
    {
        $outgoing_message = new OutgoingMessage();
        $outgoing_message->user_id = $this->user->id;
        $outgoing_message->type = $type;
        $outgoing_message->name = $name;
        $outgoing_message->data = json_encode($data);
        $outgoing_message->save();
    }
}
