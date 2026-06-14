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

namespace MageMe\Core\Model\ModuleEcosystem;

/**
 * Resolved license-meta for a suite. Built by LicenseMetaResolver from modules.json catalog.
 */
class LicenseMeta
{
    /** @var string */
    public $ownerModuleName;
    /** @var string */
    public $licenseSection;
    /** @var string|null */
    public $licenseCode;
    /** @var string|null */
    public $tier;

    public function __construct(
        string $ownerModuleName,
        string $licenseSection,
        ?string $licenseCode,
        ?string $tier
    ) {
        $this->ownerModuleName = $ownerModuleName;
        $this->licenseSection  = $licenseSection;
        $this->licenseCode     = $licenseCode;
        $this->tier            = $tier;
    }
}
