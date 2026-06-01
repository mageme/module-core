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
 * Copyright (c) MageMe (https://mageme.com)
 **/

declare(strict_types=1);

namespace MageMe\Core\Model\License;

use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\Serialize\SerializerInterface;

class StatusCache
{
    public const TTL = 300;
    public const TAG = 'MAGEME_LICENSE_STATUS';

    /** @var ConfigCache */
    private $cache;
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(ConfigCache $cache, SerializerInterface $serializer)
    {
        $this->cache      = $cache;
        $this->serializer = $serializer;
    }

    /** @return LicenseStatus|null */
    public function get(string $module)
    {
        $raw = $this->cache->load($this->key($module));
        if ($raw === false || $raw === null || $raw === '') {
            return null;
        }
        $data = $this->serializer->unserialize($raw);
        if (!is_array($data) || !isset($data['state'])) {
            return null;
        }
        return new LicenseStatus(
            (string)$data['state'],
            (bool)(isset($data['isActive']) ? $data['isActive'] : false),
            (bool)(isset($data['isDev']) ? $data['isDev'] : false),
            isset($data['validUntil']) ? $data['validUntil'] : null,
            (bool)(isset($data['stale']) ? $data['stale'] : false)
        );
    }

    public function set(string $module, LicenseStatus $status)
    {
        $this->cache->save(
            $this->serializer->serialize($status->toArray()),
            $this->key($module),
            [self::TAG, $this->moduleTag($module)],
            self::TTL
        );
    }

    public function invalidate(string $module)
    {
        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, [$this->moduleTag($module)]);
    }

    private function key(string $module): string
    {
        return 'mageme_license_status_' . strtolower(str_replace('\\', '_', $module));
    }

    private function moduleTag(string $module): string
    {
        return self::TAG . '_' . strtoupper(str_replace('\\', '_', $module));
    }
}
