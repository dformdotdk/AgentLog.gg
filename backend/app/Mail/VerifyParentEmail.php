<?php

namespace App\Mail;

use App\Models\ParentContact;
use Illuminate\Mail\Mailable;

class VerifyParentEmail extends Mailable
{
    public function __construct(public ParentContact $contact) {}

    public function build()
    {
        $verifyUrl = rtrim(config('app.frontend_url', config('app.url')), '/')
            . '/verify-email?token=' . $this->contact->verification_token;

        return $this->subject('Confirm your AgentLog updates')
            ->view('emails.verify-parent')
            ->with(['verifyUrl'=>$verifyUrl]);
    }
}
