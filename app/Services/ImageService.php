<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public function storePhoto(UploadedFile $file, int $userId, string $folder = 'photos'): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "cards/{$userId}/{$folder}/{$filename}";

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function storeCover(UploadedFile $file, int $userId): string
    {
        return $this->storePhoto($file, $userId, 'cover');
    }

    public function storeProfile(UploadedFile $file, int $userId): string
    {
        return $this->storePhoto($file, $userId, 'profile');
    }

    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
