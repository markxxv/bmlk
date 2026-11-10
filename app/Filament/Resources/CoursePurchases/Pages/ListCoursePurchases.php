<?php

namespace App\Filament\Resources\CoursePurchases\Pages;

use App\Filament\Resources\CoursePurchases\CoursePurchaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoursePurchases extends ListRecords
{
    protected static string $resource = CoursePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Grant access')];
    }
}
