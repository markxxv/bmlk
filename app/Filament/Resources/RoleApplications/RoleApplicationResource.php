<?php

namespace App\Filament\Resources\RoleApplications;

use App\Filament\Resources\RoleApplications\Pages;
use App\Models\RoleApplication;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RoleApplicationResource extends Resource
{
    protected static ?string $model = RoleApplication::class;
    protected static string|BackedEnum|null $navigationIcon = 'lucide-clipboard-check';
    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string { return 'Shop'; }
    public static function getModelLabel(): string { return 'Partner application'; }
    public static function getPluralModelLabel(): string { return 'Partner applications'; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Application')->icon('lucide-clipboard-check')->columns(2)->schema([
                Placeholder::make('customer_name')->label('Customer')
                    ->content(fn (?RoleApplication $record): string => $record?->user?->name ?? '—'),
                Placeholder::make('customer_email')->label('Email')
                    ->content(fn (?RoleApplication $record): string => $record?->user?->email ?? '—'),
                Select::make('role')->label('Program')->options([
                    'wholesale' => 'Wholesale', 'influencer' => 'Influencer',
                ])->disabled()->dehydrated(false)->prefixIcon('lucide-badge-check'),
                Select::make('status')->label('Decision')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
                ])->required()->native(false)->prefixIcon('lucide-check-circle'),
                Textarea::make('message')->label('Customer message')->disabled()->dehydrated(false)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.name')->label('Customer')->searchable(),
            TextColumn::make('user.email')->label('Email')->searchable(),
            TextColumn::make('role')->badge()->color('gray'),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                'approved' => 'success', 'rejected' => 'danger', default => 'warning',
            }),
            TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('role')->options(['wholesale' => 'Wholesale', 'influencer' => 'Influencer']),
            SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
        ])->recordActions([EditAction::make()->icon('lucide-pencil')])
          ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoleApplications::route('/'),
            'edit' => Pages\EditRoleApplication::route('/{record}/edit'),
        ];
    }
}
