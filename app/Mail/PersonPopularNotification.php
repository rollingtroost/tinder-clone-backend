<?php

namespace App\Mail;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PersonPopularNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Person $person, public int $likeCount)
    {
    }

    public function build()
    {
        return $this->subject('Person exceeded 50 likes')
            ->view('emails.person_popular')
            ->with([
                'person' => $this->person,
                'likeCount' => $this->likeCount,
            ]);
    }
}