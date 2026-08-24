<?php
/**
 * MageMe
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageMe.com license that is
 * available through the world-wide-web at this URL:
 * https://mageme.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to a newer
 * version in the future.
 *
 * Copyright (c) MageMe (https://mageme.com)
 **/

declare(strict_types=1);

namespace MageMe\Core\Model\ModuleEcosystem;

use Exception;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

/**
 * info.mageme.com/modules.json cache.
 * getAll() is read-only (memo + Magento cache, never network); refresh() is the only
 * network entry point and is called from the async admin controller, never from the
 * PHP render path.
 */
class RemoteCatalog
{
    public const API_URL         = 'https://info.mageme.com/modules.json';
    public const CACHE_KEY       = 'MAGEME_modules_catalog';
    public const CACHE_GROUP     = CacheTypeConfig::TYPE_IDENTIFIER;
    public const CACHE_TAG       = 'extensions';
    public const FETCH_TIMEOUT_S = 5;
    /** Vendor_Module — the only key shape a real catalog has. */
    private const MODULE_NAME_PATTERN = '/^[A-Za-z][A-Za-z0-9]*_[A-Za-z][A-Za-z0-9]*$/';

    /** @var CacheInterface */
    private $cache;
    /** @var Curl */
    private $curl;
    /** @var LoggerInterface */
    private $logger;
    /** @var array<string, mixed>|null */
    private $memo = null;

    public function __construct(CacheInterface $cache, Curl $curl, LoggerInterface $logger)
    {
        $this->cache  = $cache;
        $this->curl   = $curl;
        $this->logger = $logger;
    }

    /** @return array<string, array<string, mixed>> */
    public function getAll(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached) {
            $decoded = json_decode($cached, true);
            if (is_array($decoded)) {
                return $this->memo = $decoded;
            }
        }

        return $this->memo = [];
    }

    /** @return array<string, mixed> */
    public function get(string $moduleName): array
    {
        $all = $this->getAll();
        return isset($all[$moduleName]) ? $all[$moduleName] : [];
    }

    /**
     * Stale cache preserved on failure.
     *
     * The cache has no lifetime, so whatever lands here stays until the next successful refresh —
     * which is why an error page that happens to be valid JSON must never get that far. A non-2xx
     * status and a payload that is not a map of module entries are both refused.
     */
    public function refresh(): bool
    {
        try {
            $this->curl->setOption(CURLOPT_TIMEOUT, self::FETCH_TIMEOUT_S);
            $this->curl->get(self::API_URL);
            $status = (int)$this->curl->getStatus();
            if ($status < 200 || $status > 299) {
                $this->logger->warning('MageMe RemoteCatalog refresh: HTTP ' . $status);
                return false;
            }
            $decoded = json_decode((string)$this->curl->getBody(), true);
            if (!$this->isCatalog($decoded)) {
                $this->logger->warning('MageMe RemoteCatalog refresh: response is not a module catalog');
                return false;
            }
            $this->cache->save(json_encode($decoded), self::CACHE_KEY, [self::CACHE_TAG]);
            $this->memo = $decoded;
            return true;
        } catch (Exception $e) {
            $this->logger->info('MageMe RemoteCatalog refresh failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * A catalog is a non-empty map of Vendor_Module => non-empty entry. Anything else — a list, a
     * scalar, an empty object, an error document such as {"error": {"message": "..."}} — is refused
     * rather than cached over good data. Entry contents are deliberately NOT schema-checked: the
     * catalog gains fields over time, and rejecting the whole payload over an unknown key would
     * break every panel the day a new field ships.
     *
     * @param mixed $decoded
     */
    private function isCatalog($decoded): bool
    {
        if (!is_array($decoded) || $decoded === []) {
            return false;
        }
        foreach ($decoded as $moduleName => $entry) {
            if (!is_string($moduleName) || preg_match(self::MODULE_NAME_PATTERN, $moduleName) !== 1) {
                return false;
            }
            if (!is_array($entry) || $entry === []) {
                return false;
            }
        }

        return true;
    }
}
