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
     * Single-tenant, so FrontAccounting's per-company nesting only ever had one id to nest under,
     * and sitting inside the webroot meant these permissions were managed apart from the rest of
     * the storage tree.
     */
    public function up(): void
    {
        if (! File::isDirectory($this->publicCompanyPath)) {
            return;
        }

        // Compared resolved, because once the move has happened this path is a link onto the
        // destination: followed, it hands the destination its own contents and then deletes them.
        if (realpath($this->publicCompanyPath) === realpath($this->storagePath)) {
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

        // The link goes, never what it points at: left standing, the path rebuilt under it would
        // lead back inside the very directory being emptied.
        if (is_link($this->publicCompanyPath)) {
            unlink($this->publicCompanyPath);
        }

        File::ensureDirectoryExists($this->legacyIndexPath);

        $this->mergeMoveContents($this->storagePath, $this->legacyIndexPath);

        File::deleteDirectory($this->storagePath);
    }

    /**
     * Illuminate's file and directory listings skip dotfiles, which here would be left behind to
     * be deleted along with the directory they sat in.
     */
    private function mergeMoveContents(string $from, string $to): void
    {
        foreach (scandir($from) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $this->mergeMove($from.'/'.$entry, $to.'/'.$entry);
        }
    }

    /**
     * A colliding directory is merged into rather than overwritten wholesale.
     */
    private function mergeMove(string $from, string $to): void
    {
        // A link is carried across whole rather than walked into: what it points at need not sit
        // under this directory, and emptying it would reach outside the move entirely.
        if (File::isDirectory($from) && ! is_link($from)) {
            File::ensureDirectoryExists($to);
            $this->mergeMoveContents($from, $to);
            File::deleteDirectory($from);

            return;
        }

        File::ensureDirectoryExists(dirname($to));
        File::move($from, $to);
    }
};
