<?php declare(strict_types=1);

namespace Shopware\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('framework')]
class Migration1717601705AddIntraCommunityLabelDocumentConfigToStorno extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1717601705;
    }

    public function update(Connection $connection): void
    {
        $connection->transactional(function (Connection $transaction): void {
            $stornoConfig = $transaction->executeQuery(
                <<<SQL
                    SELECT `document_base_config`.`id`, `document_base_config`.`config` FROM `document_base_config`
                    JOIN `document_type` ON `document_base_config`.`document_type_id` = `document_type`.`id`
                    WHERE `document_type`.`technical_name` = :technicalName;
                    SQL,
                ['technicalName' => 'storno'],
            )->fetchAssociative();

            if ($stornoConfig === false) {
                return;
            }

            $config = json_decode($stornoConfig['config'], true, 512, \JSON_THROW_ON_ERROR);

            if (!isset($config['displayAdditionalNoteDelivery'])) {
                $config['displayAdditionalNoteDelivery'] = false;
            }

            $transaction->executeQuery(
                'UPDATE `document_base_config` SET `config` = :config WHERE `id` = :id;',
                [
                    'id' => $stornoConfig['id'],
                    'config' => json_encode($config, \JSON_THROW_ON_ERROR),
                ],
            );
        });
    }
}
