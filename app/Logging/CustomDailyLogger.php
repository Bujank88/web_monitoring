<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class CustomDailyLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $logger = new Logger('custom-daily');
        
        // Format: log_DD_MM_YYYY.log
        $date = now()->format('d_m_Y'); // Menggunakan Carbon helper
        $logPath = storage_path("logs/log_{$date}.log");
        
        $handler = new StreamHandler(
            $logPath,
            Logger::DEBUG
        );
        
        // Set formatter
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, 'Y-m-d H:i:s', true, true);
        $handler->setFormatter($formatter);
        
        $logger->pushHandler($handler);
        
        return $logger;
    }
}
