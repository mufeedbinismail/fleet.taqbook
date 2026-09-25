<?php

namespace App\Fleet\Action;

use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Model\Deployment;
use App\Foundation\Framework\Exception\ValidationException;
use App\Trust\Exception\TrustException;
use App\Trust\Statement\Ping;

class PingDeploymentAction extends SendMessageAction
{
    /**
     * @throws ValidationException if validate() refuses it
     * @throws TrustException if the server cannot sign
     */
    public function execute(Deployment $deployment): DeliveryOutcome
    {
        return $this->deliver($deployment, new Ping);
    }
}
