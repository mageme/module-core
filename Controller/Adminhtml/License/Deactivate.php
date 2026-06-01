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

namespace MageMe\Core\Controller\Adminhtml\License;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Deactivate extends AbstractAction implements HttpPostActionInterface
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();

        list($licenseSection, $ownerModuleName) = $this->resolveLicenseTarget();
        if ($licenseSection === null || $ownerModuleName === null) {
            return $resultJson->setHttpResponseCode(400)->setData(['error' => 'license_section or module_name required']);
        }

        $result = $this->genericLicenseHelper->deactivateLicense($licenseSection, $ownerModuleName);
        return $resultJson->setData($result);
    }
}
