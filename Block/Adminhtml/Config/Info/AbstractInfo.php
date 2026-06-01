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

namespace MageMe\Core\Block\Adminhtml\Config\Info;

use Exception;
use MageMe\Core\Plugin\Magento\Config\Model\Config\Structure\Data\ConfigMerge;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Module\ModuleListInterface;

abstract class AbstractInfo extends Field
{
    /** Json fields */
    public const DESCRIPTION = 'description';
    public const IMAGE = 'image';
    public const NAME = 'name';
    public const PRICE = 'price';
    public const RELEASE_NOTES = 'release_notes';
    public const URL = 'url';
    public const VERSION = 'version';
    public const LINKS = 'links';
    public const ADDONS = 'add-ons';

    /**
     * Cache group Tag
     */
    public const CACHE_GROUP = Config::TYPE_IDENTIFIER;

    /**
     * Prefix for cache key of block
     */
    public const CACHE_KEY_PREFIX = 'MAGEME_';

    /**
     * Cache tag for MageMe extensions info
     */
    public const CACHE_TAG = 'extensions';

    /**
     * Mageme api url to get extension json
     */
    public const API_URL = 'https://info.mageme.com/modules.json';

    protected ModuleListInterface $_moduleList;

    protected ProductMetadataInterface $_metadata;

    protected Curl $curl;

    /**
     * @param ModuleListInterface $moduleList
     * @param Context $context
     * @param ProductMetadataInterface $metadata
     * @param array $data
     * @param Curl|null $curl
     */
    public function __construct(
        ModuleListInterface      $moduleList,
        Context                  $context,
        ProductMetadataInterface $metadata,
        array                    $data = [],
        ?Curl                    $curl = null
    ) {
        $this->_moduleList = $moduleList;
        $this->_metadata   = $metadata;
        $this->curl        = $curl ?? ObjectManager::getInstance()->get(Curl::class);
        parent::__construct($context, $data);
    }

    /**
     * Get module information by name
     *
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo(string $moduleName)
    {
        $info = $this->getInfo();
        if (isset($info[$moduleName])) {
            return $info[$moduleName];
        }
        return [];
    }

    /**
     * Get extensions information from API
     *
     * @return bool|mixed|string
     */
    public function getInfo()
    {
        $result = $this->_loadCache();
        if (!$result) {
            try {
                $this->curl->setOption(CURLOPT_TIMEOUT, 5);
                $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 5);
                $this->curl->get(self::API_URL);
                $result = $this->curl->getBody();
                if ($result === '' || $result === false) {
                    return false;
                }
                $this->_saveCache($result);
            } catch (Exception $e) {
                return false;
            }
        }

        return json_decode($result, true);
    }

    /**
     * Get module name from element config
     *
     * @param AbstractElement $element
     * @return string
     */
    public function getElementModuleName(AbstractElement $element): string
    {
        $config = $element->getData('field_config');
        return $config[ConfigMerge::MODULE_NAME] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element): string
    {
        return empty($this->_getElementHtml($element)) ? '' : parent::render($element);
    }
}
