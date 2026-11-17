<?php

namespace App\Filament\Pages;

use App\Models\InvoiceExport;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use UnitEnum;

class Invoices extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'lucide-file-archive';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'invoices';

    protected static ?string $title = 'Invoices';

    protected string $view = 'filament.pages.invoices';

    public const MAX_DAYS = 366;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Shop';
    }

    public static function getNavigationLabel(): string
    {
        return 'Invoices';
    }

    public function mount(): void
    {
        $this->form->fill($this->rangeFor('previous-month'));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(['default' => 1, 'sm' => 2])->schema([
                    DatePicker::make('date_from')
                        ->label('From')
                        ->prefixIcon('lucide-calendar-arrow-up')
                        ->native()
                        ->required()
                        ->maxDate(now('Europe/Paris')->toDateString())
                        ->live(onBlur: true),
                    DatePicker::make('date_to')
                        ->label('To (inclusive)')
                        ->prefixIcon('lucide-calendar-arrow-down')
                        ->native()
                        ->required()
                        ->maxDate(now('Europe/Paris')->toDateString())
                        ->afterOrEqual('date_from')
                        ->live(onBlur: true),
                ]),
            ]);
    }

    public function setRange(string $preset): void
    {
        $this->form->fill($this->rangeFor($preset));
        $this->resetErrorBag();
    }

    /** @return array{date_from: string, date_to: string} */
    private function rangeFor(string $preset): array
    {
        $today = CarbonImmutable::now('Europe/Paris');

        [$from, $to] = match ($preset) {
            'this-month' => [$today->startOfMonth(), $today],
            'last-7' => [$today->subDays(6), $today],
            default => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
        };

        return ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString()];
    }

    /** Only inserts a row: the scheduled command builds the archive, so this request ends immediately. */
    public function createExport(): void
    {
        $state = $this->form->getState();
        $from = CarbonImmutable::parse($state['date_from']);
        $to = CarbonImmutable::parse($state['date_to']);

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            Notification::make()->danger()->title('Period is too long')
                ->body('Choose a period of up to '.self::MAX_DAYS.' days.')->send();

            return;
        }

        $existing = InvoiceExport::query()
            ->whereDate('date_from', $from)->whereDate('date_to', $to)
            ->whereIn('status', [InvoiceExport::PENDING, InvoiceExport::PROCESSING])
            ->exists();

        if ($existing) {
            Notification::make()->warning()->title('Already in progress')
                ->body('An archive for this period is already being prepared.')->send();

            return;
        }

        InvoiceExport::query()->create([
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'status' => InvoiceExport::PENDING,
            'requested_by' => auth()->id(),
        ]);

        Notification::make()->success()->title('Archive requested')
            ->body('It will be ready in a minute or two. You will get a Telegram message.')->send();
    }

    public function deleteAction(): Action
    {
        return Action::make('delete')
            ->label('Delete')
            ->icon('lucide-trash-2')
            ->color('danger')
            ->iconButton()
            ->tooltip('Delete archive')
            ->requiresConfirmation()
            ->modalIcon('lucide-trash-2')
            ->modalHeading('Delete this archive?')
            ->modalDescription('The ZIP file will be removed from the server. You can request it again at any time.')
            ->modalSubmitActionLabel('Delete')
            ->modalCancelActionLabel('Cancel')
            ->action(function (array $arguments): void {
                InvoiceExport::query()->find($arguments['export'] ?? null)?->delete();

                Notification::make()->success()->title('Archive deleted')->send();
            });
    }

    /** @return Collection<int, InvoiceExport> */
    public function getExports(): Collection
    {
        return InvoiceExport::query()->with('requester:id,name,email')->latest('id')->limit(30)->get();
    }
}
