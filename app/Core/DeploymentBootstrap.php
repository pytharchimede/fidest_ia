<?php

declare(strict_types=1);

namespace FidestIA\Core;

use RuntimeException;

final class DeploymentBootstrap
{
    public function __construct(private readonly string $rootPath) {}

    public function run(array $config): array
    {
        $checks = [];

        $directories = [
            $config['storage']['documents_path'] ?? $this->rootPath . '/storage/documents',
            $this->rootPath . '/storage/tmp',
            $this->rootPath . '/storage/logs',
            $this->rootPath . '/storage/cache',
        ];

        foreach ($directories as $directory) {
            $checks['directories'][$directory] = $this->ensureDirectory($directory);
        }

        $checks['php']['version'] = PHP_VERSION;
        $checks['php']['pdo_mysql'] = extension_loaded('pdo_mysql');
        $checks['php']['fileinfo'] = extension_loaded('fileinfo');
        $checks['php']['exec_available'] = $this->commandExecutionAvailable();

        $checks['system']['tesseract'] = $this->findBinary((string) ($config['ocr']['binary'] ?? 'tesseract'));
        $checks['system']['pdftoppm'] = $this->findBinary('pdftoppm');

        return $checks;
    }

    private function ensureDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if (is_dir($directory)) {
            @chmod($directory, 0775);
        }

        return [
            'exists' => is_dir($directory),
            'writable' => is_writable($directory),
        ];
    }

    private function commandExecutionAvailable(): bool
    {
        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));

        foreach (['exec', 'shell_exec', 'proc_open'] as $function) {
            if (function_exists($function) && !in_array($function, $disabled, true)) {
                return true;
            }
        }

        return false;
    }

    private function findBinary(string $binary): ?string
    {
        if ($binary === '') {
            return null;
        }

        if (str_contains($binary, '/') && is_executable($binary)) {
            return $binary;
        }

        if (!$this->commandExecutionAvailable()) {
            return null;
        }

        $path = null;
        if (function_exists('shell_exec')) {
            $result = @shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null');
            $path = is_string($result) ? trim($result) : null;
        } elseif (function_exists('exec')) {
            $output = [];
            $code = 1;
            @exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $code);
            if ($code === 0 && isset($output[0])) {
                $path = trim((string) $output[0]);
            }
        }

        return $path !== '' ? $path : null;
    }
}
