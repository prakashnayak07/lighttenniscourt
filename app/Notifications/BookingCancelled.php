<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public int $refundAmount
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Booking Cancelled - ' . $this->booking->resource->name)
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('Your booking has been cancelled.')
            ->line('**Court:** ' . $this->booking->resource->name)
            ->line('**Date:** ' . $this->booking->date->format('l, F j, Y'))
            ->line('**Time:** ' . $this->booking->start_time->format('g:i A') . ' - ' . $this->booking->end_time->format('g:i A'));

        if ($this->refundAmount > 0) {
            $message->line('**Refund Amount:** $' . number_format($this->refundAmount / 100, 2));
            
            if ($this->booking->payment_method === 'wallet') {
                $message->line('The refund has been credited to your wallet.');
            } else {
                $message->line('Your refund will be processed within 5-7 business days.');
            }
        }

        return $message
            ->action('Book Another Court', route('bookings.create'))
            ->line('We hope to see you again soon!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'court_name' => $this->booking->resource->name,
            'refund_amount' => $this->refundAmount,
        ];
    }
}
