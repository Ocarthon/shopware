<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Cache\InvalidatorStorage\AbstractInvalidatorStorage;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\PlatformRequest;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @final
 */
#[Package('framework')]
class CacheInvalidator
{
    /**
     * @internal
     *
     * @param CacheItemPoolInterface[] $adapters
     */
    public function __construct(
        private readonly array $adapters,
        private readonly AbstractInvalidatorStorage $cache,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly string $environment
    ) {
    }

    /**
     * @param array<string> $tags
     */
    public function invalidate(array $tags, bool $force = false): void
    {
        $tags = array_filter(array_unique($tags));

        if (empty($tags)) {
            return;
        }

        if ($force || $this->shouldForceInvalidate()) {
            $this->purge($tags);

            return;
        }

        $this->cache->store($tags);
    }

    /**
     * @return array<string>
     */
    public function invalidateExpired(): array
    {
        $tags = $this->cache->loadAndDelete();

        if (empty($tags)) {
            return $tags;
        }

        $this->logger->info(\sprintf('Purged %d tags', \count($tags)));

        $this->purge($tags);

        return $tags;
    }

    /**
     * @param array<string> $keys
     */
    private function purge(array $keys): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->deleteItems($keys);

            if ($adapter instanceof TagAwareAdapterInterface) {
                $adapter->invalidateTags($keys);
            }
        }

        $this->dispatcher->dispatch(new InvalidateCacheEvent($keys));
    }

    private function shouldForceInvalidate(): bool
    {
        return $this->environment === 'test' // immediately invalidate in test environment, to make tests deterministic
            || $this->requestStack->getMainRequest()?->headers->get(PlatformRequest::HEADER_FORCE_CACHE_INVALIDATE) === '1';
    }
}
