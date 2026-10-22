<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PrivatizeInvoices extends Command
{
    protected $signature = 'invoices:privatize {--dry-run : Inspect documents without moving them}';

    protected $description = 'Preserve historical invoices privately and archive public originals';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $count = 0;
        foreach ($public->files('boletas') as $path) {
            if (! preg_match('#^boletas/boleta-[0-9]+\.pdf$#', $path)) {
                continue;
            }
            $count++;
            if ($this->option('dry-run')) {
                continue;
            }
            $contents = $public->get($path);
            if ($private->exists($path) && ! hash_equals(hash('sha256', $private->get($path)), hash('sha256', $contents))) {
                $this->error('Private document differs from public original; nothing overwritten.');

                return self::FAILURE;
            }
            if (! $private->put($path, $contents) || ! hash_equals(hash('sha256', $private->get($path)), hash('sha256', $contents))) {
                $this->error('Could not verify private copy. Original retained.');

                return self::FAILURE;
            }
            $private->makeDirectory('boletas/legacy-originals');
            $archive = $private->path('boletas/legacy-originals/'.Str::uuid().'.pdf');
            $source = realpath($public->path($path));
            $publicRoot = realpath($public->path('boletas'));
            $privateRoot = realpath($private->path('boletas/legacy-originals'));
            $archive = $privateRoot ? $privateRoot.DIRECTORY_SEPARATOR.basename($archive) : $archive;
            if (! $source || ! $publicRoot || ! $privateRoot
                || ! str_starts_with($source, $publicRoot.DIRECTORY_SEPARATOR)
                || ! str_starts_with($archive, $privateRoot.DIRECTORY_SEPARATOR)
                || ! File::move($source, $archive)) {
                $this->error('Could not safely archive original. Private copy preserved.');

                return self::FAILURE;
            }
        }
        $this->info("Historical invoices found: {$count}. No document content deleted.");

        return self::SUCCESS;
    }
}
