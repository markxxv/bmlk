<?php

namespace App\Filament\Resources\RoleApplications\Pages;

use App\Filament\Resources\RoleApplications\RoleApplicationResource;
use App\Filament\Widgets\PartnerBalanceStats;
use App\Models\PartnerPayout;
use App\Services\PartnerBalance;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\EditRecord;

class EditRoleApplication extends EditRecord
{
    protected static string $resource = RoleApplicationResource::class;

    private function partner(): ?\App\Models\User
    {
        $user = $this->record->role === 'influencer' ? $this->record->user : null;

        return $user && filled($user->influencer_code) ? $user : null;
    }

    protected function getHeaderWidgets(): array
    {
        return [PartnerBalanceStats::class];
    }

    protected function getHeaderActions(): array
    {
        $page = $this;

        return [
            Action::make('recordPayout')
                ->label('Record payout')
                ->icon('lucide-wallet')
                ->color('success')
                ->visible(fn (): bool => $page->partner() !== null)
                ->modalHeading('Record a payout')
                ->modalDescription('Amount defaults to what we owe today (discounts on promo-code sales minus earlier payouts).')
                ->fillForm(fn (): array => [
                    'amount' => max(PartnerBalance::owed($page->partner()), 0),
                    'paid_at' => now('Europe/Paris')->toDateString(),
                ])
                ->schema([
                    TextInput::make('amount')->label('Amount, €')->numeric()->step(0.01)->minValue(0.01)->required()
                        ->prefixIcon('lucide-euro'),
                    DatePicker::make('paid_at')->label('Paid on')->required()->native(false)->prefixIcon('lucide-calendar'),
                    TextInput::make('note')->label('Note (optional)')->maxLength(255)->prefixIcon('lucide-message-square'),
                ])
                ->action(function (array $data) use ($page): void {
                    PartnerPayout::create([
                        'user_id' => $page->partner()->id,
                        'amount' => $data['amount'],
                        'paid_at' => $data['paid_at'],
                        'note' => $data['note'] ?? null,
                    ]);
                    Notification::make()->title('Payout recorded')->success()->send();
                    $page->redirect(RoleApplicationResource::getUrl('edit', ['record' => $page->record]));
                }),

            Action::make('undoPayout')
                ->label('Undo last payout')
                ->icon('lucide-undo-2')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('The most recent recorded payout of this partner will be deleted.')
                ->visible(fn (): bool => $page->partner() !== null
                    && PartnerPayout::where('user_id', $page->partner()->id)->exists())
                ->action(function () use ($page): void {
                    PartnerPayout::where('user_id', $page->partner()->id)->orderByDesc('paid_at')->orderByDesc('id')->first()?->delete();
                    Notification::make()->title('Last payout removed')->success()->send();
                    $page->redirect(RoleApplicationResource::getUrl('edit', ['record' => $page->record]));
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->role === 'influencer') {
            $data['influencer_code'] = $this->record->user?->influencer_code;
            $data['influencer_discount'] = $this->record->user?->influencer_discount ?? 30;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $userData = [];

            if ($record->role === 'influencer') {
                $userData = [
                    'influencer_code' => $data['influencer_code'] ?? null,
                    'influencer_discount' => $data['influencer_discount'],
                ];
            }

            unset($data['influencer_code'], $data['influencer_discount']);
            $record->update($data);

            if ($record->status === 'approved') {
                $userData[$record->role === 'wholesale' ? 'is_wholesale' : 'is_influencer'] = true;
            }

            if ($userData !== []) {
                $record->user->forceFill($userData)->save();
            }

            return $record;
        });
    }
}
