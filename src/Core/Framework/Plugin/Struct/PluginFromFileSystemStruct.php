<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Plugin\Struct;

use Composer\Package\CompletePackageInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('framework')]
class PluginFromFileSystemStruct extends Struct
{
    protected string $baseClass;

    protected string $path;

    protected bool $managedByComposer;

    protected CompletePackageInterface $composerPackage;

    public function getBaseClass(): string
    {
        return $this->baseClass;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getManagedByComposer(): bool
    {
        return $this->managedByComposer;
    }

    public function getComposerPackage(): CompletePackageInterface
    {
        return $this->composerPackage;
    }

    public function getName(): string
    {
        $baseClass = $this->baseClass;

        $pos = mb_strrpos($baseClass, '\\');

        return $pos === false ? $this->baseClass : mb_substr($this->baseClass, $pos + 1);
    }

    public function getApiAlias(): string
    {
        return 'plugin_from_file_system';
    }
}
