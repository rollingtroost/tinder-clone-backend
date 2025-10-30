<?php

namespace App\Jobs;

use App\Mail\PersonPopularNotification;
use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPopularPersonNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Person $person, public int $likeCount)
    {
    }

    public function handle(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        if (! $adminEmail) {
            return; // no admin email configured
        }

        Mail::to($adminEmail)->queue(new PersonPopularNotification($this->person, $this->likeCount));
    }
}