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

namespace MageMe\Core\Notification\License;

use MageMe\Core\Helper\GenericLicenseHelper;
use MageMe\Core\Model\ModuleEcosystem\LicenseMetaResolver;
use MageMe\Core\Model\ModuleEcosystem\RemoteCatalog;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Notification\MessageInterface;

class Activate implements MessageInterface
{
    protected RemoteCatalog $catalog;

    protected LicenseMetaResolver $licenseMetaResolver;

    protected GenericLicenseHelper $genericLicenseHelper;

    protected ModuleManager $moduleManager;

    /** @var array<string, string>|null */
    private ?array $unlicensed = null;

    public function __construct(
        RemoteCatalog        $catalog,
        LicenseMetaResolver  $licenseMetaResolver,
        GenericLicenseHelper $genericLicenseHelper,
        ModuleManager        $moduleManager
    ) {
        $this->catalog              = $catalog;
        $this->licenseMetaResolver  = $licenseMetaResolver;
        $this->genericLicenseHelper = $genericLicenseHelper;
        $this->moduleManager        = $moduleManager;
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        return hash('sha256', 'MAGEME_NOT_ACTIVATED');
    }

    /**
     * @inheritdoc
     */
    public function isDisplayed(): bool
    {
        return (bool)count($this->getUnlicensed());
    }

    /**
     * @inheritdoc
     */
    public function getText(): string
    {
        $modules = $this->getUnlicensed();
        if (empty($modules)) {
            return '';
        }
        return (string)__('Please activate your license for:') . ' ' . implode(', ', $modules);
    }

    /**
     * @inheritdoc
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * Returns map of [ownerModuleName => display name] for suites that need activation
     * AND have at least one locally-enabled module participating in the suite.
     *
     * @return array<string, string>
     */
    private function getUnlicensed(): array
    {
        if ($this->unlicensed !== null) {
            return $this->unlicensed;
        }

        $result = [];
        foreach ($this->catalog->getAll() as $moduleName => $entry) {
            if (!is_string($moduleName) || !is_array($entry)) {
                continue;
            }
            $hasSection = isset($entry['license_section'])
                && is_string($entry['license_section'])
                && $entry['license_section'] !== '';
            $ownsAddons = isset($entry['add-ons']) && is_array($entry['add-ons']) && $entry['add-ons'] !== [];
            if (!$hasSection && !$ownsAddons) {
                continue;
            }
            $meta = $this->licenseMetaResolver->resolveFor($moduleName);
            if ($meta === null) {
                continue;
            }
            if (!$this->moduleManager->isEnabled($meta->ownerModuleName)) {
                continue;
            }
            if ($this->genericLicenseHelper->isActive($meta->licenseSection)) {
                continue;
            }
            $name = isset($entry['name']) && is_string($entry['name']) && $entry['name'] !== ''
                ? $entry['name']
                : $moduleName;
            $result[$moduleName] = $name;
        }

        return $this->unlicensed = $result;
    }
}
