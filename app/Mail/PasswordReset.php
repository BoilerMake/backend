<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;
    protected $user;
    protected $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $link)
    {
        $this->user = $user;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('team@boilermake.org', 'BoilerMake')
            ->subject('BoilerMake Password Reset')
            ->view('emails.password-reset')
            ->with([
                'user' => $this->user,
                'link' => $this->link,
            ]);
    }
}
