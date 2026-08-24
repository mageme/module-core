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

use Magento\Framework\Module\ModuleListInterface;

/**
 * Keeps add-ons that belong to the suite-core major the merchant actually runs.
 *
 * A suite may drop add-ons between majors — WebForms 4 folds several of them into the core, so
 * they stop being products. Both WF3 and WF4 merchants read the same catalog, so the entries stay
 * and carry a `core_majors` list instead; this filter applies it.
 *
 * An add-on the merchant still has installed is kept regardless: it is sitting in their system and
 * needs removing, so `isSupersededFor()` marks it instead of the list hiding it.
 *
 * Fails open on purpose. An unreadable local version, an absent or malformed `core_majors` — all of
 * them keep the add-on visible. Erasing a live product from a merchant's panel because a version
 * string could not be parsed is far worse than showing one row too many.
 */
class AddonCompatibility
{
    /**
     * @var RemoteCatalog
     */
    private $catalog;
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    public function __construct(RemoteCatalog $catalog, ModuleListInterface $moduleList)
    {
        $this->catalog    = $catalog;
        $this->moduleList = $moduleList;
    }

    /**
     * Every caller resolves the running major the same way — through here.
     *
     * @param string[] $addonNames
     * @return string[]
     */
    public function filterFor(string $coreModuleName, array $addonNames): array
    {
        return $this->filter($addonNames, $this->localVersionOf($coreModuleName));
    }

    /**
     * @param string[] $addonNames
     * @return string[]
     */
    public function filter(array $addonNames, ?string $coreLocalVersion): array
    {
        $major = $this->majorOf($coreLocalVersion);
        if ($major === null) {
            return $addonNames;
        }

        $kept = [];
        foreach ($addonNames as $addonName) {
            if (!is_string($addonName) || $addonName === '') {
                continue;
            }
            if (!$this->belongsToMajor($addonName, $major) && !$this->isInstalled($addonName)) {
                continue;
            }
            $kept[] = $addonName;
        }

        return $kept;
    }

    /**
     * True for an add-on the merchant still runs while the suite major it belonged to is gone —
     * WebForms 4 absorbed it, so the files sitting in their install are dead weight.
     */
    public function isSupersededFor(string $coreModuleName, string $addonName): bool
    {
        if ($addonName === '' || !$this->isInstalled($addonName)) {
            return false;
        }
        $major = $this->majorOf($this->localVersionOf($coreModuleName));

        return $major !== null && !$this->belongsToMajor($addonName, $major);
    }

    private function belongsToMajor(string $addonName, int $major): bool
    {
        $majors = $this->majorsOf($this->catalog->get($addonName));

        return $majors === [] || in_array($major, $majors, true);
    }

    private function isInstalled(string $moduleName): bool
    {
        return $this->moduleList->getOne($moduleName) !== null;
    }

    /**
     * ModuleListInterface only carries ENABLED modules and setup_version is optional, so this
     * returns null often enough that the null path is the important one.
     */
    private function localVersionOf(string $moduleName): ?string
    {
        $info = $this->moduleList->getOne($moduleName);
        if (!is_array($info) || !isset($info['setup_version'])) {
            return null;
        }
        $version = (string) $info['setup_version'];

        return $version === '' ? null : $version;
    }

    /**
     * Leading integer of a version string: "3.6.0", "v4.0.0", "4.0.0-beta1" → 3, 4, 4.
     *
     * Major 0 is refused so the two ends agree: majorsOf() only keeps positive majors, so a
     * catalog can never name 0, and a 0.x suite would otherwise match nothing and mark every
     * tagged add-on as absorbed.
     */
    private function majorOf(?string $version): ?int
    {
        if ($version === null || $version === '') {
            return null;
        }
        if (preg_match('/^v?(\d+)/', trim($version), $m) !== 1) {
            return null;
        }
        $major = (int) $m[1];

        return $major > 0 ? $major : null;
    }

    /**
     * @param array<string, mixed> $entry
     * @return int[]
     */
    private function majorsOf(array $entry): array
    {
        if (!isset($entry['core_majors']) || !is_array($entry['core_majors'])) {
            return [];
        }

        $majors = [];
        foreach ($entry['core_majors'] as $major) {
            if (is_int($major) && $major > 0) {
                $majors[] = $major;
                continue;
            }
            if (is_string($major) && preg_match('/^\d+$/', trim($major)) === 1 && (int) $major > 0) {
                $majors[] = (int) $major;
            }
        }

        return array_values(array_unique($majors));
    }
}
