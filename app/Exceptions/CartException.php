<?php

namespace App\Exceptions;

use Exception;

class CartException extends Exception
{
    /**
     * Create a new cart exception with a user-friendly message.
     * 
     * @param string $message The user-friendly error message
     * @param int $code Exception code (optional)
     * @param \Throwable|null $previous Previous exception (optional)
     */
    public function __construct(string $message = "Cart operation failed", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}