<?php

namespace App\State\Gateway;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Gateway\CheckoutApiResource;
use App\Dto\Gateway\CheckoutCreationDto;
use App\Dto\Gateway\CheckoutUpdationDto;
use App\Entity\EmbeddableMoney;
use App\Entity\Gateway\Checkout;
use App\Gateway\GatewayLocator;
use App\Mapping\AutoMapper;
use App\Money\Conversion\ExchangeLocator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CheckoutStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $innerProcessor,
        private AutoMapper $autoMapper,
        private GatewayLocator $gatewayLocator,
        private ExchangeLocator $exchangeLocator,
    ) {}

    /**
     * @param CheckoutCreationDto|CheckoutUpdationDto $data
     *
     * @return Checkout
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof CheckoutUpdationDto) {
            $data = $this->autoMapper->map($uriVariables, $data);
        }

        $entity = $this->autoMapper->map($data, Checkout::class);

        /** @var Checkout */
        $checkout = $this->innerProcessor->process($entity, $operation, $uriVariables, $context);

        if ($data instanceof CheckoutUpdationDto) {
            return $this->autoMapper->map($checkout, CheckoutApiResource::class);
        }

        foreach ($checkout->getCharges() as $charge) {
            $fromCurrency = $charge->getMoney()->getCurrency();
            $toCurrency = $charge->getTarget()->getCurrency();

            if ($fromCurrency === $toCurrency) {
                continue;
            }

            $exchange = $this->exchangeLocator->get($fromCurrency, $toCurrency);

            $charge->setMoney(EmbeddableMoney::of(
                $exchange->convert($charge->getMoney(), $toCurrency)
            ));
        }

        $checkout = $this->gatewayLocator->get($data->gateway->id)->process($checkout);
        $checkout = $this->innerProcessor->process($checkout, $operation, $uriVariables, $context);

        return $this->autoMapper->map($checkout, CheckoutApiResource::class);
    }
}
