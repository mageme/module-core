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

class Status
{
    public const UP_TO_DATE        = 'up_to_date';
    public const UPDATE_AVAILABLE  = 'update_available';
    public const NOT_INSTALLED     = 'not_installed';
    /** Installed, but the running suite major absorbed it — the merchant should remove it. */
    public const SUPERSEDED        = 'superseded';
}
