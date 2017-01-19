<?php

namespace App\Models;

use App\Services\Notifier;
use Log;
use Illuminate\Database\Eloquent\Model;
use Slack;
class Announcement extends Model
{
    //
    public function send()
    {
        $checkInHackerUserIds = Application::whereNotNull('checked_in_at')->lists('user_id')->toArray();
        $execs =  User::whereHas('roles', function ($q) {
            $q->where('name', 'exec');
        })->lists('id')->toArray();
        $toSendTo = array_merge($checkInHackerUserIds,$execs);
        $users = User::whereIn('id',$toSendTo)->get();


        if($this->slack)
        {
            Log::info("sending annoucement to slack");
            Slack::send('@channel ' . $this->body);
        }
        if($this->email)
        {
            Log::info("sending annoucement to EMAIL");
            foreach ($users as $u) {
                $n = new Notifier($u);
                $n->sendEmail("BoilerMake Announcement","announcement",["announcement"=>$this->id,"message"=>$this->body]);
            }
        }
        if($this->sms)
        {
            Log::info("sending annoucement to sms");
            foreach ($users as $u) {
                $n = new Notifier($u);
                $n->sendSMS($this->body, 'annoucement-'.$this->id);
            }
        }
    }
}
