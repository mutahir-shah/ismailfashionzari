<?php

namespace App\Services\Stability;

class LogCertificationScanner
{
    private string $logPath;
    private int $startOffset = 0;

    /**
     * Known intentional/benign log patterns that should not fail certification.
     * Every entry must have an explicit reason.
     *
     * @var array<string, string>
     */
    private array $allowList = [
        // 'Known test exception pattern' => 'Reason why this is safe and expected in test setup',
    ];

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? storage_path('logs/laravel.log');
    }

    /**
     * Snapshot the current byte offset of the log file before tests run.
     */
    public function snapshot(): void
    {
        if (file_exists($this->logPath)) {
            clearstatcache(true, $this->logPath);
            $this->startOffset = (int) filesize($this->logPath);
        } else {
            $this->startOffset = 0;
        }
    }

    /**
     * Scan newly appended log lines for unexpected errors or exceptions.
     *
     * @return array{
     *     passed: bool,
     *     error_count: int,
     *     errors: array<array{line: string, level: string, message: string}>
     * }
     */
    public function scan(): array
    {
        if (!file_exists($this->logPath)) {
            return [
                'passed' => true,
                'error_count' => 0,
                'errors' => [],
            ];
        }

        clearstatcache(true, $this->logPath);
        $currentSize = (int) filesize($this->logPath);

        if ($currentSize <= $this->startOffset) {
            return [
                'passed' => true,
                'error_count' => 0,
                'errors' => [],
            ];
        }

        $handle = fopen($this->logPath, 'rb');
        if (!$handle) {
            return [
                'passed' => true,
                'error_count' => 0,
                'errors' => [],
            ];
        }

        fseek($handle, $this->startOffset);
        $newContent = fread($handle, $currentSize - $this->startOffset);
        fclose($handle);

        if (!$newContent) {
            return [
                'passed' => true,
                'error_count' => 0,
                'errors' => [],
            ];
        }

        $lines = explode("\n", $newContent);
        $errors = [];

        $criticalPatterns = [
            '/\b(production|local|testing)\.ERROR\b/i',
            '/\b(production|local|testing)\.CRITICAL\b/i',
            '/\b(production|local|testing)\.EMERGENCY\b/i',
            '/\bSQLSTATE\[/i',
            '/\bTypeError\b/i',
            '/\bErrorException\b/i',
            '/\bUndefined variable\b/i',
            '/\bAttempt to read property\b/i',
            '/\bDeadlock found\b/i',
            '/\bLock wait timeout exceeded\b/i',
            '/\bMaximum execution time of\b/i',
            '/\bAllowed memory size of\b/i',
        ];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            foreach ($criticalPatterns as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $isAllowListed = false;
                    foreach ($this->allowList as $allowPattern => $reason) {
                        if (stripos($trimmed, $allowPattern) !== false) {
                            $isAllowListed = true;
                            break;
                        }
                    }

                    if (!$isAllowListed) {
                        $errors[] = [
                            'line' => substr($trimmed, 0, 300),
                            'level' => 'ERROR',
                            'message' => substr($trimmed, 0, 200),
                        ];
                        break;
                    }
                }
            }
        }

        return [
            'passed' => count($errors) === 0,
            'error_count' => count($errors),
            'errors' => $errors,
        ];
    }
}
