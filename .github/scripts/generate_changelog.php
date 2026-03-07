<?php

declare(strict_types=1);

if ($argc < 7) {
    fwrite(STDERR, "Usage: php generate_changelog.php <previous-version.php> <current-version.php> <changelog.md> <release-notes.md> <previous-package-version> <new-package-version>\n");
    exit(1);
}

[$script, $previousVersionPath, $currentVersionPath, $changelogPath, $releaseNotesPath, $previousPackageVersion, $newPackageVersion] = $argv;

$previous = loadVersionFile($previousVersionPath);
$current = loadVersionFile($currentVersionPath);

$releaseDate = $current['data_date'] ?? gmdate('Y-m-d');
$oldHash = shortHash($previous['source']['hash'] ?? $previous['source_hash'] ?? 'unknown');
$newHash = shortHash($current['source']['hash'] ?? $current['source_hash'] ?? 'unknown');
$oldDownloaded = (int) ($previous['counts']['total'] ?? $previous['downloaded'] ?? 0);
$newDownloaded = (int) ($current['counts']['total'] ?? $current['downloaded'] ?? 0);
$oldProv = (int) ($previous['counts']['prov'] ?? 0);
$newProv = (int) ($current['counts']['prov'] ?? 0);
$oldKab = (int) ($previous['counts']['kab'] ?? 0);
$newKab = (int) ($current['counts']['kab'] ?? 0);
$assetName = (string) ($current['asset']['name'] ?? 'wilayah-logos.zip');
$assetSize = (int) ($current['asset']['size_bytes'] ?? 0);
$assetSizeLabel = formatBytes($assetSize);

$entry = implode("\n", [
    "## [{$newPackageVersion}] — {$releaseDate}",
    '',
    '### Asset Sync',
    "- Package version: `{$previousPackageVersion}` -> `{$newPackageVersion}`",
    "- Upstream `cahyadsn/wilayah_logo`: `{$oldHash}` -> `{$newHash}`",
    '',
    '### Statistik',
    '- Downloaded assets: ' . $oldDownloaded . ' -> ' . $newDownloaded . ' (' . formatDelta($newDownloaded - $oldDownloaded) . ')',
    '- Province assets: ' . $oldProv . ' -> ' . $newProv . ' (' . formatDelta($newProv - $oldProv) . ')',
    '- Regency assets: ' . $oldKab . ' -> ' . $newKab . ' (' . formatDelta($newKab - $oldKab) . ')',
    "- Asset archive: `{$assetName}` ({$assetSizeLabel})",
    '',
]);

$releaseNotes = implode("\n", [
    '## Update Asset Logo/Lambang Daerah',
    '',
    "Release package: `v{$newPackageVersion}`",
    "Tanggal sync: `{$releaseDate}`",
    '',
    '### Upstream',
    "- `cahyadsn/wilayah_logo`: `{$oldHash}` -> `{$newHash}`",
    '',
    '### Statistik Asset',
    '- Downloaded assets: ' . $oldDownloaded . ' -> ' . $newDownloaded . ' (' . formatDelta($newDownloaded - $oldDownloaded) . ')',
    '- Province assets: ' . $oldProv . ' -> ' . $newProv . ' (' . formatDelta($newProv - $oldProv) . ')',
    '- Regency assets: ' . $oldKab . ' -> ' . $newKab . ' (' . formatDelta($newKab - $oldKab) . ')',
    "- Asset archive: `{$assetName}` ({$assetSizeLabel})",
    '',
    '### Update di project Anda',
    '```bash',
    'composer update aliziodev/laravel-wilayah-logos',
    'php artisan logos:publish --force',
    '```',
    '',
]);

$existing = file_exists($changelogPath) ? file_get_contents($changelogPath) : '';
if ($existing === false) {
    fwrite(STDERR, "Failed to read {$changelogPath}\n");
    exit(1);
}

$updated = injectChangelogEntry($existing, $entry, $newPackageVersion);
file_put_contents($changelogPath, $updated);
file_put_contents($releaseNotesPath, $releaseNotes);

function loadVersionFile(string $path): array
{
    if (! file_exists($path)) {
        return [];
    }

    $data = include $path;

    return is_array($data) ? $data : [];
}

function shortHash(string $hash): string
{
    if ($hash === '' || $hash === 'unknown' || $hash === 'none') {
        return $hash;
    }

    return strlen($hash) > 12 ? substr($hash, 0, 12) : $hash;
}

function formatDelta(int $delta): string
{
    if ($delta > 0) {
        return '+' . $delta;
    }

    return (string) $delta;
}

function formatBytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
    $value = $bytes / (1024 ** $power);

    return number_format($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
}

function injectChangelogEntry(string $contents, string $entry, string $newVersion): string
{
    if (trim($contents) === '') {
        return "# Changelog\n\n{$entry}\n";
    }

    if (str_contains($contents, '## [Unreleased]')) {
        $contents = preg_replace('/## \[Unreleased\]\s*/', "## [Unreleased]\n\n---\n\n{$entry}", $contents, 1);
    } else {
        $contents = "# Changelog\n\n{$entry}\n" . ltrim($contents);
    }

    if (preg_match('/^\[Unreleased\]: .*$/m', $contents)) {
        $contents = preg_replace(
            '/^\[Unreleased\]: .*$/m',
            "[Unreleased]: https://github.com/aliziodev/laravel-wilayah-logos/compare/v{$newVersion}...HEAD",
            $contents,
            1
        );
    }

    if (! preg_match('/^\[' . preg_quote($newVersion, '/') . '\]:/m', $contents)) {
        $contents = rtrim($contents) . "\n[" . $newVersion . "]: https://github.com/aliziodev/laravel-wilayah-logos/releases/tag/v{$newVersion}\n";
    }

    return $contents;
}
