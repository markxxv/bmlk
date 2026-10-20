<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['options'] = ProductResource::optionsForForm($data['options'] ?? []);

        return $data;
    }

    protected function afterSave(): void
    {
        // Refresh just the options field, so invalid old placeholders also disappear
        // immediately after saving without resetting the other form inputs.
        $this->data['options'] = ProductResource::optionsForForm($this->record->refresh()->options);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
