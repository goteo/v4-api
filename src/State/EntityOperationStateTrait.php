<?php

namespace App\State;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Operation;

trait EntityOperationStateTrait
{
    /**
     * Extract the FQCN for the Entity behind a given Operation.
     *
     * @return string A FQCN
     */
    public function getEntityClass(Operation $operation): string
    {
        $entityClass = $operation->getClass();

        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $entityClass = $options->getEntityClass();
        }

        return $entityClass;
    }
}
