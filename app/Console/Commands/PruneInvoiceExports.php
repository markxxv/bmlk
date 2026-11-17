<?php

namespace App\Console\Commands;

use App\Models\InvoiceExport;
use Illuminate\Console\Command;

class PruneInvoiceExports extends Command
{
    protected $signature = 'invoices:prune-exports {--days=14 : Delete exports older than this many days}';

    protected $description = 'Delete old invoice archives (they contain customer data) together with their database rows';

    public function handle(): int
    {
        $deleted = 0;
        InvoiceExport::query()
            ->where('created_at', '<', now()->subDays(max(1, (int) $this->option('days'))))
            ->whereNotIn('status', [InvoiceExport::PENDING, InvoiceExport::PROCESSING])
            ->each(function (InvoiceExport $export) use (&$deleted): void {
                $export->delete();
                $deleted++;
            });

        $this->info("Deleted {$deleted} old invoice export(s).");

        return self::SUCCESS;
    }
}
