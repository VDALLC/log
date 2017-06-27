<?php
namespace Vda\Log;

use Psr\Log\NullLogger;

class LoggerFactory
{
    private static $logService;
    private static $nullLogger;

    public static function init(ILogService $logService)
    {
        self::$logService = $logService;
    }

    public static function getLogger($context)
    {
        if (self::$logService) {
            return self::$logService->getLogger($context);
        }

        if (!self::$nullLogger) {
            self::$nullLogger = new NullLogger();
        }

        return self::$nullLogger;
    }
}
