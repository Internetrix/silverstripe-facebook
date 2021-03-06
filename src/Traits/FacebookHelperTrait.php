<?php

namespace Internetrix\Facebook\Traits;

use Facebook\Authentication\AccessToken;
use Facebook\Facebook;
use GuzzleHttp\Client;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
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
        $accessTokenString = $siteConfig->FacebookAccessToken;
        $accessTokenDate = $siteConfig->FacebookExpiryDate;

        if ($accessTokenString) {
            return new AccessToken($accessTokenString, $accessTokenDate ? $accessTokenDate : 0);
        }

        return null;
    }

    /**
     * @param $profileID
     * @param $accessToken
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPageAccessToken($profileID, $accessToken)
    {
        $client = new Client();
        $request = $client->request('GET', 'https://graph.facebook.com/' . $profileID . '?fields=access_token&access_token=' . $accessToken);
        $response = $request->getBody()->getContents();
        $accessToken = json_decode($response, true)['access_token'];

        if ($accessToken) {
            return $accessToken;
        }
    }

    /**
     * @param $accessToken
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function createLongAccessToken($accessToken)
    {
        $connection = $this->createFacebookConnection();
        $oAuth2Client = $connection->getOAuth2Client();

        $newToken = $oAuth2Client->getLongLivedAccessToken($accessToken);

        // If no expiry has been set, create a new token with a set expiry date of +3 months
        if (!$newToken->getExpiresAt()) {
            $newToken = new AccessToken($newToken->getValue(), strtotime('+3 months'));
        }

        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->FacebookLongToken = $newToken->getValue();
        $siteConfig->FacebookExpiryDate = strtotime('+3 months');
        $siteConfig->write();
    }

    public function getLongAccessToken()
    {
        $siteConfig = SiteConfig::current_site_config();

        if (!$siteConfig->FacebookLongToken) {
            if ($this->getSiteAccessToken()) {
                $this->createLongAccessToken($this->getSiteAccessToken());
            } else {
                return null;
            }
        }

        return new AccessToken($siteConfig->FacebookLongToken, $siteConfig->FacebookExpiryDate);
    }

    /**
     * @param $accessToken
     * @return bool
     */
    public function getRefreshPeriod($accessToken)
    {
        $expiryDate = $accessToken->getExpiresAt()->format('d-m-Y');
        $period = date('d-m-Y', strtotime('-14 days', strtotime($expiryDate)));

        if (strtotime($period) <= strtotime("today")) {
            return true;
        }

        return false;
    }
}
