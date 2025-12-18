<?php

namespace App\Jobs;

use App\Mail\VerifyParentEmail;
use App\Models\ParentContact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $parentContactId) {}

    public function handle(): void
    {
        $contact = ParentContact::find($this->parentContactId);
        if (!$contact) return;
        if ($contact->status !== 'pending') return;

        Mail::to($contact->email)->send(new VerifyParentEmail($contact));
    }
}
