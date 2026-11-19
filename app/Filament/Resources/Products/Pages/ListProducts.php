<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Locked;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    #[Locked]
    public bool $editingStock = false;

    public function toggleStockEditing(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $this->editingStock = ! $this->editingStock;

        $sortColumn = $this->getTableSortColumn();
        $sortDirection = $this->getTableSortDirection();

        if (in_array($sortColumn, ['stock', 'stock_readonly'], true) && $sortDirection !== null) {
            $column = $this->editingStock ? 'stock' : 'stock_readonly';
            $this->tableSort = "{$column}:{$sortDirection}";
        }

        $this->getTable()->flushCachedVisibleColumns();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleStockEditing')
                ->label(fn (): string => $this->editingStock ? 'Done editing stock' : 'Edit stock')
                ->icon(fn (): string => $this->editingStock ? 'lucide-check' : 'lucide-pencil')
                ->color(fn (): string => $this->editingStock ? 'success' : 'gray')
                ->visible(fn (): bool => auth()->user()?->is_admin === true)
                ->action(fn () => $this->toggleStockEditing()),
            CreateAction::make(),
        ];
    }
}
