<?php

namespace AppKit\Log\Handler\Stream;

class StreamHandler extends AbstractStreamHandler {
    function __construct($stream, ...$args) {
        parent::__construct(
            $stream,
            false,
            ...$args
        );
    }
}
