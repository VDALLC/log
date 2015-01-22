<?php
namespace Vda\Log\Logger;

use Monolog\Handler\StreamHandler;
use Vda\Log\Formatter\BaseFormatter;
use Vda\Util\VarUtil;

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
            throw new \Exception('Directory for file logger is not set');//todo
        }

        $filename = VarUtil::ifEmpty($this->config['filename'], $this->getDefaultFilename());

        $handler = new StreamHandler("{$logDirectory}{$filename}", $this->monologLevel);
        $formatter = new BaseFormatter();
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);
    }
}
