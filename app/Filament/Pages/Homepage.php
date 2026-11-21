<?php

namespace App\Filament\Pages;

use App\Support\HomepageBenefitIcons;
use App\Support\HomepageSettings;
use BackedEnum;
use Filament\Forms\Components\JsField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Vite;

class Homepage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'lucide-house';
    protected static ?int $navigationSort = -1;
    protected static ?string $navigationLabel = 'Homepage';
    protected static ?string $title = 'Homepage';
    protected static ?string $slug = 'homepage';
    protected string $view = 'filament.pages.homepage';
    protected Width|string|null $maxContentWidth = Width::Full;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(['settings' => HomepageSettings::load()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            JsField::make('settings')
                ->label('Hero editor')
                ->hiddenLabel()
                ->columnSpanFull()
                ->renderer(fn () => Vite::asset('resources/js/homepage-editor.jsx'))
                ->rendererConfiguration(fn () => [
                    'locales' => HomepageSettings::locales(),
                    'themes' => HomepageSettings::THEMES,
                    'fields' => collect(HomepageSettings::FIELDS)->map(fn (array $field) => ['label' => $field['label'], 'max' => $field['max']])->all() + [
                        'badge_percent' => ['label' => 'Discount (%)', 'max' => 100],
                    ],
                    'previewUrl' => route('homepage.preview'),
                    'iconCatalogUrl' => route('homepage.icons'),
                    'selectedIcons' => HomepageBenefitIcons::selected($this->data['settings']['benefit_icons'] ?? null),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $settings = HomepageSettings::save($this->form->getState());
        $this->form->fill(['settings' => $settings]);
        Notification::make()->success()->title('Homepage published')->send();
    }
}
