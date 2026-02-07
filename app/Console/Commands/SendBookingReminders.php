<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\BookingReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send reminder emails for bookings happening in 24 hours';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow();

        $bookings = Booking::query()
            ->whereDate('date', $tomorrow)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with(['user', 'resource'])
            ->get();

        $count = 0;

        foreach ($bookings as $booking) {
            $booking->user->notify(new BookingReminder($booking));
            $count++;
        }

        $this->info("Sent {$count} booking reminder(s).");

        return Command::SUCCESS;
    }
}
