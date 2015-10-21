<?php
namespace Vda\Log;

use Vda\Log\Exception\LoggerBuildingFailedException;
use Vda\Log\Exception\UnsupportedFileLoggerDirectoryInitializer;
use Vda\Log\Exception\WrongLoggersConfigurationException;
use Vda\Log\Logger\ILoggerBuilder;

/**
 * LogService usage
 *
 *  Base methods:
 *    getLogger($context) - return logger by $context based on context configuration
 *    setFileLoggersDirectory($logDirectory) - it's need to set directory for file logs before usage
 *
 *  Service try find appropriate logger in context configuration
 *  Config key is a string delimited with '\' sign.
 *
 *  For context '\a\b\c' service will return logger:
 *   1) based on config with key '\a\b\c\', '\a\b', '\a'
 *   2) based on config with key \Vda\Log\ILogService::DEFAULT_CONTEXT
 *   3) otherwise return nop logger which do nothing
 *
 *  As a context key you can use namespace string, class name string, or custom string.
 *  Don't use underscore as first letter in key.
 *
 *  Config example:
 *    [
 *        '\some\custom\context'                => [
 *            // b) set settings for implementation of ILoggerBuilder, which will create logger
 *            [
 *                'class'  => $className, // \Vda\Log\ILoggerBuilder implementation
 *                'level'  => $psrLogLevel, // some \Psr\Log\LogLevel constant
 *                'config' => $configParamForLoggerBuilder, // param for \Vda\Log\ILoggerBuilder constructor arg $config
 *            ],
 *            // e.g.
 *            [
 *                'class' => \Vda\Log\Logger\BrowserConsoleLoggerBuilder::class,
 *                'level' => \Psr\Log\LogLevel::INFO,
 *            ],
 *            [
 *                'class' => \Vda\Log\Logger\BrowserPageLoggerBuilder::class,
 *                'level' => \Psr\Log\LogLevel::INFO,
 *            ],
 *            [
 *                'class'  => \Vda\Log\Logger\FileLoggerBuilder::class,
 *                'level'  => \Psr\Log\LogLevel::INFO,
 *                'config' => ['filename' => 'debug.log'],
 *            ],
 *        ],
 *        \Vda\Log\ILogService::DEFAULT_CONTEXT => [
 *            [
 *                'class'  => \Vda\Log\Logger\FileLoggerBuilder::class,
 *                'level'  => \Psr\Log\LogLevel::INFO,
 *                'config' => ['filename' => 'application.log'],
 *            ]
 *        ],
 *        'default-exception-to-review'         => [
 *            [
 *                'class'  => \Vda\Log\Logger\FileLoggerBuilder::class,
 *                'level'  => \Psr\Log\LogLevel::DEBUG,
 *                'config' => ['filename' => 'exceptions-to-review.log'],
 *            ],
 *        ],
 *        'default-console'                     => [
 *            [
 *                'class' => \Vda\Log\Logger\ConsoleLoggerBuilder::class,
 *                'level' => \Psr\Log\LogLevel::INFO,
 *            ],
 *        ],
 *    ];
 */
class LogService implements ILogService
{
    const CONTEXT_KEY = '_context';
    const CONTEXT_LOGGER_BUILDER_KEY = '_logger_builder';

    private $contextsConfig;

    /**
     * @var string | null
     */
    private $fileLoggersDirectory = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $nopeLogger;

    /**
     * @var \Psr\Log\LoggerInterface | null
     */
    private $defaultLogger = null;

    /**
     * @var boolean
     */
    private $isDefaultLoggerInited = false;

    /**
     * Tree of context structure with ContextLoggerBuilder objects in some leafs.
     * It's based on $contextConfig.
     *
     * @var array
     */
    private $loggersTree;

    private $contextToLoggerFastMap;

    /**
     * @var \Psr\Log\LoggerInterface[]
     */
    protected $loggersByContext = [];

    public function __construct($contextsConfig, $fileLoggersDirectory = null)
    {
        if (!is_null($fileLoggersDirectory)) {
            $this->setFileLoggersDirectory($fileLoggersDirectory);
        }

        $this->contextsConfig = (array)$contextsConfig;
        $this->nopeLogger = new LoggersCollection();

        $this->buildLoggersTree();
    }

    /**
     * @return null|\Psr\Log\LoggerInterface
     * @throws \Exception
     */
    private function getDefaultLogger()
    {
        if (!$this->isDefaultLoggerInited) {
            $defaultLoggerBuilder = $this->getLoggerBuilderByContext(self::DEFAULT_CONTEXT);
            if ($defaultLoggerBuilder) {
                $this->defaultLogger = $defaultLoggerBuilder->getLogger();
            }

            $this->isDefaultLoggerInited = true;
        }

        return $this->defaultLogger;
    }

    /**
     * @param callable|string $logDirectory
     * @throws UnsupportedFileLoggerDirectoryInitializer
     */
    public function setFileLoggersDirectory($logDirectory)
    {
        if (is_string($logDirectory)) {
            $logDirectoryString = $logDirectory;
        } else if (is_callable($logDirectory)) {
            $logDirectoryString = $logDirectory();
        } else {
            throw new UnsupportedFileLoggerDirectoryInitializer();
        }

        if (!is_string($logDirectoryString)) {
            throw new UnsupportedFileLoggerDirectoryInitializer();
        }

        $this->fileLoggersDirectory = $logDirectoryString . '/';
    }

    /**
     * @return string | null
     */
    public function getFileLoggersDirectory()
    {
        return $this->fileLoggersDirectory;
    }

    private function buildLoggersTree()
    {
        $this->loggersTree = [
            self::CONTEXT_KEY => '',
        ];

        foreach ($this->contextsConfig as $context => $loggersCollectionConfig) {
            $context = trim($context, '\\');
            $contextPath = explode('\\', $context);

            $pointer = &$this->loggersTree;

            foreach ($contextPath as $item) {
                if (!isset($pointer[$item])) {
                    $pointer[$item] = [
                        self::CONTEXT_KEY => $pointer[self::CONTEXT_KEY] . '\\' . $item,
                    ];
                }
                $pointer = &$pointer[$item];
            }

            if (!is_array($loggersCollectionConfig)) {
                throw new WrongLoggersConfigurationException('Context config must be an array');
            }

            $pointer[self::CONTEXT_LOGGER_BUILDER_KEY] = ContextLoggerBuilder::createWithLoggerConfig(
                $this,
                $pointer[self::CONTEXT_KEY],
                $loggersCollectionConfig
            );
        }
    }

    /**
     * Return loggers container
     *
     * @param $context
     * @return null|ContextLoggerBuilder
     */
    private function getLoggerBuilderByContext($context)
    {
        $context = trim($context, '\\');
        $contextPath = explode('\\', $context);

        $currentLeaf = $this->loggersTree;

        $loggerBuilder = null;

        foreach ($contextPath as $item) {
            if (isset($currentLeaf[$item])) {
                if (isset($currentLeaf[$item][self::CONTEXT_LOGGER_BUILDER_KEY])) {
                    $loggerBuilder = $currentLeaf[$item][self::CONTEXT_LOGGER_BUILDER_KEY];
                }

                $currentLeaf = $currentLeaf[$item];
            } else {
                break;
            }
        }

        return $loggerBuilder;
    }

    /**
     * @param string $context
     * @return null|\Psr\Log\LoggerInterface|LoggersCollection
     * @throws LoggerBuildingFailedException
     */
    public function getLogger($context)
    {
        if (isset($this->contextToLoggerFastMap[$context])) {
            return $this->contextToLoggerFastMap[$context];
        }

        $logger = null;

        $loggerBuilder = $this->getLoggerBuilderByContext($context);
        if ($loggerBuilder != null) {
            $logger = $loggerBuilder->getLogger();
        }

        $defaultLogger = $this->getDefaultLogger();

        if (!$logger && $defaultLogger) {
            $logger = $defaultLogger;
        }

        if (!$logger) {
            $logger = $this->nopeLogger;
        }

        $this->contextToLoggerFastMap[$context] = $logger;


        return $logger;
    }
}


/**
 * Contains logger or loggers configuration for particular context, based 'contextConfig' array.
 * Allows to build (automatically) and get final complex logger for context.
 *
 * Class ContextLoggerBuilder
 * @package Vda\Log
 */
class ContextLoggerBuilder
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    private $logger;

    /**
     * @var array|null
     */
    private $loggersCollectionConfig;
    /**
     * @var ILogService
     */
    private $logService;

    private function __construct(
        ILogService $logService,
        $context,
        \Psr\Log\LoggerInterface $logger = null,
        $loggersCollectionConfig = null
    ) {
        if (is_null($logger) && is_null($loggersCollectionConfig)) {
            throw new WrongLoggersConfigurationException('Logger or logger config must be set');
        }

        $this->context = $context;
        $this->logger = $logger;
        $this->loggersCollectionConfig = $loggersCollectionConfig;
        $this->logService = $logService;
    }

    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     * @throws LoggerBuildingFailedException
     * @throws WrongLoggersConfigurationException
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->buildLogger();
        }

        if (!$this->logger) {
            throw new LoggerBuildingFailedException();
        }

        return $this->logger;
    }

    private function buildLogger()
    {
        $loggersCollection = new LoggersCollection();

        foreach ($this->loggersCollectionConfig as $loggerConfig) {

            if (!isset($loggerConfig['class'])) {
                throw new WrongLoggersConfigurationException(
                    "Logger's config must to contain 'class' field");
            }
            if (!is_subclass_of($loggerConfig['class'], ILoggerBuilder::class)) {
                throw new WrongLoggersConfigurationException(
                    "Class '{$loggerConfig['class']}' must be instance of " . ILoggerBuilder::class);
            }

            if (!isset($loggerConfig['level'])) {
                throw new WrongLoggersConfigurationException(
                    "Logger's config must to contain 'level' field");
            }

            /* @var $loggerBuilder ILoggerBuilder */
            $loggerBuilder = new $loggerConfig['class'](
                $this->logService,
                $this->context,
                $loggerConfig['level'],
                empty($loggerConfig['config']) ? [] : $loggerConfig['config'])
            );

            $loggersCollection->addLogger($loggerBuilder->getLogger());
        }

        $this->logger = $loggersCollection;
    }

    public static function createWithLogger(ILogService $logService, $context, \Psr\Log\LoggerInterface $logger)
    {
        return new self($logService, $context, $logger, null);
    }

    public static function createWithLoggerConfig(ILogService $logService, $context, $loggerConfig) {
        if (isset($loggerConfig['class'])) {
            $loggerConfig = [$loggerConfig];
        }

        return new self($logService, $context, null, $loggerConfig);
    }
}
