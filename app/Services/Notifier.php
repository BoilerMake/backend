<?php
namespace App\Services;
use App\Jobs\TwillioSMS;
use App\Models\OutgoingMessage;
use App\Models\User;
use Mail;
use Log;
use Illuminate\Foundation\Bus\DispatchesJobs;
class Notifier
{
    use DispatchesJobs;
    public $user;
    public function __construct(User $user_obj) {
        $this->user= $user_obj;
    }
    public function sendEmail($subject,$template_name,$data=NULL)
    {
        $user = $this->user;
        Mail::queue('emails.'.$template_name, ['user' => $user, 'data'=>$data], function ($m) use ($user,$subject,$data,$template_name)
        {
                $m->to($user->email, $user->preferred_name)->subject($subject);
        });
        $this->logNotification('email',$template_name,$data);
    }
    public function sendSMS($message, $name="n/a")
    {
        $job = new TwillioSMS($this->user,$message,$name,$this);
        $this->dispatch($job);
    }

    public function logNotification($type,$name,$data)
    {
        $l = new OutgoingMessage();
        $l->user_id=$this->user->id;
        $l->type=$type;
        $l->name=$name;
        $l->data=json_encode($data);
        $l->save();
        Log::info('[NOTIFIER] '.$type.' sent to id#'.$this->user->id.'. data: '.json_encode($data));
    }
}