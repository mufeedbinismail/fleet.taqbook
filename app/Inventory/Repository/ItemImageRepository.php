<?php

namespace App\Inventory\Repository;

use App\Foundation\Shared\ValueObject\ShortCode;
use App\Inventory\ValueObject\ItemImageSnapshot;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * An item's picture, kept where the legacy reports look for it: `images/<code>.<ext>` in the company folder.
 *
 * A short code is letters, digits, "-" and "_", all of which the legacy file-name mapping leaves alone, so the code is the name.
 */
class ItemImageRepository
{
    private const DIRECTORY = 'images';

    /**
     * Every extension an image may carry; legacy removes and finds an image by trying each.
     */
    private const EXTENSIONS = ['jpg', 'png', 'gif'];

    public function imageExists(ShortCode $code): bool
    {
        return $this->storedExtension($code) !== null;
    }

    public function streamImage(ShortCode $code): ?StreamedResponse
    {
        $extension = $this->storedExtension($code);

        return $extension === null ? null : $this->disk()->response($this->path($code, $extension));
    }

    /**
     * Writes the upload under the extension its content has, then removes the item's older image if it had another extension.
     */
    public function storeImage(ShortCode $code, UploadedFile $upload): void
    {
        $extension = $this->extensionOf($upload);

        $this->disk()->putFileAs(self::DIRECTORY, $upload, "{$code}.{$extension}");

        $this->deleteExcept($code, $extension);
    }

    public function deleteImage(ShortCode $code): void
    {
        $this->deleteExcept($code, null);
    }

    public function snapshotImage(ShortCode $code): ItemImageSnapshot
    {
        $extension = $this->storedExtension($code);

        if ($extension === null) {
            return ItemImageSnapshot::none();
        }

        return new ItemImageSnapshot($extension, $this->disk()->get($this->path($code, $extension)));
    }

    /**
     * Leaves the item with the image the snapshot holds, or with none when it holds none.
     */
    public function restoreImage(ShortCode $code, ItemImageSnapshot $snapshot): void
    {
        if ($snapshot->isEmpty()) {
            $this->deleteImage($code);

            return;
        }

        $this->disk()->put($this->path($code, $snapshot->extension), $snapshot->contents);

        $this->deleteExcept($code, $snapshot->extension);
    }

    /**
     * The extension is read from the content, not the file name, and a JPEG always lands as ".jpg".
     */
    private function extensionOf(UploadedFile $upload): string
    {
        $extension = strtolower((string) $upload->guessExtension());

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    private function deleteExcept(ShortCode $code, ?string $keep): void
    {
        foreach (self::EXTENSIONS as $extension) {
            if ($extension !== $keep) {
                $this->disk()->delete($this->path($code, $extension));
            }
        }
    }

    private function storedExtension(ShortCode $code): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            if ($this->disk()->exists($this->path($code, $extension))) {
                return $extension;
            }
        }

        return null;
    }

    private function path(ShortCode $code, string $extension): string
    {
        return self::DIRECTORY."/{$code}.{$extension}";
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk('legacy');
    }
}
