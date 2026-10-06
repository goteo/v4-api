<?php

namespace App\Dto\Gateway;

use App\ApiResource\Accounting\AccountingApiResource;
use App\ApiResource\MoneyInput;
use App\Gateway\ChargeType;
use Symfony\Component\Validator\Constraints as Assert;

class ChargeCreationDto
{
    /**
     * How this item should be processed by the Gateway.\
     * \
     * `single` is for one time payments.\
     * `recurring` is for payments repeated over time.
     */
    #[Assert\NotBlank()]
    public ChargeType $type = ChargeType::Single;

    /**
     * A short, descriptive string for this charge item.\
     * May be displayed to the payer.
     */
    #[Assert\NotBlank()]
    public string $title;

    /**
     * Detailed information about the charge item.\
     * May be displayed to the payer.
     */
    public ?string $description = null;

    /**
     * The Accounting receiving the money after a successful payment.
     */
    #[Assert\NotBlank()]
    public AccountingApiResource $target;

    /**
     * The money to-be-paid for this item at the Gateway.
     *
     * It is money before fees and taxes, not accountable.
     */
    #[Assert\NotBlank()]
    public MoneyInput $money;
}
