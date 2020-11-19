<?php

namespace Internetrix\Facebook\Tasks;

use Facebook\Authentication\AccessToken;
use Internetrix\Facebook\Traits\FacebookHelperTrait;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DB;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class FacebookValidationTask
 * @package Internetrix\Dev\Tasks
 */
class FacebookValidationTask extends BuildTask
{
    use FacebookHelperTrait;

    protected $title = "Facebook Validation";

    protected $description = "Checks the expiry status of our Facebook Token and refreshes if needed.";

    private static $segment = 'facebook-validation';

    protected $db;

    /**
     * @param HTTPRequest $request
     * @throws
     */
    public function run($request)
    {
        set_time_limit(0);

        $siteConfig = SiteConfig::current_site_config();
        $accessToken = unserialize($siteConfig->FacebookAccessToken);

        if (is_a($accessToken, AccessToken::class)) {
            DB::alteration_message('Found a valid token.');

            $expiryDate = $accessToken->getExpiresAt()->format('d-m-Y');
            $weekBefore = date('d-m-Y', strtotime('-7 days', strtotime($expiryDate)));

            if (strtotime($weekBefore) <= strtotime("today")) {
                DB::alteration_message('Token is about to expire. Refreshing token.');

                $connection = $this->createFacebookConnection();
                $oAuth2Client = $connection->getOAuth2Client();

                $newToken = $oAuth2Client->getLongLivedAccessToken($accessToken);

                // If no expiry has been set, create a new token with a set expiry date of +3 months
                if (!$newToken->getExpiresAt()) {
                    $newToken = new AccessToken($newToken->getValue(), strtotime('+3 months'));
                }

                $siteConfig->FacebookAccessToken = serialize($newToken);
                $siteConfig->write();

                DB::alteration_message('Token successfully refreshed.');
            } else {
                DB::alteration_message('Token has not yet expired. Cancelling process.');
            }
        } else {
            DB::alteration_message('No valid Access Token has been found. Cancelling process.');
        }
    }
}
