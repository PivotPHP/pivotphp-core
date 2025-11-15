<?php

namespace PivotPHP\Core\Logging;

use RuntimeException;

/**
 * Handler para arquivo
 */
class FileHandler implements LogHandlerInterface
{
    private string $filePath;
    private string $dateFormat;

    /**
     * __construct method
     */
    public function __construct(string $filePath, string $dateFormat = 'Y-m-d H:i:s')
    {
        $this->filePath = $filePath;
        $this->dateFormat = $dateFormat;

        // Cria o diretório se não existir
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException("Cannot create log directory: {$dir}");
            }
        }
    }

    /**
     * Handle the request
     */
    public function handle(array $record): void
    {
        $message = $this->format($record);
        $written = @file_put_contents($this->filePath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($written === false) {
            error_log('Failed to write to log file: ' . $this->filePath);
        }
    }

    private function format(array $record): string
    {
        $datetime = $record['datetime']->format($this->dateFormat);
        $level = $record['level_name'];
        $message = $record['message'];

        $context = '';
        if (!empty($record['context'])) {
            $context = ' ' . json_encode($record['context']);
        }

        return "[{$datetime}][{$level}] {$message}{$context}";
    }
}
