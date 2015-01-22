<?php
namespace Vda\Log\Formatter;

use Monolog\Formatter\NormalizerFormatter;
use Exception;

class BaseFormatter extends NormalizerFormatter
{
    const MAX_PREVIOUS_EXCEPTION_DEPTH = 10;

    const SIMPLE_FORMAT = "%extra.process_id% %datetime% %level_name% %message%%context%\n";

    protected $format;
    protected $allowInlineLineBreaks;

    /**
     * @param string $format                The format of the message
     * @param string $dateFormat            The format of the timestamp: one supported by DateTime::format
     * @param bool   $allowInlineLineBreaks Whether to allow inline line breaks in log entries
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false)
    {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        parent::__construct($dateFormat);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $vars = parent::format($record);

        $output = $this->format;
        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->convertToString($val), $output);
                unset($vars['extra'][$var]);
            }
        }
        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $val = $this->convertToString($val);

                if ($var == 'context') {
                    if ($val !== '') {
                        $val = "\n" . $val;
                    }
                }

                $output = str_replace('%'.$var.'%', $val, $output);
            }
        }

        return $output;
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    protected function normalizeException(\Exception $e, $depth = 0)
    {
        $class = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();

        $previous = $e->getPrevious();
        if (!empty($previous) && $depth < self::MAX_PREVIOUS_EXCEPTION_DEPTH) {
            $depth++;
            $previousException = "\nPrevious exception[{$depth}]: " . $this->normalizeException($previous, $depth);
        } else {
            $previousException = '';
        }

        $trace = $e->getTraceAsString();

        $exceptionMessage =<<<EE

Class: {$class}
Message: {$message}
Thrown at {$file}:{$line}
Stack trace:
{$trace}
{$previousException}
EE;
        return $exceptionMessage;

    }

    protected function convertToString($data)
    {
        if (is_array($data) && empty($data)) {
            return '';
        }

        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        return print_r($data, true);
    }
}
