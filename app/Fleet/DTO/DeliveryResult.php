<?php

namespace App\Fleet\DTO;

use App\Fleet\Model\Deployment;
use App\Trust\Statement\Message;
use Illuminate\Http\Client\Response;

final class DeliveryResult
{
    public function __construct(
        public readonly Deployment $to,
        public readonly Message $message,
        public readonly Response $response,
    ) {}
}
