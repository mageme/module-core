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

namespace MageMe\Core\Plugin\Config;

use MageMe\Core\Config\Feed;
use MageMe\Core\Model\Feed\InstallationProfile;

/**
 * Adds the installation profile to the news feed request.
 */
class FeedUrl
{
    /** @var InstallationProfile */
    private InstallationProfile $installationProfile;

    /**
     * @param InstallationProfile $installationProfile
     */
    public function __construct(InstallationProfile $installationProfile)
    {
        $this->installationProfile = $installationProfile;
    }

    /**
     * Append the installation profile parameters to the feed URL
     *
     * @param Feed $subject
     * @param string $result
     * @return string
     */
    public function afterGetFeedUrl(Feed $subject, string $result): string
    {
        $params = $this->installationProfile->getParams();
        if (!$params) {
            return $result;
        }

        $separator = strpos($result, '?') === false ? '?' : '&';

        return $result . $separator . http_build_query($params);
    }
}
