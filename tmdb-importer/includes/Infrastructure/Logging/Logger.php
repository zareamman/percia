<?php
namespace TMDBImporter\Infrastructure\Logging;

use TMDBImporter\Infrastructure\Cache\ObjectCache;
use TMDBImporter\Infrastructure\Cache\CacheKeyGenerator;

class Logger
{
    private string $context = '';
    private int $minLevel = 6;
    private ObjectCache $cache;

    private static array $levelValues = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    public function __construct(?ObjectCache $cache = null)
    {
        $this->cache = $cache ?? new ObjectCache();
        $this->minLevel = $this->getConfiguredMinLevel();
    }

    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    public function setMinLevel(string $level): void
    {
        $this->minLevel = self::$levelValues[$level] ?? 6;
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if (self::$levelValues[$level] > $this->minLevel) {
            return;
        }

        $context = array_merge([
            'context' => $this->context,
            'timestamp' => current_time('mysql'),
            'memory' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        ], $context);

        $logEntry = sprintf(
            "[%s] [%s] %s %s",
            $context['timestamp'],
            strtoupper($level),
            $message,
            $context ? ' ' . wp_json_encode($context) : ''
        );

        error_log($logEntry);

        if (in_array($level, [LogLevel::ERROR, LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY], true)) {
            $this->persistLog($level, $message, $context);
        }
    }

    private function persistLog(string $level, string $message, array $context): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_logs';

        $wpdb->insert($table, [
            'level' => $level,
            'message' => $message,
            'context' => wp_json_encode($context),
            'created_at' => current_time('mysql'),
        ], ['%s', '%s', '%s', '%s']);
    }

    private function getConfiguredMinLevel(): int
    {
        $level = get_option('tmdb_importer_log_level', 'info');
        return self::$levelValues[$level] ?? 6;
    }

    public function getRecentLogs(int $limit = 100, string $level = ''): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_logs';

        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE 1=1 %s ORDER BY created_at DESC LIMIT %d",
            $level ? $wpdb->prepare(' AND level = %s', $level) : '',
            $limit
        );

        return $wpdb->get_results($query, ARRAY_A) ?? [];
    }

    public function clearLogs(int $olderThanDays = 30): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'tmdb_logs';

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $olderThanDays
        ));

        return $result !== false ? $result : 0;
    }
}