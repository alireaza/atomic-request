<?php

namespace AliReaza\Atomic\Containers;

use AliReaza\ErrorHandler\ErrorHandler;

class ErrorHandlerContainer
{
    public function __invoke(): ErrorHandler
    {
        $error_handler = new ErrorHandler();
        $error_handler->register(true, false);

        return $error_handler;
    }
}
