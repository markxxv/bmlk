<?php

namespace App\Filament\Resources\RoleApplications\Pages;

use App\Filament\Resources\RoleApplications\RoleApplicationResource;
use Filament\Resources\Pages\EditRecord;

class EditRoleApplication extends EditRecord
{
    protected static string $resource = RoleApplicationResource::class;

    protected function afterSave(): void
    {
        $application = $this->record;

        if ($application->status === 'approved') {
            $field = $application->role === 'wholesale' ? 'is_wholesale' : 'is_influencer';
            $application->user->forceFill([$field => true])->save();
        }
    }
}
