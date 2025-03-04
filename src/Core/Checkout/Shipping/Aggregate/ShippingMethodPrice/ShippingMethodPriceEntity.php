<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice;

use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Contract\IdAware;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class ShippingMethodPriceEntity extends Entity implements IdAware
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    protected string $shippingMethodId;

    protected ?string $ruleId = null;

    protected ?int $calculation = null;

    protected ?float $quantityStart = null;

    protected ?float $quantityEnd = null;

    protected ?ShippingMethodEntity $shippingMethod = null;

    protected ?RuleEntity $rule = null;

    protected ?string $calculationRuleId = null;

    protected ?RuleEntity $calculationRule = null;

    protected ?PriceCollection $currencyPrice = null;

    public function getShippingMethodId(): string
    {
        return $this->shippingMethodId;
    }

    public function setShippingMethodId(string $shippingMethodId): void
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    public function getQuantityStart(): ?float
    {
        return $this->quantityStart;
    }

    public function setQuantityStart(float $quantityStart): void
    {
        $this->quantityStart = $quantityStart;
    }

    public function getQuantityEnd(): ?float
    {
        return $this->quantityEnd;
    }

    public function setQuantityEnd(float $quantityEnd): void
    {
        $this->quantityEnd = $quantityEnd;
    }

    public function getCalculation(): ?int
    {
        return $this->calculation;
    }

    public function setCalculation(int $calculation): void
    {
        $this->calculation = $calculation;
    }

    public function getShippingMethod(): ?ShippingMethodEntity
    {
        return $this->shippingMethod;
    }

    public function setShippingMethod(ShippingMethodEntity $shippingMethod): void
    {
        $this->shippingMethod = $shippingMethod;
    }

    public function getRuleId(): ?string
    {
        return $this->ruleId;
    }

    public function setRuleId(string $ruleId): void
    {
        $this->ruleId = $ruleId;
    }

    public function getRule(): ?RuleEntity
    {
        return $this->rule;
    }

    public function setRule(?RuleEntity $rule): void
    {
        $this->rule = $rule;
    }

    public function getCalculationRuleId(): ?string
    {
        return $this->calculationRuleId;
    }

    public function setCalculationRuleId(?string $calculationRuleId): void
    {
        $this->calculationRuleId = $calculationRuleId;
    }

    public function getCalculationRule(): ?RuleEntity
    {
        return $this->calculationRule;
    }

    public function setCalculationRule(?RuleEntity $calculationRule): void
    {
        $this->calculationRule = $calculationRule;
    }

    public function getCurrencyPrice(): ?PriceCollection
    {
        return $this->currencyPrice;
    }

    public function setCurrencyPrice(?PriceCollection $price): void
    {
        $this->currencyPrice = $price;
    }
}
