<?php
namespace Vda\Log\Logger;

use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\FirePHPHandler;

class BrowserConsoleLoggerBuilder extends BaseLoggerBuilder
{
    protected function init()
    {
        $this->baseInit();

        $this->logger->pushHandler(new FirePHPHandler($this->monologLevel));
        $this->logger->pushHandler(new ChromePHPHandler($this->monologLevel));
    }
}
