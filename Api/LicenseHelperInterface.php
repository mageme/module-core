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

namespace MageMe\Core\Api;

use Magento\Framework\Phrase;

/**
 * @deprecated 2.1.0 Replaced by {@see \MageMe\Core\Model\ModuleEcosystem\LicenseMetaResolver}
 * + {@see \MageMe\Core\Helper\GenericLicenseHelper}. License-section / module-name /
 * module-title for a suite are now resolved from local `etc/license.xml` declarations
 * (and the catalog fallback at info.mageme.com), not from a per-module subclass of
 * {@see \MageMe\Core\Helper\LicenseHelper}. No MageMe_Core code path calls
 * getConfigSection() / getModuleName() / getModuleTitle() anymore. Scheduled for
 * removal in MageMe_Core 3.0.
 */
interface LicenseHelperInterface extends ModuleHelperInterface
{
    /**
     * Get config section
     *
     * @return string
     */
    public function getConfigSection(): string;

    /**
     * Get module name
     *
     * @return string
     */
    public function getModuleName(): string;

    /**
     * Get module title
     *
     * @return string|Phrase
     */
    public function getModuleTitle();
}
