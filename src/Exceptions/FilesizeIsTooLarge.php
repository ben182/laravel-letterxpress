<?php

namespace Ben182\Letterxpress\Exceptions;

use Exception;

class FilesizeIsTooLarge extends Exception
{
    public $file;

    public function __construct($file)
    {
        $this->file = $file;
        parent::__construct('File is too large');
    }
}
