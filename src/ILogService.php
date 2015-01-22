<?php
namespace Vda\Log;

interface ILogService
{
    const DEFAULT_CONTEXT = '_default';

    //useful contexts
    const SYSTEM_CONSOLE_CONTEXT = '_system_console_context';
    const BROWSER_CONSOLE_CONTEXT = '_browser_console_context';
    const BROWSER_PAGE_CONTEXT = '_browser_page_context';

    /**
     * Return PSR logger by context string.
     *
     * @param string $context
     * @return \Psr\Log\LoggerInterface
     * @throws Exception\LoggerBuildingFailedException
     */
    public function getLogger($context);

    /**
     * Method initializes the absolute path of the directory that can be used by file loggers
     *
     * @param callable|string $logDirectory - can be a directory string, or callback that return directory
     * @throws Exception\UnsupportedFileLoggerDirectoryInitializer
     */
    public function setFileLoggersDirectory($logDirectory);

    /**
     * Method return absolute path of the directory for file loggers or null if it's not set
     *
     * @return string | null
     */
    public function getFileLoggersDirectory();
}