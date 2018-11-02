<?php declare(strict_types=1);

namespace Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextWithHtmlField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TenantIdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Flag\Required;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelTypeTranslation\SalesChannelTypeTranslationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class SalesChannelTypeDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'sales_channel_type';
    }

    public static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            new TenantIdField(),
            new StringField('cover_url', 'coverUrl'),
            new StringField('icon_name', 'iconName'),
            new ListField('screenshot_urls', 'screenshotUrls', StringField::class),
            new TranslatedField(new StringField('name', 'name')),
            new TranslatedField(new StringField('manufacturer', 'manufacturer')),
            new TranslatedField(new StringField('description', 'description')),
            new TranslatedField(new LongTextWithHtmlField('description_long', 'descriptionLong')),
            new CreatedAtField(),
            new UpdatedAtField(),
            (new TranslationsAssociationField(SalesChannelTypeTranslationDefinition::class))->setFlags(new Required(), new CascadeDelete()),

            new OneToManyAssociationField('salesChannels', SalesChannelDefinition::class, 'type_id', false, 'id'),
        ]);
    }

    public static function getCollectionClass(): string
    {
        return SalesChannelTypeCollection::class;
    }

    public static function getStructClass(): string
    {
        return SalesChannelTypeStruct::class;
    }

    public static function getTranslationDefinitionClass(): ?string
    {
        return SalesChannelTypeTranslationDefinition::class;
    }
}
