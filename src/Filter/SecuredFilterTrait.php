<?php

namespace App\Filter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Contracts\Service\Attribute\Required;

trait SecuredFilterTrait
{
    protected Security $securityBundle;

    protected ExpressionLanguage $expressionLanguage;

    #[Required]
    public function setSecurityBundle(Security $securityBundle)
    {
        $this->securityBundle = $securityBundle;
    }

    #[Required]
    public function setExpressionLanguage(
        #[Autowire(service: 'security.expression_language')]
        ExpressionLanguage $expressionLanguage,
    ) {
        $this->expressionLanguage = $expressionLanguage;
    }

    protected function isFilteringGranted(?string $securityExpression): bool
    {
        if ($securityExpression === null) {
            return true;
        }

        return $this->expressionLanguage->evaluate(
            $securityExpression,
            [
                'user' => $this->securityBundle->getUser(),
                'auth_checker' => $this->securityBundle,
            ]
        );
    }
}
