<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistration extends Mailable
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
        return $this->from('hello@boilermake.org', 'BoilerMake')
                    ->subject('Welcome to BoilerMake!')
                    ->view('emails.welcome')
                    ->with([
                        'user' => $this->user,
                        'link' => $this->link,
                    ]);
    }
}
