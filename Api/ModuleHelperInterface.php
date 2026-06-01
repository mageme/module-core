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

/**
 * @deprecated 2.1.0 Marker interface for the legacy per-module helper convention
 * (`MageMe\<Module>\Helper\<Name>` discovered by {@see \MageMe\Core\Helper\ModulesHelper::getModuleHelper()}).
 * No MageMe_Core code path consumes this interface anymore. Retained only so that
 * legacy plugin versions whose helpers still implement it continue to load.
 * Scheduled for removal in MageMe_Core 3.0.
 */
interface ModuleHelperInterface
{
}
