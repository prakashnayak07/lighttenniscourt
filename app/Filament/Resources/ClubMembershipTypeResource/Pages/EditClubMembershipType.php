<?php

namespace App\Filament\Resources\ClubMembershipTypeResource\Pages;

use App\Filament\Resources\ClubMembershipTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClubMembershipType extends EditRecord
{
    protected static string $resource = ClubMembershipTypeResource::class;

    protected function authorizeAccess(): void
    {
        abort_unless(ClubMembershipTypeResource::canEdit($this->record), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
