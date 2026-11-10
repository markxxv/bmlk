<?php

namespace App\Filament\Resources\CoursePurchases\Pages;

use App\Filament\Resources\CoursePurchases\CoursePurchaseResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCoursePurchase extends CreateRecord
{
    protected static string $resource = CoursePurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + [
            'status' => 'paid',
            'granted_manually' => true,
            'paid_at' => now(),
            'amount_cents' => 0,
            'currency' => 'eur',
            'email' => User::query()->whereKey($data['user_id'])->value('email'),
            'access_token' => Str::random(64),
        ];
    }
}
