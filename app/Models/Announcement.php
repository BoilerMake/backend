<?php

namespace App\Models;

use Log;
use App\Services\Notifier;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    //
    public function send()
    {
        $checkInHackerUserIds = Application::whereNotNull('checked_in_at')->pluck('user_id')->toArray();
        $execs = User::whereHas('roles', function ($q) {
            $q->where('name', 'exec');
        })->pluck('id')->toArray();
        $toSendTo = array_merge($checkInHackerUserIds, $execs);
        $users = User::whereIn('id', $toSendTo)->get();

        if ($this->slack) {
            Log::info('sending annoucement to slack');
        }
        if ($this->email) {
            Log::info('sending annoucement to EMAIL');
            foreach ($users as $u) {
                $n = new Notifier($u);
                $n->sendEmail('BoilerMake Announcement', 'announcement', ['announcement'=>$this->id, 'message'=>$this->body]);
            }
        }
        if ($this->sms) {
            Log::info('sending annoucement to sms');
            foreach ($users as $u) {
                $n = new Notifier($u);
                $n->sendSMS($this->body, 'annoucement-'.$this->id);
            }
        }
    }
}
