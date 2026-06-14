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

class LicenseInfo
{
    /** @var bool */
    public $supported;
    /** @var bool */
    public $isActive;
    /** @var string|null */
    public $serial;
    /** @var string */
    public $moduleId;
    /** @var string */
    public $moduleName;
    /** @var string */
    public $activateUrl;
    /** @var string */
    public $deactivateUrl;
    /** @var string */
    public $state;
    /** @var string|null */
    public $validUntil;
    /** @var string */
    public $statusUrl;
    /** @var string|null */
    public $tier;
    /** @var string|null */
    public $licenseCode;
    /** @var string */
    public $magentoEdition;

    public function __construct(
        bool $supported,
        bool $isActive,
        ?string $serial,
        string $moduleId,
        string $moduleName,
        string $activateUrl,
        string $deactivateUrl,
        string $state,
        ?string $validUntil,
        string $statusUrl,
        ?string $tier = null,
        ?string $licenseCode = null,
        string $magentoEdition = ''
    ) {
        $this->supported      = $supported;
        $this->isActive       = $isActive;
        $this->serial         = $serial;
        $this->moduleId       = $moduleId;
        $this->moduleName     = $moduleName;
        $this->activateUrl    = $activateUrl;
        $this->deactivateUrl  = $deactivateUrl;
        $this->state          = $state;
        $this->validUntil     = $validUntil;
        $this->statusUrl      = $statusUrl;
        $this->tier           = $tier;
        $this->licenseCode    = $licenseCode;
        $this->magentoEdition = $magentoEdition;
    }
}
