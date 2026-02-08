<?php

namespace App\Filament\Resources\ResourceResource\Pages;

use App\Filament\Resources\ResourceResource;
use App\Models\Organization;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateResource extends CreateRecord
{
    protected static string $resource = ResourceResource::class;

    protected function authorizeAccess(): void
    {
        if (ResourceResource::canCreate()) {
            return;
        }

        Notification::make()
            ->title('Court limit reached')
            ->body('Your organization needs an active subscription or has reached its court limit.')
            ->danger()
            ->send();

        $this->redirect(ResourceResource::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $organizationId = $data['organization_id']
            ?? config('app.current_organization_id')
            ?? auth()->user()?->organization_id;

        if (! $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => 'An organization is required to create a court.',
            ]);
        }

        $data['organization_id'] = $organizationId;

        $organization = Organization::find($organizationId);

        if ($organization && ! $organization->activeSubscription()) {
            throw ValidationException::withMessages([
                'organization_id' => "An active subscription is required before creating courts.",
            ]);
        }

        if ($organization && ! $organization->canCreateAnotherResource()) {
            $limit = $organization->getMaxCourtsLimit();
            $limitLabel = $limit === null ? 'unlimited' : (string) $limit;

            throw ValidationException::withMessages([
                'organization_id' => "Court limit reached. This organization's plan allows a maximum of {$limitLabel} courts.",
            ]);
        }

        return $data;
    }
}
