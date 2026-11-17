<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InvoiceExport extends Model
{
    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const READY = 'ready';

    public const FAILED = 'failed';

    public const DISK = 'local';

    public const DIRECTORY = 'invoice-exports';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'file_size' => 'integer',
            'invoices_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Never leave an orphaned archive with customer data on disk.
        static::deleting(function (self $export): void {
            $export->deleteFile();
        });
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::PENDING, self::PROCESSING], true);
    }

    public function isReady(): bool
    {
        return $this->status === self::READY && filled($this->file_path);
    }

    public function downloadName(): string
    {
        return sprintf('invoices_%s_%s.zip', $this->date_from->format('Y-m-d'), $this->date_to->format('Y-m-d'));
    }

    public function downloadUrl(): string
    {
        return route('invoice-exports.download', $this);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk(self::DISK)->delete($this->file_path);
        }
    }
}
