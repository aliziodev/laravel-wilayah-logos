<?php

namespace Aliziodev\WilayahLogos\Support;

use RuntimeException;
use ZipArchive;

class LogoAssetManager
{
    public function manifestPath(): string
    {
        return __DIR__.'/../../assets/version.php';
    }

    public function manifest(): array
    {
        $manifestPath = $this->manifestPath();

        if (! file_exists($manifestPath)) {
            return [];
        }

        $manifest = require $manifestPath;

        return is_array($manifest) ? $manifest : [];
    }

    public function archivePath(?array $manifest = null): string
    {
        $manifest ??= $this->manifest();

        return rtrim((string) config('logos.storage_path', storage_path('app/wilayah-logos')), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .($manifest['asset']['name'] ?? 'wilayah-logos.zip');
    }

    public function extractedPath(): string
    {
        return rtrim((string) config('logos.storage_path', storage_path('app/wilayah-logos')), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .'extracted';
    }

    public function publicPath(): string
    {
        return rtrim((string) config('logos.public_path', public_path('vendor/wilayah/logos')), DIRECTORY_SEPARATOR);
    }

    public function ensureArchiveAvailable(): string
    {
        $manifest = $this->manifest();
        $archivePath = $this->archivePath($manifest);
        $localBuildPath = __DIR__.'/../../.build/wilayah-logos.zip';

        if (file_exists($archivePath) && filesize($archivePath) > 0) {
            return $archivePath;
        }

        if (file_exists($localBuildPath) && filesize($localBuildPath) > 0) {
            return $localBuildPath;
        }

        $assetUrl = $manifest['asset']['url'] ?? null;
        if (! is_string($assetUrl) || $assetUrl === '') {
            throw new RuntimeException('Logo asset URL tidak tersedia di manifest package.');
        }

        $directory = dirname($archivePath);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Gagal membuat direktori archive logo: {$directory}");
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: aliziodev-logos-asset/1.0\r\n",
                'timeout' => (int) config('logos.asset_timeout', 300),
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $stream = @fopen($assetUrl, 'rb', false, $context);
        if (! $stream) {
            throw new RuntimeException("Gagal mengunduh logo archive dari {$assetUrl}");
        }

        $target = @fopen($archivePath, 'wb');
        if (! $target) {
            fclose($stream);
            throw new RuntimeException("Gagal menulis logo archive ke {$archivePath}");
        }

        stream_copy_to_stream($stream, $target);
        fclose($stream);
        fclose($target);

        if (! file_exists($archivePath) || filesize($archivePath) === 0) {
            throw new RuntimeException('Logo archive terunduh tetapi kosong.');
        }

        return $archivePath;
    }

    public function ensureExtracted(bool $force = false): string
    {
        $archivePath = $this->ensureArchiveAvailable();
        $extractPath = $this->extractedPath();

        if ($force && is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }

        if (is_dir($extractPath.'/prov') || is_dir($extractPath.'/kab')) {
            return $extractPath;
        }

        if (! is_dir($extractPath) && ! mkdir($extractPath, 0755, true) && ! is_dir($extractPath)) {
            throw new RuntimeException("Gagal membuat direktori extract: {$extractPath}");
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException("Gagal membuka archive logo: {$archivePath}");
        }

        if (! $zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException("Gagal extract archive logo ke {$extractPath}");
        }

        $zip->close();

        return $extractPath;
    }

    public function publishToPublic(bool $force = false): string
    {
        $extractPath = $this->ensureExtracted($force);
        $publicPath = $this->publicPath();

        if ($force && is_dir($publicPath)) {
            $this->deleteDirectory($publicPath);
        }

        $this->copyDirectory($extractPath, $publicPath);

        return $publicPath;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($source)) {
            throw new RuntimeException("Direktori sumber logo tidak ditemukan: {$source}");
        }

        if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
            throw new RuntimeException("Gagal membuat direktori tujuan logo: {$destination}");
        }

        $items = scandir($source);
        if ($items === false) {
            throw new RuntimeException("Gagal membaca direktori logo: {$source}");
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source.DIRECTORY_SEPARATOR.$item;
            $destinationPath = $destination.DIRECTORY_SEPARATOR.$item;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);

                continue;
            }

            copy($sourcePath, $destinationPath);
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
