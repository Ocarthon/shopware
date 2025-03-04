<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Store\Struct;

use Shopware\Core\Framework\Log\Package;

/**
 * @codeCoverageIgnore
 */
#[Package('checkout')]
class VariantStruct extends StoreStruct
{
    final public const TYPE_RENT = 'rent';
    final public const TYPE_BUY = 'buy';
    final public const TYPE_FREE = 'free';

    protected int $id;

    protected string $type;

    protected float $netPrice;

    protected bool $trialPhaseIncluded = false;

    protected ?DiscountCampaignStruct $discountCampaign;

    /**
     * @return VariantStruct
     */
    public static function fromArray(array $data): StoreStruct
    {
        return (new self())->assign($data);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getNetPrice(): float
    {
        return $this->netPrice;
    }

    public function isTrialPhaseIncluded(): bool
    {
        return $this->trialPhaseIncluded;
    }

    public function setTrialPhaseIncluded(bool $trialPhaseIncluded): void
    {
        $this->trialPhaseIncluded = $trialPhaseIncluded;
    }

    public function getDiscountCampaign(): ?DiscountCampaignStruct
    {
        return $this->discountCampaign;
    }

    public function setDiscountCampaign(?DiscountCampaignStruct $discountCampaign): void
    {
        $this->discountCampaign = $discountCampaign;
    }
}
