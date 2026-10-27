<?php

namespace App\Actions\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ItemImages
{
    public function store(UploadedFile $upload): string
    {
        $path = 'items/'.Str::random(40).'.'.$upload->extension();
        try {
            $stored = $upload->storeAs('items', basename($path), 'public');
        } catch (Throwable $exception) {
            $this->delete($path);
            report($exception);
            throw ValidationException::withMessages(['image' => 'The image could not be stored. Please try again.']);
        }
        if ($stored === false) {
            $this->delete($path);
            throw ValidationException::withMessages(['image' => 'The image could not be stored. Please try again.']);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path === null) {
            return;
        }
        // Only paths owned by this module may be deleted, always read from DB
        // or the generated upload result, never from client input.
        try {
            if (! preg_match('#^items/[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)$#D', $path) || ! Storage::disk('public')->delete($path)) {
                Log::warning('Item image cleanup failed.', ['path' => $path]);
            }
        } catch (Throwable $exception) {
            Log::warning('Item image cleanup failed.', ['path' => $path, 'exception' => $exception->getMessage()]);
        }
    }
}
