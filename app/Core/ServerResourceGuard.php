<?php
declare(strict_types=1);

namespace FidestIA\Core;

final class ServerResourceGuard
{
    public function __construct(
        private readonly bool $enabled = false,
        private readonly float $maxLoadPerCpu = 1.20,
        private readonly int $maxMemoryPercent = 85,
        private readonly int $retryAfterSeconds = 15
    ) {}

    /** @return array{status:string,available:bool,busy:bool,paused:bool,message:string,retry_after:int,metrics:array<string,mixed>} */
    public function status(): array
    {
        if (!$this->enabled) {
            return $this->available([]);
        }

        $metrics = $this->metrics();
        $reasons = [];

        $loadPerCpu = $metrics['load_per_cpu'] ?? null;
        if (is_float($loadPerCpu) && $loadPerCpu >= max(0.10, $this->maxLoadPerCpu)) {
            $reasons[] = 'charge_cpu';
        }

        $memoryPercent = $metrics['memory_percent'] ?? null;
        if (is_float($memoryPercent) && $memoryPercent >= max(1, min(100, $this->maxMemoryPercent))) {
            $reasons[] = 'memoire';
        }

        if ($reasons !== []) {
            return [
                'status' => 'paused',
                'available' => false,
                'busy' => true,
                'paused' => true,
                'message' => 'FIDEST IA est temporairement en pause pour protéger les ressources du serveur. Merci de patienter.',
                'retry_after' => max(1, $this->retryAfterSeconds),
                'metrics' => $metrics + ['reasons' => $reasons],
            ];
        }

        return $this->available($metrics);
    }

    public function assertAvailable(): void
    {
        $state = $this->status();
        if (!($state['available'] ?? false)) {
            throw new \RuntimeException('OCR_RESOURCE_BUSY: ressources serveur momentanément élevées. Réessayez dans quelques secondes.');
        }
    }

    /** @param array<string,mixed> $metrics */
    private function available(array $metrics): array
    {
        return [
            'status' => 'available',
            'available' => true,
            'busy' => false,
            'paused' => false,
            'message' => 'FIDEST IA est disponible.',
            'retry_after' => 0,
            'metrics' => $metrics,
        ];
    }

    /** @return array<string,mixed> */
    private function metrics(): array
    {
        $metrics = [];

        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
        $cpuCount = $this->cpuCount();
        if (is_array($load) && isset($load[0]) && is_numeric($load[0])) {
            $load1 = (float) $load[0];
            $metrics['load_1m'] = round($load1, 2);
            $metrics['cpu_count'] = $cpuCount;
            $metrics['load_per_cpu'] = round($load1 / max(1, $cpuCount), 2);
        }

        $memory = $this->memoryUsage();
        if ($memory !== null) {
            $metrics += $memory;
        }

        return $metrics;
    }

    private function cpuCount(): int
    {
        $count = 0;
        if (is_readable('/proc/cpuinfo')) {
            $content = @file_get_contents('/proc/cpuinfo');
            if (is_string($content)) {
                $count = preg_match_all('/^processor\s*:/m', $content) ?: 0;
            }
        }
        return max(1, $count);
    }

    /** @return array{memory_total_mb:int,memory_available_mb:int,memory_percent:float}|null */
    private function memoryUsage(): ?array
    {
        if (!is_readable('/proc/meminfo')) {
            return null;
        }

        $content = @file_get_contents('/proc/meminfo');
        if (!is_string($content)) {
            return null;
        }

        $values = [];
        foreach (preg_split('/\r?\n/', $content) ?: [] as $line) {
            if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)\s+kB$/', trim($line), $m)) {
                $values[$m[1]] = (int) $m[2];
            }
        }

        if (empty($values['MemTotal']) || !isset($values['MemAvailable'])) {
            return null;
        }

        $total = $values['MemTotal'];
        $available = max(0, $values['MemAvailable']);
        $usedPercent = (($total - $available) / max(1, $total)) * 100;

        return [
            'memory_total_mb' => (int) round($total / 1024),
            'memory_available_mb' => (int) round($available / 1024),
            'memory_percent' => round($usedPercent, 1),
        ];
    }
}
