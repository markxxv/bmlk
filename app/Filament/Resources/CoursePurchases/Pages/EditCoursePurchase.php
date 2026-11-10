<?php

namespace App\Filament\Resources\CoursePurchases\Pages;

use App\Filament\Resources\CoursePurchases\CoursePurchaseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoursePurchase extends EditRecord
{
    protected static string $resource = CoursePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
