<?php

namespace App\Filament\Resources\ClubMembershipTypeResource\Pages;

use App\Filament\Resources\ClubMembershipTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClubMembershipTypes extends ListRecords
{
    protected static string $resource = ClubMembershipTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
