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

namespace MageMe\Core\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Base for storefront full-page GET controllers.
 *
 * Extends the framework Action so the shared App\View is resolved at
 * controller construction (via Context) rather than lazily mid-render.
 * On Hyva themes a late App\View instantiation rebinds the per-request
 * Page\Config builder and forces a second layout generation that drops the
 * head bootstrap blocks; resolving it early keeps a single build.
 */
abstract class AbstractStorefrontGetPage extends Action implements HttpGetActionInterface
{
}
