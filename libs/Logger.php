<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: Logger
 * ------------------------------------------------------------
 */

class Logger
{
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const NOTICE = 'NOTICE';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    private string $logPath;

    public function __construct(string $logPath)
    {
        $this->logPath = rtrim($logPath, DIRECTORY_SEPARATOR);

        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Scrive una riga di log.
     */
    public function write(
        string $level,
        string $message,
        array $context = []
    ): void {

        $file = $this->logPath . DIRECTORY_SEPARATOR .
                date('Y-m-d') . '.log';

        $record = [
            'datetime' => date('Y-m-d H:i:s'),
            'level'    => strtoupper($level),
            'message'  => $message,
            'context'  => $context
        ];

        file_put_contents(
            $file,
            json_encode(
                $record,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    public function debug(
        string $message,
        array $context = []
    ): void {
        $this->write(self::DEBUG, $message, $context);
    }

    public function info(
        string $message,
        array $context = []
    ): void {
        $this->write(self::INFO, $message, $context);
    }

    public function notice(
        string $message,
        array $context = []
    ): void {
        $this->write(self::NOTICE, $message, $context);
    }

    public function warning(
        string $message,
        array $context = []
    ): void {
        $this->write(self::WARNING, $message, $context);
    }

    public function error(
        string $message,
        array $context = []
    ): void {
        $this->write(self::ERROR, $message, $context);
    }

    public function critical(
        string $message,
        array $context = []
    ): void {
        $this->write(self::CRITICAL, $message, $context);
    }

    /**
     * Log di eccezione.
     */
    public function exception(Throwable $e): void
    {
        $this->critical(
            $e->getMessage(),
            [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'code'  => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]
        );
    }
}