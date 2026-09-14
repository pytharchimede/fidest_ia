<?php

namespace FidestIA\Services;

use RuntimeException;

final class DocumentStorageService
{
    public function __construct(private readonly string $basePath) {}

    public function store(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Téléversement invalide. Code PHP : ' . ($file['error'] ?? UPLOAD_ERR_NO_FILE));
        }

        $original = basename((string) ($file['name'] ?? 'document'));
        $tmp = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp) && !is_file($tmp)) {
            throw new RuntimeException('Fichier temporaire introuvable.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/tiff', 'application/pdf'];
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Format non supporté : ' . $mime);
        }

        $basePath = rtrim($this->basePath, DIRECTORY_SEPARATOR);

        if (!is_dir($basePath)) {
            if (!@mkdir($basePath, 0775, true) && !is_dir($basePath)) {
                throw new RuntimeException(
                    'Impossible de créer le dossier de stockage principal : ' . $basePath
                    . '. Vérifiez les droits d’écriture du processus PHP/Apache.'
                );
            }
        }

        if (!is_writable($basePath)) {
            throw new RuntimeException(
                'Le dossier de stockage n’est pas accessible en écriture : ' . $basePath
                . '. Donnez les droits d’écriture à l’utilisateur du serveur web.'
            );
        }

        $year = date('Y');
        $month = date('m');
        $directory = $basePath . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            $lastError = error_get_last();
            $detail = isset($lastError['message']) ? ' Détail : ' . $lastError['message'] : '';
            throw new RuntimeException('Impossible de créer le dossier de stockage : ' . $directory . '.' . $detail);
        }

        if (!is_writable($directory)) {
            throw new RuntimeException('Le dossier de stockage n’est pas accessible en écriture : ' . $directory);
        }

        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $extension = $extension ? preg_replace('/[^a-z0-9]/', '', $extension) : '';
        $stored = bin2hex(random_bytes(16)) . ($extension ? '.' . $extension : '');
        $absolute = $directory . DIRECTORY_SEPARATOR . $stored;

        $moved = is_uploaded_file($tmp)
            ? move_uploaded_file($tmp, $absolute)
            : rename($tmp, $absolute);

        if (!$moved) {
            throw new RuntimeException('Impossible de sauvegarder le document dans : ' . $directory);
        }

        @chmod($absolute, 0664);

        return [
            'original_name' => $original,
            'stored_name' => $stored,
            'absolute_path' => $absolute,
            'relative_path' => $year . '/' . $month . '/' . $stored,
            'mime_type' => $mime,
            'file_size' => filesize($absolute) ?: 0,
            'sha256' => hash_file('sha256', $absolute),
        ];
    }
}
