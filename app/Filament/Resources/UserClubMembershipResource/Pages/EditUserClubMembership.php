<?php

namespace App\Filament\Resources\UserClubMembershipResource\Pages;

use App\Filament\Resources\UserClubMembershipResource;
use App\Models\ClubMembershipType;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUserClubMembership extends EditRecord
{
    protected static string $resource = UserClubMembershipResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateOrganizationConsistency($data);

        return $data;
    }

    private function validateOrganizationConsistency(array $data): void
    {
        $user = User::find($data['user_id'] ?? null);
        $membershipType = ClubMembershipType::find($data['membership_type_id'] ?? null);

        if (! $user || ! $membershipType) {
            return;
        }

        if ($user->organization_id !== $membershipType->organization_id) {
            throw ValidationException::withMessages([
                'membership_type_id' => 'Membership type must belong to the same organization as the user.',
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
