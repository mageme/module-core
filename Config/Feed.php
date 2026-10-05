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

namespace MageMe\Core\Config;

use Laminas\Http\Request;
use SimpleXMLElement;

class Feed extends \Magento\AdminNotification\Model\Feed
{
    public const MAGEME_FEED_URL = 'mageme.com/feeds/m2.rss';

    /**
     * Get MageMe feed URL
     *
     * @return string
     */
    public function getFeedUrl(): string
    {
        if ($this->_feedUrl === null) {
            $this->_feedUrl = 'https://' . self::MAGEME_FEED_URL;
        }
        return $this->_feedUrl;
    }

    /**
     * Retrieve feed data, discarding anything the server did not answer with a success status
     *
     * @return SimpleXMLElement|false
     */
    public function getFeedData()
    {
        $curl = $this->curlFactory->create();
        $curl->setOptions(
            [
                CURLOPT_TIMEOUT   => 2,
                CURLOPT_USERAGENT => $this->productMetadata->getName()
                    . '/' . $this->productMetadata->getVersion()
                    . ' (' . $this->productMetadata->getEdition() . ')',
                CURLOPT_REFERER   => ''
            ]
        );
        $curl->write(Request::METHOD_GET, $this->getFeedUrl(), '1.0');
        $body   = $curl->read();
        $status = (int)$curl->getInfo(CURLINFO_HTTP_CODE);
        $curl->close();

        if ($status < 200 || $status > 299) {
            return false;
        }

        $parts = preg_split('/^\r?$/m', $body, 2);
        $data  = trim($parts[1] ?? '');

        try {
            return new SimpleXMLElement($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Observe and check for updates
     *
     * @return void
     */
    public function observe()
    {
        $this->checkUpdate();
    }

    /**
     * @inheritdoc
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load('mageme_notifications_lastcheck');
    }

    /**
     * @inheritdoc
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), 'mageme_notifications_lastcheck');

        return $this;
    }
}
