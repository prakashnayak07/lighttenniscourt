<?php

namespace App\Filament\Resources\ClubMembershipTypeResource\Pages;

use App\Filament\Resources\ClubMembershipTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClubMembershipType extends CreateRecord
{
    protected static string $resource = ClubMembershipTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $data['organization_id'] = auth()->user()?->organization_id;
        }

        return $data;
    }
}
