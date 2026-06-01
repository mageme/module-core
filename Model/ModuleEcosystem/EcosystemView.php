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

final class EcosystemView
{
    /** @var EcosystemRow */
    public $core;
    /** @var EcosystemRow[] */
    public $addons;
    /** @var int */
    public $updateCount;
    /** @var bool */
    public $expandedByDefault;
    /** @var string|null */
    public $docsUrl;
    /** @var string|null */
    public $supportUrl;
    /** @var string */
    public $productName;
    /** @var LicenseInfo|null */
    public $license;
    /**
     * True only for "mixed" suites — free core + at least one pro addon (e.g. EU Withdrawal Pro).
     * Drives row-level Pro/Free pill visibility in the phtml: uniform pro suites (WebForms,
     * HidePricePro, EasyQuote) and uniform free suites suppress the noise.
     * @var bool
     */
    public $showTierPills;
    /** @var string|null Composed renew URL, or null when not applicable. */
    public $renewUrl;
    /** @var string|null Composed Get-Pro URL, or null when not applicable. */
    public $getProUrl;
    /** @var string|null Raw purchase URL (ungated), for the "no licence yet" hint. */
    public $purchaseUrl;
    /** @var string|null One-click buy URL (deep-links to store cart), preferred over purchaseUrl when present. */
    public $buyUrl;

    /** @param EcosystemRow[] $addons */
    public function __construct(
        EcosystemRow $core,
        array $addons,
        int $updateCount,
        bool $expandedByDefault,
        ?string $docsUrl,
        ?string $supportUrl,
        string $productName = '',
        ?LicenseInfo $license = null,
        bool $showTierPills = false,
        ?string $renewUrl = null,
        ?string $getProUrl = null,
        ?string $purchaseUrl = null,
        ?string $buyUrl = null
    ) {
        $this->core              = $core;
        $this->addons            = $addons;
        $this->updateCount       = $updateCount;
        $this->expandedByDefault = $expandedByDefault;
        $this->docsUrl           = $docsUrl;
        $this->supportUrl        = $supportUrl;
        $this->productName       = $productName;
        $this->license           = $license;
        $this->showTierPills     = $showTierPills;
        $this->renewUrl          = $renewUrl;
        $this->getProUrl         = $getProUrl;
        $this->purchaseUrl       = $purchaseUrl;
        $this->buyUrl            = $buyUrl;
    }

    /** Marketing product family name (e.g. "WebForms Suite"); falls back to core row name when not set. */
    public function getProductName(): string
    {
        return $this->productName !== '' ? $this->productName : $this->core->name;
    }

    public function hasAddons(): bool
    {
        return $this->addons !== [];
    }

    public function isExpandable(): bool
    {
        return $this->hasAddons();
    }
}
