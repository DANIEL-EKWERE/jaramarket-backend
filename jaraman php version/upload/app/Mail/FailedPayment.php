<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class FailedPayment extends Mailable
{
    use Queueable, SerializesModels;

    
    public function __construct(public $user_data, public $data, public $email)
    { }

    public function build()
    {
        return $this->view('emails.failed_payment');
    }
}
