<?php

namespace App\Filament\Resources\RoleApplications\Pages;

use App\Filament\Resources\RoleApplications\RoleApplicationResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;

class EditRoleApplication extends EditRecord
{
    protected static string $resource = RoleApplicationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->role === 'influencer') {
            $data['influencer_code'] = $this->record->user?->influencer_code;
            $data['influencer_discount'] = $this->record->user?->influencer_discount ?? 30;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $userData = [];

            if ($record->role === 'influencer') {
                $userData = [
                    'influencer_code' => $data['influencer_code'] ?? null,
                    'influencer_discount' => $data['influencer_discount'],
                ];
            }

            unset($data['influencer_code'], $data['influencer_discount']);
            $record->update($data);

            if ($record->status === 'approved') {
                $userData[$record->role === 'wholesale' ? 'is_wholesale' : 'is_influencer'] = true;
            }

            if ($userData !== []) {
                $record->user->forceFill($userData)->save();
            }

            return $record;
        });
    }
}
