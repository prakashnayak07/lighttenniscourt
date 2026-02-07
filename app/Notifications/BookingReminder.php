<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: Upcoming Booking Tomorrow')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('This is a friendly reminder about your upcoming booking.')
            ->line('**Court:** ' . $this->booking->resource->name)
            ->line('**Date:** ' . $this->booking->date->format('l, F j, Y'))
            ->line('**Time:** ' . $this->booking->start_time->format('g:i A') . ' - ' . $this->booking->end_time->format('g:i A'))
            ->line('**Access Code:** ' . $this->booking->access_code)
            ->action('View Booking Details', route('bookings.show', $this->booking))
            ->line('Please arrive 10 minutes early for check-in.')
            ->line('See you soon!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'court_name' => $this->booking->resource->name,
            'date' => $this->booking->date->toDateString(),
        ];
    }
}
