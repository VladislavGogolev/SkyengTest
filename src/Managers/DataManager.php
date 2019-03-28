<?php

namespace src\Managers;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use src\Integration\DataProviderInterface;

class DataManager
{
    /** @var CacheItemPoolInterface */
    protected $cache;

    /** @var LoggerInterface */
    protected $logger;

    /** @var DataProviderInterface */
    protected $dataProvider;

    /**
     * @param DataProviderInterface
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(DataProviderInterface $dataProvider, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->dataProvider = $dataProvider;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @param array $input
     * @return array|mixed
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->prepareCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
            $result = $this->dataProvider->get($input);
            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );
            return $result;
        } catch (InvalidArgumentException $e) {
            $this->logger->critical('Error: ' . $e->getMessage(), ['code' => $e->getCode()]);
        } catch (Exception $e) {
            $this->logger->critical('Error: ' . $e->getMessage(), ['code' => $e->getCode()]);
        }

        return [];
    }

    /**
     * @param array $input
     * @return string
     */
    public function prepareCacheKey(array $input): string
    {
        return md5(json_encode($input));
    }
}