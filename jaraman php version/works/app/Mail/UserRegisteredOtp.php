<?php

// app/Mail/CustomerRegisteredOtp.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRegisteredOtp extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $firstname;

    public function __construct($otp, $firstname)
    {
        $this->otp = $otp;
        $this->firstname = $firstname;

    }

    public function build()
    {
        return $this->view('emails.User_registered_otp')
            ->subject('Your OTP for Registration');
    }
}
