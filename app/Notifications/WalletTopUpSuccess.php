<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WalletTopUpSuccess extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WalletTransaction $transaction
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Wallet Top-Up Successful')
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Your wallet has been successfully topped up.')
            ->line('**Amount Added:** $' . number_format($this->transaction->amount_cents / 100, 2))
            ->line('**New Balance:** $' . number_format($this->transaction->balance_after_cents / 100, 2))
            ->line('**Transaction ID:** ' . $this->transaction->id)
            ->line('**Payment Method:** ' . ucfirst($this->transaction->payment_method))
            ->action('View Wallet', route('wallet.index'))
            ->line('Thank you for your payment!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount_cents,
            'new_balance' => $this->transaction->balance_after_cents,
        ];
    }
}
