<?php

namespace AppKit\Log\Handler\Stream;

use AppKit\Log\Handler\Stream\Internal\AbstractStreamHandler;

use React\Stream\WritableResourceStream;

class StdoutHandler extends AbstractStreamHandler {
    function __construct(...$args) {
        parent::__construct(
            new WritableResourceStream(STDOUT),
            function_exists('posix_isatty') && posix_isatty(STDOUT),
            ...$args
        );
    }
}
