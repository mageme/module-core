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

namespace MageMe\Core\Block;

use MageMe\Core\Helper\AssetHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Init extends Template
{
    /**
     * @var string
     */
    protected $_template = 'MageMe_Core::loader.phtml';

    protected string $cssTemplate = 'MageMe_Core::inline_css.phtml';

    protected Registry $registry;

    protected AssetHelper $assetHelper;

    protected bool $useRegistry;

    /**
     * @param Registry $registry
     * @param Context $context
     * @param AssetHelper $assetHelper
     * @param array $data
     * @param bool $useRegistry
     */
    public function __construct(
        Registry    $registry,
        Context     $context,
        AssetHelper $assetHelper,
        array       $data = [],
        bool        $useRegistry = true
    ) {
        parent::__construct($context, $data);
        $this->registry    = $registry;
        $this->assetHelper = $assetHelper;
        $this->useRegistry = $useRegistry;
    }

    /**
     * Get registry instance
     *
     * @return Registry
     */
    public function getRegistry(): Registry
    {
        return $this->registry;
    }

    /**
     * Get CSS template path
     *
     * @return string
     */
    public function getCssTemplate(): string
    {
        return $this->cssTemplate;
    }

    /**
     * Get asset file content
     *
     * @param string $filePath
     * @return string
     */
    public function getAssetContent(string $filePath): string
    {
        try {
            return $this->assetHelper->getContent($filePath);
        } catch (LocalizedException $e) {
            return '';
        }
    }

    /**
     * Get inline CSS for given link
     *
     * @param string $link
     * @return string
     * @throws LocalizedException
     */
    public function getInlineCss(string $link): string
    {
        $registry = $this->getRegistry();
        if ($this->useRegistry && $registry->registry($link)) {
            return '';
        }
        $css       = preg_replace_callback(
            '/url\((\.\.\/\.\.\/images\/[^)]+)\)/',
            function ($matches) use ($link) {
                $imagePath = explode('::', $link)[0] . '::' . str_replace('../../', '', $matches[1]);
                return 'url(' . $this->getViewFileUrl($imagePath) . ')';
            },
            $this->getAssetContent($link)
        );
        $inlineCss = $this->getLayout()->createBlock(
            Template::class,
            null,
            ['data' => ['css' => $css]]
        )->setTemplate($this->getCssTemplate());
        if ($this->useRegistry) {
            $registry->register($link, true);
        }
        return $inlineCss->toHtml();
    }
}
