<?php

namespace App\Filament\Tables\Columns;

use Filament\Forms\Components\Concerns\HasInputMode;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Concerns\CanBeValidated;
use Filament\Tables\Columns\Concerns\CanUpdateState;
use Filament\Tables\Columns\Contracts\Editable;

class EditableColumn extends Column implements Editable
{
    use CanBeValidated;
    use CanUpdateState {
        updateState as protected persistState;
    }
    use HasInputMode;

    protected string $view = 'filament.tables.columns.editable-column';

    protected function setUp(): void
    {
        parent::setUp();

        $this->disabledClick();
    }

    public function updateState(mixed $state): array
    {
        return [
            'saved' => true,
            'value' => $this->persistState($state),
        ];
    }
}
