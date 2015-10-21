<?php
namespace Vda\Log\Logger;

use Monolog\Handler\StreamHandler;
use Vda\Log\Formatter\BaseFormatter;

class FileLoggerBuilder extends BaseLoggerBuilder
{
    protected function getDefaultFilename()
    {
        return 'application.log';
    }

    protected function init()
    {
        $this->baseInit();

        $logDirectory = $this->logService->getFileLoggersDirectory();

        if (is_null($logDirectory)) {
            throw new \Exception('Directory for file logger is not set');
        }

        $filename = empty($this->config['filename'])
            ? $this->getDefaultFilename()
            : $this->config['filename'];

        $handler = new StreamHandler("{$logDirectory}{$filename}", $this->monologLevel);
        $formatter = new BaseFormatter();
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);
    }
}
