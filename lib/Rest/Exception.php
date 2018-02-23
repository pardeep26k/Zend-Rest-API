<?php

namespace Rest;

use Exception as ExceptionParent;

class Exception extends ExceptionParent
{
    public function __construct($code, $message = null)
    {
        parent::__construct($message, $code);
    }
}
