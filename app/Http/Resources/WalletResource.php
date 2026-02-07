<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance_cents' => $this->balance_cents,
            'balance' => '$' . number_format($this->balance_cents / 100, 2),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            'transactions' => $this->whenLoaded('transactions', function () {
                return $this->transactions->map(fn ($transaction) => [
                    'id' => $transaction->id,
                    'amount_cents' => $transaction->amount_cents,
                    'amount' => '$' . number_format(abs($transaction->amount_cents) / 100, 2),
                    'type' => $transaction->type,
                    'payment_method' => $transaction->payment_method,
                    'description' => $transaction->description,
                    'balance_after_cents' => $transaction->balance_after_cents,
                    'created_at' => $transaction->created_at->toIso8601String(),
                ]);
            }),
        ];
    }
}
