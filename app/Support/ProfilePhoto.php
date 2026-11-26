<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ProfilePhoto
{
    private const DISK = 'public';

    /**
     * Resize the upload to 600 by 600 WebP and return the disk and path to store.
     *
     * The previous file is deleted from the disk recorded on the user, including
     * when that disk is no longer the one receiving new uploads.
     *
     * @return array{disk: string, path: string}
     */
    public function store(UploadedFile $photo, User $user): array
    {
        $encoded = (new ImageManager(Driver::class))
            ->decodePath($photo->getPathname())
            ->cover(600, 600)
            ->encode(new WebpEncoder(quality: 80));

        $path = 'profile-photos/'.$user->id.'/'.Str::uuid().'.webp';
        Storage::disk(self::DISK)->put($path, (string) $encoded);
        $this->deletePrevious($user->profile_photo_disk, $user->profile_photo_path, $path);

        return [
            'disk' => self::DISK,
            'path' => $path,
        ];
    }

    /**
     * Remove the photo currently recorded for this user.
     */
    private function deletePrevious(mixed $disk, mixed $path, string $keptPath): void
    {
        if (! is_string($disk) || $disk === '' || ! is_string($path) || $path === '') {
            return;
        }

        if ($disk === self::DISK && $path === $keptPath) {
            return;
        }

        if (! is_array(config('filesystems.disks.'.$disk))) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }
}
