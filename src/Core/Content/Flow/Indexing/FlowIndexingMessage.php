<?php declare(strict_types=1);

namespace Shopware\Core\Content\Flow\Indexing;

use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\Log\Package;

#[Package('after-sales')]
class FlowIndexingMessage extends EntityIndexingMessage
{
}
