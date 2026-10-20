<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class RepairProductMediaDisks extends Command
{
    protected $signature = 'media:repair-product-disks {--dry-run : List changes without copying files or modifying the database}';

    protected $description = 'Copy product images from private local storage to public storage and fix their media disk records';

    public function handle(): int
    {
        $query = Media::query()
            ->where('model_type', (new Product)->getMorphClass())
            ->where('collection_name', 'images')
            ->where(fn ($query) => $query->where('disk', 'local')->orWhere('conversions_disk', 'local'));

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No product images on the private local disk.');

            return self::SUCCESS;
        }

        $source = Storage::disk('local');
        $destination = Storage::disk('public');
        $dryRun = (bool) $this->option('dry-run');
        $repaired = 0;
        $failed = 0;

        $this->info(($dryRun ? 'Checking' : 'Repairing')." {$total} product media records...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(100, function ($records) use ($source, $destination, $dryRun, &$repaired, &$failed, $bar): void {
            foreach ($records as $media) {
                try {
                    // Spatie's path generator may use a custom media prefix.
                    $originalPath = $media->getPathRelativeToRoot();
                    $folder = dirname($originalPath);

                    if ($folder === '.' || $folder === '' || str_contains($folder, '..')) {
                        throw new RuntimeException('Unsafe media directory: '.$folder);
                    }

                    $files = $source->allFiles($folder);

                    if ($media->disk === 'local' && ! $source->exists($originalPath) && ! $destination->exists($originalPath)) {
                        throw new RuntimeException('Original file missing: '.$originalPath);
                    }

                    if ($files === [] && ! $destination->exists($originalPath)) {
                        throw new RuntimeException('No private files found: '.$folder);
                    }

                    foreach ($files as $file) {
                        if ($destination->exists($file)) {
                            if ($destination->size($file) !== $source->size($file)) {
                                throw new RuntimeException('Conflicting public file: '.$file);
                            }

                            continue;
                        }

                        if ($dryRun) {
                            continue;
                        }

                        $stream = $source->readStream($file);

                        if (! is_resource($stream)) {
                            throw new RuntimeException('Unable to read: '.$file);
                        }

                        try {
                            if (! $destination->put($file, $stream, ['visibility' => 'public'])) {
                                throw new RuntimeException('Unable to copy: '.$file);
                            }
                        } finally {
                            fclose($stream);
                        }

                        if ($destination->size($file) !== $source->size($file)) {
                            throw new RuntimeException('Copy verification failed: '.$file);
                        }
                    }

                    if (! $dryRun) {
                        $media->forceFill([
                            'disk' => $media->disk === 'local' ? 'public' : $media->disk,
                            'conversions_disk' => $media->conversions_disk === 'local' ? 'public' : $media->conversions_disk,
                        ])->save();
                    }

                    $repaired++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->newLine();
                    $this->error("Media #{$media->id}: {$exception->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info(($dryRun ? 'Ready to repair: ' : 'Repaired: ').$repaired.'; failed: '.$failed);

        // Keep old private originals as a rollback copy.
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
