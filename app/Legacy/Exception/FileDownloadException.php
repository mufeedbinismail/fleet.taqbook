<?php

namespace App\Legacy\Exception;

use App\Legacy\Exception\FlowControlException as Exception;

class FileDownloadException extends Exception
{
    protected $filePath;
    protected $fileName;

    public function __construct(string $filePath, string $fileName = null, int $code = 0, \Throwable $previous = null)
    {
        // Guess a default filename if one isn't provided
        $this->filePath = $filePath;
        $this->fileName = $fileName ?? basename($filePath);
        
        // Use a generic message for the exception
        parent::__construct("Initiating file download for: " . $this->fileName, $code, $previous);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
}