<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    private string $publicCompanyPath;

    private string $legacyIndexPath;

    private string $storagePath;

    public function __construct()
    {
        $this->publicCompanyPath = public_path('company');
        $this->legacyIndexPath = public_path('company/0');
        $this->storagePath = storage_path('app/legacy');
    }

    /**
     * taqbook is single-tenant, so the FrontAccounting convention of nesting
     * per-company data under public/company/<id>/ only ever had one id (0),
     * and living inside the webroot meant its permissions had to be managed
     * separately from the rest of Laravel's storage tree.
     *
     * This moves that data out to storage/app/legacy (dropping the pointless
     * index segment along the way) so it's managed like any other storage
     * disk. A `public/company` symlink to storage/app/legacy (added to
     * config/filesystems.php's `links` and created via `storage:link`) keeps
     * every existing path/URL computed from company_path() working unchanged.
     */
    public function up(): void
    {
        if (! File::isDirectory($this->publicCompanyPath)) {
            return;
        }

        File::ensureDirectoryExists($this->storagePath);

        // Older, not-yet-flattened checkouts still nest everything under company/0/.
        if (File::isDirectory($this->legacyIndexPath)) {
            $this->mergeMoveContents($this->legacyIndexPath, $this->storagePath);
            File::deleteDirectory($this->legacyIndexPath);
        }

        // Anything left directly under company/ (already-flattened checkouts,
        // or stray top-level files) moves across too.
        $this->mergeMoveContents($this->publicCompanyPath, $this->storagePath);

        File::deleteDirectory($this->publicCompanyPath);
    }

    public function down(): void
    {
        if (! File::isDirectory($this->storagePath)) {
            return;
        }

        File::ensureDirectoryExists($this->legacyIndexPath);

        $this->mergeMoveContents($this->storagePath, $this->legacyIndexPath);

        File::deleteDirectory($this->storagePath);
    }

    /**
     * Moves every entry directly inside $from into $to, merging into any
     * colliding directories instead of overwriting them wholesale.
     */
    private function mergeMoveContents(string $from, string $to): void
    {
        foreach (File::directories($from) as $dir) {
            $this->mergeMove($dir, $to.'/'.basename($dir));
        }

        foreach (File::files($from) as $file) {
            $this->mergeMove($file->getPathname(), $to.'/'.$file->getFilename());
        }
    }

    private function mergeMove(string $from, string $to): void
    {
        if (File::isDirectory($from)) {
            File::ensureDirectoryExists($to);
            $this->mergeMoveContents($from, $to);
            File::deleteDirectory($from);

            return;
        }

        File::ensureDirectoryExists(dirname($to));
        File::move($from, $to);
    }
};
