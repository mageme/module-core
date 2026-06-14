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

class LicenseStatus
{
    public const STATE_ACTIVE   = 'active';
    public const STATE_DEV      = 'dev';
    public const STATE_EXPIRED  = 'expired';
    public const STATE_INACTIVE = 'inactive';
    public const STATE_UNKNOWN  = 'unknown';

    /** @var string */
    public $state;
    /** @var bool */
    public $isActive;
    /** @var bool */
    public $isDev;
    /** @var string|null */
    public $validUntil;
    /** @var bool */
    public $stale;

    public function __construct(
        string $state,
        bool $isActive,
        bool $isDev,
        ?string $validUntil,
        bool $stale = false
    ) {
        $this->state      = $state;
        $this->isActive   = $isActive;
        $this->isDev      = $isDev;
        $this->validUntil = $validUntil;
        $this->stale      = $stale;
    }

    public function toArray(): array
    {
        return [
            'state'      => $this->state,
            'isActive'   => $this->isActive,
            'isDev'      => $this->isDev,
            'validUntil' => $this->validUntil,
            'stale'      => $this->stale,
        ];
    }
}
