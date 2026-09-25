<?php

namespace App\Fleet\Action;

use App\Fleet\Enum\DeliveryOutcome;
use App\Fleet\Exception\DeploymentException;
use App\Fleet\Model\Deployment;
use App\Trust\Statement\Ping;

class PingDeploymentAction extends SendMessageAction
{
    /**
     * @throws DeploymentException if validate() would have refused it
     */
    public function execute(Deployment $deployment): DeliveryOutcome
    {
        return $this->deliver($deployment, new Ping);
    }
}
