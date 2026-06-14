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

use MageMe\Core\Model\Declaration\DeclarationRegistry;
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;

/**
 * Resolves licensing meta for a MageMe suite.
 *
 * Decision order:
 *  1. Local declarations (etc/license.xml). If the module locally owns
 *     a section, the licensing decision is made ONLY from local declarations:
 *     it is licensed iff any enabled module declares <license tier="pro">
 *     for that section. Marketplace builds opt out by stripping the file.
 *  2. Catalog fallback (info.mageme.com/modules.json). Original behaviour
 *     (Decision #18, March 2026): tier=pro OR any enabled addon tier=pro.
 *     Used only when the module doesn't locally own a section.
 */
class LicenseMetaResolver
{
    /** @var RemoteCatalog */
    private $catalog;
    /** @var ModuleManager */
    private $moduleManager;
    /** @var EditionDetector */
    private $editionDetector;
    /** @var LoggerInterface */
    private $logger;
    /** @var DeclarationRegistry */
    private $registry;
    /** @var array<string, LicenseMeta|null> */
    private $memo = [];

    public function __construct(
        RemoteCatalog $catalog,
        ModuleManager $moduleManager,
        EditionDetector $editionDetector,
        LoggerInterface $logger,
        DeclarationRegistry $registry
    ) {
        $this->catalog         = $catalog;
        $this->moduleManager   = $moduleManager;
        $this->editionDetector = $editionDetector;
        $this->logger          = $logger;
        $this->registry        = $registry;
    }

    public function resolveFor(string $coreModuleName): ?LicenseMeta
    {
        if (array_key_exists($coreModuleName, $this->memo)) {
            return $this->memo[$coreModuleName];
        }

        $own = $this->registry->getForModule($coreModuleName);
        if ($own !== null && $own->section !== null) {
            return $this->memo[$coreModuleName] = $this->resolveLocally($coreModuleName, $own);
        }

        return $this->memo[$coreModuleName] = $this->resolveFromCatalog($coreModuleName);
    }

    private function resolveLocally(string $coreModuleName, \MageMe\Core\Model\Declaration\ModuleDeclaration $own): ?LicenseMeta
    {
        $sectionName = $own->section->name;

        $contribs = $this->registry->getPaidLicenseDeclarationsForSection($sectionName);
        if ($contribs === []) {
            return null;
        }

        $picked = $own->license;
        if ($picked === null) {
            $picked = $contribs[0];
        }

        $platform = $this->editionDetector->getCode();
        $code = $picked->codes[$platform] ?? null;

        return new LicenseMeta($coreModuleName, $sectionName, $code, $picked->tier);
    }

    private function resolveFromCatalog(string $coreModuleName): ?LicenseMeta
    {
        $entry = $this->catalog->get($coreModuleName);
        if (empty($entry)) {
            return null;
        }

        $tier       = isset($entry['tier']) && is_string($entry['tier']) ? $entry['tier'] : null;
        $isLicensed = $tier === 'pro';

        if (!$isLicensed) {
            $addons = isset($entry['add-ons']) && is_array($entry['add-ons']) ? $entry['add-ons'] : [];
            foreach ($addons as $addonName) {
                if (!is_string($addonName) || $addonName === '') {
                    continue;
                }
                $addon = $this->catalog->get($addonName);
                if (empty($addon) || (isset($addon['tier']) ? $addon['tier'] : null) !== 'pro') {
                    continue;
                }
                if (!$this->moduleManager->isEnabled($addonName)) {
                    continue;
                }
                $isLicensed = true;
                break;
            }
        }

        if (!$isLicensed) {
            return null;
        }

        $licenseSection = isset($entry['license_section']) && is_string($entry['license_section'])
            ? $entry['license_section']
            : '';
        if ($licenseSection === '') {
            $ownsAddons = isset($entry['add-ons']) && is_array($entry['add-ons']) && $entry['add-ons'] !== [];
            if ($ownsAddons) {
                $this->logger->warning(
                    'MageMe LicenseMetaResolver: missing license_section for licensed suite',
                    ['module' => $coreModuleName]
                );
            }
            return null;
        }

        $licenseCode = null;
        $codes = isset($entry['license_codes']) ? $entry['license_codes'] : null;
        if (!is_array($codes)) {
            $this->logger->warning(
                'MageMe LicenseMetaResolver: missing license_codes for licensed suite',
                ['module' => $coreModuleName]
            );
        } else {
            $platformCode = $this->editionDetector->getCode();
            $licenseCode  = isset($codes[$platformCode]) && is_string($codes[$platformCode])
                ? $codes[$platformCode]
                : null;
        }

        return new LicenseMeta($coreModuleName, $licenseSection, $licenseCode, $tier);
    }
}
