<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
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
        $total = $this->booking->lineItems->sum('total_cents');

        return (new MailMessage)
            ->subject('Booking Confirmed - ' . $this->booking->resource->name)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your booking has been confirmed.')
            ->line('**Court:** ' . $this->booking->resource->name)
            ->line('**Date:** ' . $this->booking->date->format('l, F j, Y'))
            ->line('**Time:** ' . $this->booking->start_time->format('g:i A') . ' - ' . $this->booking->end_time->format('g:i A'))
            ->line('**Total:** $' . number_format($total / 100, 2))
            ->line('**Access Code:** ' . ($this->booking->access_code ?? 'Will be provided soon'))
            ->action('View Booking Details', route('bookings.show', $this->booking))
            ->line('Please arrive 10 minutes before your scheduled time.')
            ->line('Thank you for choosing our facility!');
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
