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
use Magento\Framework\UrlInterface;

class Activate implements MessageInterface
{
    protected UrlInterface $urlBuilder;

    protected RemoteCatalog $catalog;

    protected LicenseMetaResolver $licenseMetaResolver;

    protected GenericLicenseHelper $genericLicenseHelper;

    protected ModuleManager $moduleManager;

    /** @var array<string, string>|null */
    private ?array $unlicensed = null;

    public function __construct(
        UrlInterface         $urlBuilder,
        RemoteCatalog        $catalog,
        LicenseMetaResolver  $licenseMetaResolver,
        GenericLicenseHelper $genericLicenseHelper,
        ModuleManager        $moduleManager
    ) {
        $this->urlBuilder           = $urlBuilder;
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
        $links = [];
        foreach ($modules as $ownerModuleName => $name) {
            $meta = $this->licenseMetaResolver->resolveFor($ownerModuleName);
            if ($meta === null) {
                continue;
            }
            $url = $this->urlBuilder->getUrl(
                'adminhtml/system_config/edit',
                ['section' => $meta->licenseSection]
            );
            $links[] = sprintf('<a href="%s">%s</a>', $url, $name);
        }
        return (string)__('Please activate your license for: ') . implode(', ', $links);
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
