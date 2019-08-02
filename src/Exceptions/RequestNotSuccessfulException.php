<?php

namespace Ben182\Letterxpress\Exceptions;

use Exception;

class RequestNotSuccessfulException extends Exception
{
    public $body;
    public $statusCode;

    public function __construct($body, $statusCode)
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        parent::__construct('LetterXpress Request was not successful. Response: ' . $this->body);
    }
}
