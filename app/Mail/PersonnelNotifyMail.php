<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PersonnelNotifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $personnelName;
    public string $body;

    public function __construct(string $personnelName, string $body)
    {
        $this->personnelName = $personnelName;
        $this->body          = $body;
    }

    public function build(): self
    {
        return $this
            ->subject('APAO License Notice — ' . $this->personnelName)
            ->view('emails.personnel_notify');
    }
}