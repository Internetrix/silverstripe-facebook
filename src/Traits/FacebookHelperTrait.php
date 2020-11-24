<?php

namespace Internetrix\Facebook\Traits;

use Facebook\Authentication\AccessToken;
use Facebook\Facebook;
use SilverStripe\Core\Config\Config;
use SilverStripe\SiteConfig\SiteConfig;

trait FacebookHelperTrait
{
    /**
     * @return bool|Facebook
     */
    public function createFacebookConnection()
    {
        $config = $this->getFacebookConfig();

        if ($config) {
            return new Facebook([
                'app_id' => $config[0],
                'app_secret' => $config[1],
                'default_graph_version' => 'v8.0',
            ]);
        } else {
            return false;
        }
    }

    /**
     * @return array|bool
     */
    public function getFacebookConfig()
    {
        if (Config::inst()->exists('Internetrix\Facebook\Config', 'facebook_public_token') && Config::inst()->exists('Internetrix\Facebook\Config', 'facebook_secret_token')) {
            return [Config::inst()->get('Internetrix\Facebook\Config', 'facebook_public_token'), Config::inst()->get('Internetrix\Facebook\Config', 'facebook_secret_token')];
        } else {
            return false;
        }
    }

    /**
     * @return string|null
     */
    public function getSiteAccessToken()
    {
        $siteConfig = SiteConfig::current_site_config();

        /** @var AccessToken $accessToken */
        $accessToken = unserialize($siteConfig->FacebookAccessToken);

        if ($accessToken && is_a($accessToken, AccessToken::class)) {
            return $accessToken->getValue();
        }

        return null;
    }
}
