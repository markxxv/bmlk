<?php

namespace App\Filament\Resources\ShippingBoxes\Pages;

use App\Filament\Resources\ShippingBoxes\ShippingBoxResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShippingBoxes extends ListRecords
{
    protected static string $resource = ShippingBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('lucide-plus'),
        ];
    }
}
