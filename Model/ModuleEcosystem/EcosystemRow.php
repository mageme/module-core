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

final class EcosystemRow
{
    /** @var string */
    public $moduleName;
    /** @var string */
    public $name;
    /** @var string|null */
    public $localVersion;
    /** @var string|null */
    public $latestVersion;
    /** @var string */
    public $status;
    /** @var string|null */
    public $releaseNotesUrl;
    /** @var string|null */
    public $description;
    /** @var string[] */
    public $tiers;
    /** @var string|null */
    public $subgroup;

    /** @param string[] $tiers */
    public function __construct(
        string $moduleName,
        string $name,
        ?string $localVersion,
        ?string $latestVersion,
        string $status,
        ?string $releaseNotesUrl,
        ?string $description,
        array $tiers = [],
        ?string $subgroup = null
    ) {
        $this->moduleName      = $moduleName;
        $this->name            = $name;
        $this->localVersion    = $localVersion;
        $this->latestVersion   = $latestVersion;
        $this->status          = $status;
        $this->releaseNotesUrl = $releaseNotesUrl;
        $this->description     = $description;
        $this->tiers           = $tiers;
        $this->subgroup        = $subgroup;
    }

    public function hasUpdate(): bool
    {
        return $this->status === Status::UPDATE_AVAILABLE;
    }

    public function isInstalled(): bool
    {
        return $this->status !== Status::NOT_INSTALLED;
    }
}
