<?php

namespace AppKit\Log\Handler\Stream\Internal;

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
                  '[' . $this -> formatContextData($executionContext) . '] ';
        }

        // Module
        $module = end($modulePath);
        $line .= $this -> onlyTty(self::COLOR_MODULE) .
            '[' . $this -> shortClassName($module['module']);

        if($module['instance'] !== null)
            $line .= ':' . $module['instance'];

        $line .= '] ';

        // Message
        foreach($localContext as $key => $value) {
            $replacedCount = 0;
            $message = str_replace(
                '{' . $key . '}',
                $this -> formatContextData($value),
                $message,
                $replacedCount
            );
            if($replacedCount > 0)
                unset($localContext[$key]);
        }

        $line .= $this -> onlyTty(self::COLOR_RESET) . $message;

        // Local context
        if(!empty($localContext))
             $line .= ', ' . $this -> formatContextData($localContext);

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

    private function formatContextData($data, $decorators = false) {
        if(is_string($data)) {
            if($decorators)
                return '"' . $data . '"';
            return $data;
        }

        if(is_bool($data))
            return $data ? 'true' : 'false';

        if(is_scalar($data))
            return (string) $data;

        if(is_null($data))
            return 'null';

        if(is_array($data)) {
            $assoc = ! array_is_list($data);
            $resultArray = [];
            foreach($data as $k => $v) {
                $v = $this -> formatContextData($v, true);
                $resultArray[] = $assoc ? "$k: $v" : $v;
            }

            $resultString = implode(', ', $resultArray);

            if($decorators)
                return '[' . $resultString . ']';
            return $resultString;
        }

        return gettype($data);
    }
}
