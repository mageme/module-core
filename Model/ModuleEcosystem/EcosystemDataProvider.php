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

use MageMe\Core\Helper\GenericLicenseHelper;
use MageMe\Core\Helper\ModulesHelper;
use MageMe\Core\Model\License\LicenseStatus;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Module\ModuleListInterface;

class EcosystemDataProvider
{
    /** @var ModuleListInterface */
    private $moduleList;
    /** @var RemoteCatalog */
    private $catalog;
    /** @var ModulesHelper */
    private $modulesHelper;
    /** @var UrlInterface */
    private $backendUrl;
    /** @var LicenseMetaResolver */
    private $licenseMetaResolver;
    /** @var GenericLicenseHelper */
    private $genericLicenseHelper;
    /** @var EditionDetector */
    private $editionDetector;
    /** @var CtaLinkBuilder */
    private $ctaLinkBuilder;
    /** @var AddonCompatibility */
    private $addonCompatibility;
    /** @var array<string, EcosystemView> */
    private $memo = [];

    public function __construct(
        ModuleListInterface  $moduleList,
        RemoteCatalog        $catalog,
        ModulesHelper        $modulesHelper,
        UrlInterface         $backendUrl,
        LicenseMetaResolver  $licenseMetaResolver,
        GenericLicenseHelper $genericLicenseHelper,
        EditionDetector      $editionDetector,
        CtaLinkBuilder       $ctaLinkBuilder,
        AddonCompatibility   $addonCompatibility
    ) {
        $this->moduleList           = $moduleList;
        $this->catalog              = $catalog;
        $this->modulesHelper        = $modulesHelper;
        $this->backendUrl           = $backendUrl;
        $this->licenseMetaResolver  = $licenseMetaResolver;
        $this->genericLicenseHelper = $genericLicenseHelper;
        $this->editionDetector      = $editionDetector;
        $this->ctaLinkBuilder       = $ctaLinkBuilder;
        $this->addonCompatibility   = $addonCompatibility;
    }

    private function buildLicenseInfo(string $moduleName): LicenseInfo
    {
        $meta    = $this->licenseMetaResolver->resolveFor($moduleName);
        $edition = $this->editionDetector->getCode();

        if ($meta === null) {
            $entry = $this->catalog->get($moduleName);
            $tier  = isset($entry['tier']) && is_string($entry['tier']) ? $entry['tier'] : null;
            $statusUrl = $this->backendUrl->getUrl('core/license/status', ['module_name' => $moduleName]);
            return new LicenseInfo(
                false,                              // supported
                false,                              // isActive
                null,                               // serial
                '',                                 // moduleId
                $moduleName,                        // moduleName
                '',                                 // activateUrl
                '',                                 // deactivateUrl
                LicenseStatus::STATE_INACTIVE,      // state
                null,                               // validUntil
                $statusUrl,
                $tier,
                null,                               // licenseCode
                $edition
            );
        }

        $statusUrl = $this->backendUrl->getUrl(
            'core/license/status',
            ['license_section' => $meta->licenseSection, 'module_name' => $meta->ownerModuleName]
        );
        $status   = $this->genericLicenseHelper->getStatus($meta->licenseSection);
        $isActive = $this->genericLicenseHelper->isActive($meta->licenseSection);

        return new LicenseInfo(
            true,                                                                           // supported
            $isActive,
            $this->genericLicenseHelper->getSerial($meta->licenseSection),
            $meta->licenseSection,                                                          // moduleId (= license_section)
            $meta->ownerModuleName,
            $this->backendUrl->getUrl('core/license/activate'),
            $this->backendUrl->getUrl('core/license/deactivate'),
            $status !== null && isset($status->state) ? $status->state
                : ($isActive ? LicenseStatus::STATE_ACTIVE : LicenseStatus::STATE_INACTIVE),
            $status !== null ? $status->validUntil : null,
            $statusUrl,
            $meta->tier,
            $meta->licenseCode,
            $edition
        );
    }

    public function buildFor(string $coreModuleName): EcosystemView
    {
        if (isset($this->memo[$coreModuleName])) {
            return $this->memo[$coreModuleName];
        }

        $coreEntry = $this->catalog->get($coreModuleName);
        $core      = $this->buildRow($coreModuleName, $coreEntry, true);

        $addonNames = isset($coreEntry['add-ons']) && is_array($coreEntry['add-ons']) ? $coreEntry['add-ons'] : [];
        // Filtered before anything is derived from it: counts, tier pills, subgroups and the
        // Pro offer all read off this list.
        $addonNames = $this->addonCompatibility->filterFor($coreModuleName, $addonNames);
        $addons     = [];
        foreach ($addonNames as $addonName) {
            if (!is_string($addonName) || $addonName === '') {
                continue;
            }
            $addons[] = $this->buildRow($addonName, $this->catalog->get($addonName), false);
        }

        // An add-on the running major absorbed is still on disk, so it is listed — but as something
        // to remove, not as a product. Marked before the update count so a stale 3.x add-on never
        // advertises an upgrade the merchant must not install.
        foreach ($addons as $row) {
            if ($this->addonCompatibility->isSupersededFor($coreModuleName, $row->moduleName)) {
                $row->status = Status::SUPERSEDED;
            }
        }

        $updateCount = (int)$core->hasUpdate();
        foreach ($addons as $row) {
            if ($row->hasUpdate()) {
                $updateCount++;
            }
        }

        $coreTier = isset($coreEntry['tier']) && is_string($coreEntry['tier']) ? $coreEntry['tier'] : null;
        $license  = $this->buildLicenseInfo($coreModuleName);

        // Collapsed by default. Expanded only for a free suite with nothing paid resolved yet and
        // at least one uninstalled Pro add-on — i.e. exactly when there is something to upgrade to
        // and the collapsed header cannot say so. A paying customer is never opened into a list of
        // add-ons they have not bought. The user toggle is persisted in localStorage and overrides this.
        // The tier test deliberately mirrors CtaLinkBuilder::getProUrl() so that expanding and
        // offering the upgrade can never disagree; both read the legacy `tier` string.
        $expanded = false;
        foreach ($addons as $row) {
            if ($row->status === Status::SUPERSEDED) {
                $expanded = true;
                break;
            }
        }
        if (!$expanded && $coreTier === 'free' && !$license->supported) {
            foreach ($addons as $row) {
                if (!$row->isInstalled() && in_array('pro', $row->tiers, true)) {
                    $expanded = true;
                    break;
                }
            }
        }

        $docsUrl     = $this->extractLinkUrl($coreEntry, ['Documentation', 'Docs', 'User Guide']);
        $supportUrl  = $this->extractLinkUrl($coreEntry, ['Support', 'Extension Support', 'Contact Support']);
        $productName = isset($coreEntry['product_name']) && is_string($coreEntry['product_name']) && $coreEntry['product_name'] !== ''
            ? $coreEntry['product_name']
            : '';

        // Show row-level tier pills only for "mixed" suites: free core with at least one pro addon
        // (case B per LicenseMetaResolver). Uniform pro suites (WebForms, HidePricePro, EasyQuote)
        // and uniform free suites suppress per-row Pro/Free labels — they add no information.
        $showTierPills = false;
        if ($coreTier === 'free') {
            foreach ($addonNames as $addonName) {
                if (!is_string($addonName) || $addonName === '') {
                    continue;
                }
                $addonEntry = $this->catalog->get($addonName);
                if (isset($addonEntry['tier']) && $addonEntry['tier'] === 'pro') {
                    $showTierPills = true;
                    break;
                }
            }
        }

        $cta = $this->ctaLinkBuilder->build($coreEntry, $license, $expanded);

        // Every uninstalled paid add-on gets its own tagged upgrade link, so the row a merchant
        // clicked is visible in analytics instead of collapsing into one anonymous CTA.
        if ($cta['getProUrl'] !== null) {
            foreach ($addons as $addonRow) {
                if (!$addonRow->isInstalled() && in_array('pro', $addonRow->tiers, true)) {
                    $addonRow->proUrl = $this->ctaLinkBuilder->proUrlForRow(
                        $coreEntry,
                        $license,
                        $expanded,
                        $addonRow->moduleName,
                        $this->catalog->get($addonRow->moduleName)
                    );
                }
            }
        }

        return $this->memo[$coreModuleName] = new EcosystemView(
            $core,
            $addons,
            $updateCount,
            $expanded,
            $docsUrl,
            $supportUrl,
            $productName,
            $license,
            $showTierPills,
            $cta['renewUrl'],
            $cta['getProUrl'],
            $cta['purchaseUrl'],
            $cta['buyUrl'],
            $cta['proPlateUrl']
        );
    }

    public function buildRowFor(string $moduleName): EcosystemRow
    {
        return $this->buildRow($moduleName, $this->catalog->get($moduleName), true);
    }

    /** @param array<string, mixed> $entry */
    private function buildRow(string $moduleName, array $entry, bool $isCore): EcosystemRow
    {
        $localInfo    = $this->moduleList->getOne($moduleName);
        $localVersion = isset($localInfo['setup_version']) ? $localInfo['setup_version'] : null;
        if (is_string($localVersion) && $localVersion === '') {
            $localVersion = null;
        }
        $isInstalled = $localInfo !== null;

        $latest = isset($entry['version']) ? $entry['version'] : null;
        if (!is_string($latest) || $latest === '') {
            $latest = null;
        }

        $name = (string)(isset($entry['name']) ? $entry['name'] : $this->humanize($moduleName));
        $description = isset($entry['description']) && is_string($entry['description'])
            ? $entry['description']
            : null;
        $releaseNotesUrl = isset($entry['release_notes']) && is_string($entry['release_notes']) && $entry['release_notes'] !== ''
            ? $entry['release_notes']
            : null;
        // tiers (array) preferred; fall back to legacy single tier (string)
        $tiers = [];
        if (isset($entry['tiers']) && is_array($entry['tiers'])) {
            foreach ($entry['tiers'] as $t) {
                if (is_string($t) && $t !== '') {
                    $tiers[] = $t;
                }
            }
        } elseif (isset($entry['tier']) && is_string($entry['tier']) && $entry['tier'] !== '') {
            $tiers = [$entry['tier']];
        }

        $subgroup = isset($entry['subgroup']) && is_string($entry['subgroup']) && $entry['subgroup'] !== ''
            ? $entry['subgroup']
            : null;

        if (!$isInstalled) {
            return new EcosystemRow(
                $moduleName,
                $name,
                null,
                $latest,
                Status::NOT_INSTALLED,
                $releaseNotesUrl,
                $description,
                $tiers,
                $subgroup
            );
        }

        $hasUpdate = $latest !== null
            && $localVersion !== null
            && version_compare($latest, $localVersion, '>');

        return new EcosystemRow(
            $moduleName,
            $name,
            $localVersion,
            $latest,
            $hasUpdate ? Status::UPDATE_AVAILABLE : Status::UP_TO_DATE,
            $releaseNotesUrl,
            $description,
            $tiers,
            $subgroup
        );
    }

    private function humanize(string $moduleName): string
    {
        $shortName = preg_replace('/^MageMe[_]?/', '', $moduleName);
        if ($shortName === null) {
            $shortName = $moduleName;
        }
        $words = preg_replace('/(?<!^)([A-Z][a-z])|(?<=[a-z])([A-Z])/', ' $1$2', $shortName);
        return trim((string)$words);
    }

    /**
     * @param array<string, mixed> $entry
     * @param string[] $titlesInOrder
     * @return string|null
     */
    private function extractLinkUrl(array $entry, array $titlesInOrder)
    {
        $links = isset($entry['links']) ? $entry['links'] : null;
        if (!is_array($links)) {
            return null;
        }
        foreach ($titlesInOrder as $wanted) {
            foreach ($links as $link) {
                if (!is_array($link)) {
                    continue;
                }
                $title = (string)(isset($link['title']) ? $link['title'] : '');
                if (strcasecmp($title, $wanted) === 0) {
                    $url = (string)(isset($link['url']) ? $link['url'] : '');
                    return $url !== '' ? $url : null;
                }
            }
        }
        return null;
    }
}
