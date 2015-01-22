<?php
namespace Vda\Log\Logger;


use Monolog\Handler\StreamHandler;
use Vda\Log\Formatter\BaseFormatter;

class ConsoleLoggerBuilder extends BaseLoggerBuilder
{
    protected  function init()
    {
        $this->baseInit();

        $this->logger->pushHandler(
            (new StreamHandler('php://stdout', $this->monologLevel))
                ->setFormatter(new BaseFormatter("%datetime% %level_name% %message%\n", "H:i:s"))
        );
    }
} 