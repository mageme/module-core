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

namespace MageMe\Core\Helper;

use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\Exception\LocalizedException;
use Exception;

class AssetHelper
{
    protected AssetRepository $assetRepository;

    /**
     * @param AssetRepository $assetRepository
     */
    public function __construct(
        AssetRepository $assetRepository
    ) {
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get static file content
     *
     * @param string $filePath
     * @return string
     * @throws LocalizedException
     */
    public function getContent(string $filePath): string
    {
        $asset = $this->assetRepository->createAsset($filePath);
        try {
            $file = $asset->getSourceFile();
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $content = file_get_contents($file);
        } catch (Exception $e) {
            throw new LocalizedException(__('Unable to read file: %1', $filePath));
        }
        if ($content === false) {
            throw new LocalizedException(__('Unable to read file: %1', $filePath));
        }
        return $content;
    }
}
