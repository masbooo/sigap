<?php

declare(strict_types=1);

require_once __DIR__ . '/../Legacy.php';

if (!function_exists('upload_file')) {
    function upload_file(array $file, string $directory, ?array $allowedExtensions = null): ?string
    {
        $allowedExtensions = array_map('strtolower', $allowedExtensions ?? ['jpg', 'jpeg', 'png', 'pdf']);

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($tmpName === '' || $extension === '' || !in_array($extension, $allowedExtensions, true)) {
            return null;
        }

        if (!is_uploaded_file($tmpName) && !is_file($tmpName)) {
            return null;
        }

        $directory = legacy_normalize_upload_relative_path($directory);
        if ($directory === '' || str_contains($directory, '..')) {
            return null;
        }

        $targetDirectory = legacy_upload_path($directory);
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            return null;
        }

        $filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $extension;
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;

        $moved = is_uploaded_file($tmpName)
            ? move_uploaded_file($tmpName, $targetPath)
            : rename($tmpName, $targetPath);

        return $moved ? $filename : null;
    }
}
