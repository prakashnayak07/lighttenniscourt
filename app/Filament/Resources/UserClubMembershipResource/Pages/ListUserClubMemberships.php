<?php

namespace App\Filament\Resources\UserClubMembershipResource\Pages;

use App\Filament\Resources\UserClubMembershipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserClubMemberships extends ListRecords
{
    protected static string $resource = UserClubMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
