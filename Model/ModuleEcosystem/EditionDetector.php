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

use Magento\Framework\App\ProductMetadataInterface;

/**
 * Buckets Magento edition into license-server platform code.
 *
 * Community → M2CE; everything else (Enterprise / Cloud / B2B) → M2EE.
 * If a third platform appears in mm_license schema, extend here.
 */
class EditionDetector
{
    /** @var ProductMetadataInterface */
    private $productMetadata;

    public function __construct(ProductMetadataInterface $productMetadata)
    {
        $this->productMetadata = $productMetadata;
    }

    /** @return string M2CE or M2EE */
    public function getCode(): string
    {
        return $this->productMetadata->getEdition() === 'Community' ? 'M2CE' : 'M2EE';
    }
}
