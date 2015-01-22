<?php
namespace Vda\Log\Logger;

use Vda\Log\ILogService;

interface ILoggerBuilder
{
    public function __construct(ILogService $logService, $context, $level, $config = []);

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger();
}
