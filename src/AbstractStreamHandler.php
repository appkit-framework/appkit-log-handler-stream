<?php

namespace AppKit\Log\Handler\Stream;

use AppKit\Log\Handler\LogHandlerInterface;
use AppKit\Log\LogLevel;

abstract class AbstractStreamHandler implements LogHandlerInterface {
    const COLOR_RESET = "\033[0m";
    const COLOR_CONTEXT = "\033[35m";
    const COLOR_MODULE = "\033[34m";
    const LEVEL_COLOR_MAP = [
        LogLevel::Error -> value   => "\033[31m",
        LogLevel::Warning -> value => "\033[33m",
        LogLevel::Info -> value    => "\033[32m",
        LogLevel::Debug -> value   => "\033[90m"
    ];
    const LEVEL_TEXT_MAP = [
        LogLevel::Error -> value   => 'ERROR',
        LogLevel::Warning -> value => 'WARN ',
        LogLevel::Info -> value    => 'INFO ',
        LogLevel::Debug -> value   => 'DEBUG'
    ];

    private $stream;
    private $isTty;
    private $printStackTraces;

    function __construct($stream, $isTty, $printStackTraces) {
        $this -> stream = $stream;
        $this -> isTty = $isTty;
        $this -> printStackTraces = $printStackTraces;
    }
    
    public function log(
        $time,
        $level,
        $executionContext,
        $modulePath,
        $message,
        $localContext,
        $exception
    ) {
        // Hide user input
        $line = $this -> onlyTty("\r");

        // Time
        $line .= date('Y-m-d H:i:s  ', $time);

        // Level
        $line .= $this -> onlyTty(self::LEVEL_COLOR_MAP[$level -> value]) .
            self::LEVEL_TEXT_MAP[$level -> value] . '  ';

        // Execution context
        if(!empty($executionContext)) {
             $line .= $this -> onlyTty(self::COLOR_CONTEXT) .
                  '[' . $this -> formatArray($executionContext) . '] ';
        }

        // Module
        $module = end($modulePath);
        $line .= $this -> onlyTty(self::COLOR_MODULE) .
            '[' . $this -> shortClassName($module['module']);

        if($module['instance'] !== null)
            $line .= ':' . $module['instance'];

        $line .= '] ';

        // Message
        $line .= $this -> onlyTty(self::COLOR_RESET) . $message;

        // Local context
        if(!empty($localContext))
             $line .= ', ' . $this -> formatArray($localContext);

        // Exception
        if($exception) {
            $line .= ': ' . $this -> shortClassName(get_class($exception));

            $exceptionCode = $exception -> getCode();
            if($exceptionCode != 0)
                $line .= '[' . $exceptionCode . ']';

            $line .= ': ' . $exception -> getMessage();

            if($this -> printStackTraces)
                $line .= PHP_EOL . PHP_EOL . ((string) $exception) . PHP_EOL;
        }

        // EOL
        $line .= PHP_EOL;

        // Write
        $this -> stream -> write($line);
    }

    private function shortClassName($fqcn) {
        $pos = strrpos($fqcn, '\\');
        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }

    private function onlyTty($string) {
        if($this -> isTty)
            return $string;
        return '';
    }

    private function formatArray($array) {
        $assoc = ! array_is_list($array);
        $formatted = [];

        foreach($array as $k => $v) {
            if(is_string($v))
                $v = '"' . $v . '"';
            else if(is_bool($v))
                $v = $v ? 'true' : 'false';
            else if(is_scalar($v))
                $v = (string) $v;
            else if(is_null($v))
                $v = 'null';
            else if(is_array($v))
                $v = '[' . $this -> formatArray($v) . ']';
            else
                $v = gettype($v);

            $formatted[] = $assoc ? "$k: $v" : $v;
        }

        return implode(', ', $formatted);
    }
}
