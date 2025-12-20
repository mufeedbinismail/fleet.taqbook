<?php

namespace App\Legacy\Exception;

use App\Legacy\Exception\FlowControlException as Exception;

class FlowRedirectionException extends Exception
{
    protected $targetUrl;
    protected $httpCode;
    
    // Pass the target URL (where to redirect) to the constructor
    public function __construct(string $targetUrl, int $httpCode = 302, string $message = "Legacy flow initiated HTTP redirect.", int $code = 0, \Throwable $previous = null)
    {
        $this->targetUrl = $targetUrl;
        $this->httpCode = $httpCode;
        parent::__construct($message, $code, $previous);
    }

    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}