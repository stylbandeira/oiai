<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageFromUrl
{
    public function saveImageFromUrl(string $url): string
    {
        if (blank($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('URL da imagem inválida.');
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Laravel Image Downloader)',
            'Accept' => 'image/*,*/*',
        ])
            ->timeout(20)
            ->get($url);

        if (! $response->successful()) {
            Log::error([
                'message' => 'Não foi possível baixar a imagem.',
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Não foi possível baixar a imagem.');
        }

        $content = $response->body();

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($content);

        if (! str_starts_with($mimeType, 'image/')) {
            throw new \Exception('A URL não retornou uma imagem válida.');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $path = 'products/images/' . Str::uuid() . '.' . $extension;

        Storage::disk('public')->put($path, $content);

        return $path;
    }
}
