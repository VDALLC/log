<?php
namespace Vda\Log\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use Vda\Log\Formatter\HtmlDebugFormatter;

class BrowserPageLoggerBuilder extends BaseLoggerBuilder
{
    protected  function init()
    {
        $this->baseInit();

        $this->logger->pushProcessor(new IntrospectionProcessor());
        $this->logger->pushHandler(
            (new StreamHandler('php://output', $this->monologLevel))
                ->setFormatter(
                    new HtmlDebugFormatter()
                )
        );
    }
}
