<?php
namespace Vda\Log;

class LoggersCollection implements \Psr\Log\LoggerInterface
{
    /**
     * @var \Psr\Log\LoggerInterface[]
     */
    private $loggers = [];

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function addLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->loggers[] = $logger;
    }

    /**
     * @inheritdoc
     */
    public function emergency($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->emergency($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function alert($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->alert($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function critical($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->critical($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->error($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function warning($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->warning($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function notice($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->notice($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->info($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->debug($message, $context);
        }
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = array())
    {
        foreach ($this->loggers as $logger) {
            $logger->log($message, $context);
        }
    }
}