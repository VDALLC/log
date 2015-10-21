<?php
namespace Vda\Log\Logger;

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Vda\Log\ILogService;

abstract class BaseLoggerBuilder implements ILoggerBuilder
{
    protected $context;
    protected $monologLevel;
    protected $config;

    /**
     * @var ILogService
     */
    protected $logService;

    /**
     * @var Logger
     */
    protected $logger;

    protected $initParams;

    public static function getPsrToMonologLogLevelMap()
    {
        return [
            LogLevel::DEBUG => Logger::DEBUG,
            LogLevel::ALERT => Logger::ALERT,
            LogLevel::CRITICAL => Logger::CRITICAL,
            LogLevel::EMERGENCY => Logger::EMERGENCY,
            LogLevel::WARNING => Logger::WARNING,
            LogLevel::NOTICE => Logger::NOTICE,
            LogLevel::INFO => Logger::INFO,
            LogLevel::ERROR => Logger::ERROR,
        ];
    }

    public function __construct(ILogService $logService, $context, $level, $config = [])
    {
        $this->logService = $logService;
        $this->context = $context;

        $psrLevel = $level ?: LogLevel::INFO;
        $this->monologLevel = self::getPsrToMonologLogLevelMap()[$psrLevel] ?: LogLevel::INFO;

        $this->config = $config;

        $this->init();
    }

    protected function baseInit()
    {
        $this->logger = new Logger($this->context);

        if (!empty($this->config['timezone'])) {
            $this->logger->setTimezone(new \DateTimeZone($this->config['timezone']));
        }

        $this->logger->pushProcessor(new ProcessIdProcessor());
        $this->logger->pushProcessor(new IntrospectionProcessor());
    }

    protected  function init()
    {
        $this->baseInit();
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
