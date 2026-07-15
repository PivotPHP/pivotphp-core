<?php

declare(strict_types=1);

namespace PivotPHP\Core\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Simple PSR-3 compliant logger implementation
 */
class PsrLogger extends AbstractLogger
{
    private string $logPath;
    private string $dateFormat;
    private array $logLevels;

    /**
     * __construct method
     */
    public function __construct(
        string $logPath = '',
        string $dateFormat = 'Y-m-d H:i:s'
    ) {
        $this->logPath = $logPath ?: ($_ENV['LOG_PATH'] ?? sys_get_temp_dir() . '/pivotphp.log');
        $this->dateFormat = $dateFormat;
        $this->logLevels = [
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT     => 1,
            LogLevel::CRITICAL  => 2,
            LogLevel::ERROR     => 3,
            LogLevel::WARNING   => 4,
            LogLevel::NOTICE    => 5,
            LogLevel::INFO      => 6,
            LogLevel::DEBUG     => 7,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\Stringable $level
     */
    public function log(
        $level,
        string|\Stringable $message,
        array $context = []
    ): void {
        $levelString = (string)$level;

        if (!isset($this->logLevels[$levelString])) {
            throw new \InvalidArgumentException("Unknown log level: {$levelString}");
        }

        $logEntry = $this->formatMessage($levelString, $message, $context);

        $this->writeToFile($logEntry);
    }

    /**
     * Format the log message
     */
    private function formatMessage(
        string $level,
        string|\Stringable $message,
        array $context
    ): string {
        $timestamp = date($this->dateFormat);
        $levelUpper = strtoupper($level);

        // Interpolate context values into message placeholders
        $message = $this->interpolate((string)$message, $context);

        // Format: [2023-12-07 10:30:45] INFO: Message content
        $logEntry = "[{$timestamp}] {$levelUpper}: {$message}";

        // Add context if provided
        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $logEntry . PHP_EOL;
    }

    /**
     * Interpolate context values into the message placeholders
     */
    private function interpolate(string $message, array $context): string
    {
        // Build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // Check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // Interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Write log entry to file
     */
    private function writeToFile(string $logEntry): void
    {
        try {
            // Create directory if it doesn't exist
            $dir = dirname($this->logPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Append to log file
            file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Fallback to error_log if file writing fails
            error_log("Express Logger Error: " . $e->getMessage());
            error_log($logEntry);
        }
    }

    /**
     * Set the log file path
     */
    public function setLogPath(string $path): void
    {
        $this->logPath = $path;
    }

    /**
     * Get the current log file path
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }

    /**
     * Clear the log file
     */
    public function clear(): bool
    {
        return file_put_contents($this->logPath, '') !== false;
    }
}
