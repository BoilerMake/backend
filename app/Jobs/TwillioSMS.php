<?php

namespace App\Jobs;

use Log;
use App\Models\User;
use Services_Twilio;
use App\Services\Notifier;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class TwillioSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $message = $this->message;
        $name = $this->name;
        $to = $this->user->phone;

        if (! env('ENABLE_TWILLIO')) {
            $this->notif->logNotification('SMS', '(local) '.$name, ['message'=>$message, 'to'=>$to]);

            return;
        }

        if (! $to) {
            Log::error('Could not send SMS notification - user phone empty');
            $this->notif->logNotification('SMS', 'FAIL-'.$name, ['message'=>$message, 'to'=>$to]);

            return;
        }

        $twilioService = new Services_Twilio(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));

        try {
            $twilioService->account->messages->create(
                [
                    'From' => env('TWILIO_NUMBER'),
                    'To' => $to,
                    'Body' => $message,
                ]
            );
        } catch (\Exception $e) {
            Log::alert(
                'Could not send SMS notification'.
                ' Twilio replied with: '.$e
            );
            $this->notif->logNotification('SMS-FAILED', 'FAILED-'.$name, ['message'=>$message, 'to'=>$to]);

            return;
        }
        $this->notif->logNotification('SMS', $name, ['message'=>$message, 'to'=>$to]);
    }
}
