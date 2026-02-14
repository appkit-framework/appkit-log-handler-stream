<?php

namespace AppKit\Log\Handler\Stream;

use AppKit\Log\Handler\Stream\Internal\AbstractStreamHandler;

class StreamHandler extends AbstractStreamHandler {
    function __construct($stream, ...$args) {
        parent::__construct(
            $stream,
            false,
            ...$args
        );
    }
}
