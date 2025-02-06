<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Shipping;

use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceCollection;
use Shopware\Core\Checkout\Shipping\Aggregate\ShippingMethodTranslation\ShippingMethodTranslationCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\App\Aggregate\AppShippingMethod\AppShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Contract\IdAware;
use Shopware\Core\Framework\DataAbstractionLayer\Contract\RuleIdAware;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\Tag\TagCollection;
use Shopware\Core\System\Tax\TaxEntity;

#[Package('checkout')]
class ShippingMethodEntity extends Entity implements IdAware, RuleIdAware
{
    use EntityCustomFieldsTrait;
    use EntityIdTrait;

    final public const TAX_TYPE_AUTO = 'auto';
    final public const TAX_TYPE_FIXED = 'fixed';
    final public const TAX_TYPE_HIGHEST = 'highest';
    final public const POSITION_DEFAULT = 1;
    final public const ACTIVE_DEFAULT = false;

    protected ?string $name = null;

    protected ?bool $active = null;

    protected ?int $position = null;

    protected ?string $description = null;

    protected ?string $trackingUrl = null;

    protected string $deliveryTimeId;

    protected ?DeliveryTimeEntity $deliveryTime = null;

    protected ?ShippingMethodTranslationCollection $translations = null;

    protected ?OrderDeliveryCollection $orderDeliveries = null;

    protected ?SalesChannelCollection $salesChannelDefaultAssignments = null;

    protected ?SalesChannelCollection $salesChannels = null;

    protected ?RuleEntity $availabilityRule = null;

    protected ?string $availabilityRuleId = null;

    protected ShippingMethodPriceCollection $prices;

    protected ?string $mediaId = null;

    protected ?string $taxId = null;

    protected ?MediaEntity $media = null;

    protected ?TagCollection $tags = null;

    protected string $taxType;

    protected string $technicalName;

    protected ?TaxEntity $tax = null;

    protected ?AppShippingMethodEntity $appShippingMethod = null;

    public function __construct()
    {
        $this->prices = new ShippingMethodPriceCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): void
    {
        $this->trackingUrl = $trackingUrl;
    }

    public function getDeliveryTimeId(): string
    {
        return $this->deliveryTimeId;
    }

    public function setDeliveryTimeId(string $deliveryTimeId): void
    {
        $this->deliveryTimeId = $deliveryTimeId;
    }

    public function getDeliveryTime(): ?DeliveryTimeEntity
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(DeliveryTimeEntity $deliveryTime): void
    {
        $this->deliveryTime = $deliveryTime;
    }

    public function getTranslations(): ?ShippingMethodTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(ShippingMethodTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getOrderDeliveries(): ?OrderDeliveryCollection
    {
        return $this->orderDeliveries;
    }

    public function setOrderDeliveries(OrderDeliveryCollection $orderDeliveries): void
    {
        $this->orderDeliveries = $orderDeliveries;
    }

    public function getSalesChannelDefaultAssignments(): ?SalesChannelCollection
    {
        return $this->salesChannelDefaultAssignments;
    }

    public function setSalesChannelDefaultAssignments(SalesChannelCollection $salesChannelDefaultAssignments): void
    {
        $this->salesChannelDefaultAssignments = $salesChannelDefaultAssignments;
    }

    public function getSalesChannels(): ?SalesChannelCollection
    {
        return $this->salesChannels;
    }

    public function setSalesChannels(SalesChannelCollection $salesChannels): void
    {
        $this->salesChannels = $salesChannels;
    }

    public function getPrices(): ShippingMethodPriceCollection
    {
        return $this->prices;
    }

    public function setPrices(ShippingMethodPriceCollection $prices): void
    {
        $this->prices = $prices;
    }

    public function getAvailabilityRule(): ?RuleEntity
    {
        return $this->availabilityRule;
    }

    public function setAvailabilityRule(?RuleEntity $availabilityRule): void
    {
        $this->availabilityRule = $availabilityRule;
    }

    public function getAvailabilityRuleId(): ?string
    {
        return $this->availabilityRuleId;
    }

    public function setAvailabilityRuleId(?string $availabilityRuleId): void
    {
        $this->availabilityRuleId = $availabilityRuleId;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTaxId(?string $taxId): void
    {
        $this->taxId = $taxId;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(MediaEntity $media): void
    {
        $this->media = $media;
    }

    public function getTags(): ?TagCollection
    {
        return $this->tags;
    }

    public function setTags(TagCollection $tags): void
    {
        $this->tags = $tags;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }

    public function setTaxType(string $taxType): void
    {
        $this->taxType = $taxType;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getTax(): ?TaxEntity
    {
        return $this->tax;
    }

    public function setTax(TaxEntity $tax): void
    {
        $this->tax = $tax;
    }

    public function getAppShippingMethod(): ?AppShippingMethodEntity
    {
        return $this->appShippingMethod;
    }

    public function setAppShippingMethod(?AppShippingMethodEntity $appShippingMethod): void
    {
        $this->appShippingMethod = $appShippingMethod;
    }
}
