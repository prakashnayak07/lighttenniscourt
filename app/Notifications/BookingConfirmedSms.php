<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedSms extends Notification
{
    use Queueable;

    public function __construct(
        protected Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['vonage'];
    }

    public function toVonage(object $notifiable): VonageMessage
    {
        $court = $this->booking->resource->name;
        $date = $this->booking->date->format('M j, Y');
        $time = substr($this->booking->start_time, 0, 5);
        $code = $this->booking->access_code;

        return (new VonageMessage)
            ->content("Booking confirmed! {$court} on {$date} at {$time}. Access code: {$code}");
    }
}
