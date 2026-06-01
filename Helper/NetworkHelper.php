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

namespace MageMe\Core\Helper;

class NetworkHelper
{
    /**
     * Get client ip
     *
     * @return string
     */
    // phpcs:disable Magento2.Security.Superglobal
    public function getRealIp(): string
    {
        $ip = false;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", (string)$_SERVER['HTTP_X_FORWARDED_FOR']);

            if ($ip) {
                array_unshift($ips, $ip);
                $ip = false;
            }

            $ipsCount = count($ips);
            for ($i = 0; $i < $ipsCount; $i++) {
                if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
                    if (version_compare(phpversion(), "5.0.0", ">=")) {
                        if (ip2long($ips[$i]) != false) {
                            $ip = $ips[$i];
                            break;
                        }
                    } else {
                        if (ip2long($ips[$i]) != -1) {
                            $ip = $ips[$i];
                            break;
                        }
                    }
                }
            }
        }
        return (string)($ip ?: ($_SERVER['REMOTE_ADDR'] ?? ''));
    }
    // phpcs:enable Magento2.Security.Superglobal
}
