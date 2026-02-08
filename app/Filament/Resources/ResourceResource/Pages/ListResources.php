<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Models\Organization;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListResources extends ListRecords
{
    protected static string $resource = ResourceResource::class;

    protected function getHeaderActions(): array
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $organization = Organization::find(auth()->user()?->organization_id);

            if (! $organization?->activeSubscription() || ! $organization?->canCreateAnotherResource()) {
                return [
                    Actions\Action::make('newCourt')
                        ->label('New Court')
                        ->icon('heroicon-o-plus')
                        ->color('primary')
                        ->action(function () {
                            Notification::make()
                                ->title('Court limit reached')
                                ->body('Your organization needs an active subscription or has reached its court limit.')
                                ->danger()
                                ->send();
                        }),
                ];
            }
        }

        return [
            Actions\CreateAction::make(),
        ];
    }
}
