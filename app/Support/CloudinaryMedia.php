<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CloudinaryMedia
{
    public static function upload(UploadedFile $file, string $folder, string $prefix = 'arquivo'): string
    {
        $path = $file->getRealPath();

        if (! $path || ! is_file($path)) {
            throw new RuntimeException('Não foi possível acessar o arquivo enviado.');
        }

        return self::uploadLocal($path, $file->getClientOriginalName(), $folder, $prefix);
    }

    public static function uploadLocal(string $path, string $filename, string $folder, string $prefix = 'arquivo'): string
    {
        $publicId = self::publicId($folder, $prefix);
        $params = self::signedParams(['public_id' => $publicId]);

        $response = Http::timeout(40)
            ->retry(2, 300)
            ->attach('file', file_get_contents($path), $filename)
            ->post(self::endpoint('image/upload'), [
                ...$params,
                'api_key' => self::apiKey(),
            ])
            ->throw();

        return self::secureUrl($response->json());
    }

    public static function uploadUrl(string $url, string $folder, string $prefix = 'arquivo'): string
    {
        $publicId = self::publicId($folder, $prefix);
        $params = self::signedParams(['public_id' => $publicId]);

        $response = Http::timeout(40)
            ->retry(2, 300)
            ->asForm()
            ->post(self::endpoint('image/upload'), [
                'file' => $url,
                ...$params,
                'api_key' => self::apiKey(),
            ])
            ->throw();

        return self::secureUrl($response->json());
    }

    public static function duplicate(?string $source, string $folder, string $prefix = 'evidencia'): ?string
    {
        if (! $source) {
            return null;
        }

        if (filter_var($source, FILTER_VALIDATE_URL)) {
            return self::uploadUrl($source, $folder, $prefix);
        }

        $relative = ltrim(str_replace('\\', '/', $source), '/');
        $publicPath = public_path($relative);

        if (is_file($publicPath)) {
            return self::uploadLocal($publicPath, basename($publicPath), $folder, $prefix);
        }

        return null;
    }

    public static function delete(?string $value): void
    {
        if (! $value) {
            return;
        }

        $publicId = self::publicIdFromUrl($value);

        if ($publicId) {
            $params = self::signedParams([
                'invalidate' => 'true',
                'public_id' => $publicId,
            ]);

            try {
                Http::timeout(30)
                    ->retry(2, 300)
                    ->asForm()
                    ->post(self::endpoint('image/destroy'), [
                        ...$params,
                        'api_key' => self::apiKey(),
                    ])
                    ->throw();
            } catch (Throwable $exception) {
                Log::warning('Falha ao excluir imagem no Cloudinary.', [
                    'public_id' => $publicId,
                    'erro' => $exception->getMessage(),
                ]);
            }

            return;
        }

        if (str_starts_with($value, '/')) {
            $path = public_path(ltrim($value, '/'));

            if (is_file($path)) {
                unlink($path);
            }

            return;
        }

        if (Storage::disk('local')->exists($value)) {
            Storage::disk('local')->delete($value);
        }
    }

    public static function isManagedUrl(?string $url): bool
    {
        return self::publicIdFromUrl($url) !== null;
    }

    private static function publicIdFromUrl(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host !== 'res.cloudinary.com') {
            return null;
        }

        $cloudName = trim((string) config('cloudinary.cloud_name'));

        if ($cloudName === '') {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        $prefix = '/' . $cloudName . '/image/upload/';

        if (! str_starts_with($path, $prefix)) {
            return null;
        }

        $relative = substr($path, strlen($prefix));
        $relative = preg_replace('~^v\d+/~', '', $relative) ?? $relative;
        $relative = preg_replace('~\.[A-Za-z0-9]+$~', '', $relative) ?? $relative;

        return $relative !== '' ? $relative : null;
    }

    private static function signedParams(array $params): array
    {
        $params['timestamp'] = time();
        ksort($params);

        $signature = collect($params)
            ->map(fn($value, $key) => $key . '=' . $value)
            ->implode('&');

        $params['signature'] = sha1($signature . self::apiSecret());

        return $params;
    }

    private static function publicId(string $folder, string $prefix): string
    {
        $folder = trim($folder, '/');
        $prefix = preg_replace('/[^A-Za-z0-9_-]+/', '_', $prefix) ?: 'arquivo';

        return $folder . '/' . $prefix . '_' . Str::uuid();
    }

    private static function secureUrl(array $response): string
    {
        $url = $response['secure_url'] ?? null;

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('O Cloudinary não retornou a URL da imagem.');
        }

        return $url;
    }

    private static function endpoint(string $action): string
    {
        return 'https://api.cloudinary.com/v1_1/' . self::cloudName() . '/' . $action;
    }

    private static function cloudName(): string
    {
        return self::configValue('cloud_name');
    }

    private static function apiKey(): string
    {
        return self::configValue('api_key');
    }

    private static function apiSecret(): string
    {
        return self::configValue('api_secret');
    }

    private static function configValue(string $key): string
    {
        $value = trim((string) config('cloudinary.' . $key));

        if ($value === '') {
            throw new RuntimeException('Configure as credenciais do Cloudinary no arquivo .env.');
        }

        return $value;
    }
}
