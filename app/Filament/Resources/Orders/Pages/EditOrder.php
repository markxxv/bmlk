<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Http\Controllers\SendcloudController;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Throwable;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendcloudSync')
                ->label('Synchroniser Sendcloud')
                ->icon('lucide-refresh-cw')
                ->visible(fn (): bool => $this->record->payment_status === 'paid'
                    && ! $this->record->sendcloud_order_id)
                ->action(function (): void {
                    app(SendcloudController::class)->syncPaidOrder($this->record);
                    $this->record->refresh();
                    Notification::make()
                        ->title($this->record->sendcloud_order_id ? 'Commande synchronisée' : 'Synchronisation impossible')
                        ->color($this->record->sendcloud_order_id ? 'success' : 'danger')->send();
                }),

            Action::make('sendcloudLabel')
                ->label('Créer une étiquette')
                ->icon('lucide-printer')
                ->color('primary')
                ->visible(fn (): bool => $this->record->payment_status === 'paid'
                    && ! $this->record->tracking_number)
                ->requiresConfirmation()
                ->modalDescription('Cette opération achète une étiquette de transport auprès de Sendcloud.')
                ->action(function (): void {
                    try {
                        app(SendcloudController::class)->createLabel($this->record);
                        $this->record->refresh();
                        Notification::make()->title('Étiquette créée · suivi '.$this->record->tracking_number)
                            ->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title('Échec Sendcloud')
                            ->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('downloadSendcloudLabel')
                ->label('Télécharger étiquette')
                ->icon('lucide-download')
                ->visible(fn (): bool => (bool) $this->record->tracking_number
                    && Storage::disk('local')->exists('sendcloud-labels/'.$this->record->id.'.pdf'))
                ->url(fn (): string => route('sendcloud.label', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
