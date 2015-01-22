<?php
namespace Vda\Log\Formatter;

use Monolog\Formatter\FormatterInterface;

class HtmlDebugFormatter implements FormatterInterface
{
    public function format(array $record)
    {
        $output = "<pre>{$record['extra']['file']}:{$record['extra']['line']} {$record['message']}\n";

        if (!empty($record['context'])) {
            $output .= var_export($record['context'], true);
        }
//        $output .= var_export($record['extra'], true);
        $output .= '</pre>';

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
}


