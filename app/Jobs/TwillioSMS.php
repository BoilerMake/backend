<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Services_Twilio_Twiml;
use Services_Twilio;
use Services_Twilio_RestException;
use Log;
use App;
class TwillioSMS extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $user;
    protected $message;
    protected $name;
    protected $notif;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $message, $name, Notifier $notif)
    {
        $this->user = $user;
        $this->message = $message;
        $this->name = $name;
        $this->notif = $notif;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message=$this->message;
        $name=$this->name;
        $to = $this->user->phone;
       
        if(!$to){
            $this->notif->logNotification('SMS',"FAIL-".$name,['message'=>$message,'to'=>$to]);
            return;
        }
        $twilioService = new Services_Twilio(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

        try {
            $twilioService->account->messages->create(
                [
                    'From' => env('TWILIO_NUMBER'),
                    'To' => $to,
                    'Body' => $message
                ]
            );
                    Log::info("yay twillio worked");

        }
        catch(\Exception $e) {
            Log::error(
                'Could not send SMS notification' .
                ' Twilio replied with: ' . $e
            );
            $this->notif->logNotification('SMS-FAILED',"FAILED-".$name,['message'=>$message,'to'=>$to]);
            return;
        }
        $this->notif->logNotification('SMS',$name,['message'=>$message,'to'=>$to]);
    }
}
