<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;

class BookingReminderSms extends Notification
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
        $time = substr($this->booking->start_time, 0, 5);
        $code = $this->booking->access_code;

        return (new VonageMessage)
            ->content("Reminder: Your booking at {$court} is tomorrow at {$time}. Access code: {$code}");
    }
}
