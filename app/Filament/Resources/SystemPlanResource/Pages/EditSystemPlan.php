<?php

namespace App\Filament\Resources\SystemPlanResource\Pages;

use App\Filament\Resources\SystemPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSystemPlan extends EditRecord
{
    protected static string $resource = SystemPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
