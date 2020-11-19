<?php

namespace Internetrix\Facebook\Controllers;

use Facebook\Authentication\AccessToken;
use Internetrix\Facebook\Traits\FacebookHelperTrait;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\Debug;
use SilverStripe\SiteConfig\SiteConfig;

class FacebookConnectionController extends Controller
{
    use FacebookHelperTrait;

    private static $allowed_actions = [
        'getFacebookLogin'
    ];

    private static $url_handlers = [
        'facebook/$Action' => 'getFacebookLogin',
        'login/$Action' => 'getFacebookLogin',
        'facebook/login' => 'getFacebookLogin',
    ];

    public function getFacebookLogin(HTTPRequest $request)
    {
        $fb = $this->createFacebookConnection();

        $helper = $fb->getRedirectLoginHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch(Facebook\Exception\ResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch(Facebook\Exception\SDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }

        $oAuth2Client = $fb->getOAuth2Client();

        if (!$accessToken->isLongLived()) {
            // Exchanges a short-lived access token for a long-lived one
            try {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            } catch (Facebook\Exception\SDKException $e) {
                echo "<p>Error getting long-lived access token: " . $e->getMessage() . "</p>\n\n";
                exit;
            }
        }

        // If no expiry has been set, create a new token with a set expiry date of +3 months
        if (!$accessToken->getExpiresAt()) {
            $setExpiry = new \DateTime();
            $setExpiry->setTimestamp(strtotime('+3 months'));
            $accessToken = new AccessToken($accessToken->getValue(), strtotime('+3 months'));
        }

        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->FacebookAccessToken = serialize($accessToken);
        $siteConfig->write();

        Controller::curr()->redirect('/admin/settings');
    }
}
