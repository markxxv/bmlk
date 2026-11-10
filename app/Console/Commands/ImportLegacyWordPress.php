<?php

namespace App\Console\Commands;

use App\Services\Legacy\WordPressImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyWordPress extends Command
{
    protected $signature = 'legacy:import-wp
        {--dry-run : Count and validate only, write nothing}
        {--source=legacy : Database connection of the old WordPress site}';

    protected $description = 'Import customers and completed/refunded orders from the old WooCommerce site (history only, no passwords).';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $importer = new WordPressImporter(DB::connection($this->option('source')), DB::connection(), $dry);

        $this->components->info($dry ? 'DRY RUN: nothing will be written.' : 'Importing from the old site.');

        $bar = $this->output->createProgressBar($importer->countUsers());
        $bar->setFormat(' Users  %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $importer->importUsers(fn () => $bar->advance());
        $bar->finish();
        $this->newLine(2);

        $bar = $this->output->createProgressBar($importer->countOrders());
        $bar->setFormat(' Orders %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $importer->importOrders(fn () => $bar->advance());
        $bar->finish();
        $this->newLine(2);

        $this->table(['Result', 'Count'], collect($importer->stats)->map(fn ($v, $k) => [$k, $v])->values()->all());

        if ($importer->mismatches !== []) {
            $this->components->warn(count($importer->mismatches).' orders where subtotal - discount + delivery != total (imported as-is):');
            $this->table(['WP order id', 'WP total', 'Computed'], array_map(
                fn ($m) => [$m['wp_id'], $m['expected'], $m['actual']],
                array_slice($importer->mismatches, 0, 25),
            ));
        }

        $wrong = $importer->verifyRatingsDisabled();
        if ($wrong > 0) {
            $this->components->error("{$wrong} imported orders have ratings_enabled = true!");

            return self::FAILURE;
        }

        $this->components->info($dry ? 'Dry run finished.' : 'Done. Imported orders with ratings enabled: 0.');

        return self::SUCCESS;
    }
}
