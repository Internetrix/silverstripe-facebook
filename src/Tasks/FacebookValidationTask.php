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
        $accessToken = $this->getSiteAccessToken();

        if (is_a($accessToken, AccessToken::class)) {
            DB::alteration_message('Found a valid token.');

            if ($this->getRefreshPeriod($accessToken)) {
                DB::alteration_message('Token is about to expire. Refreshing token.');

                $this->refreshAccessToken($accessToken);

                DB::alteration_message('Token successfully refreshed.');
            } else {
                DB::alteration_message('Token has not yet expired. Cancelling process.');
            }
        } else {
            DB::alteration_message('No valid Access Token has been found. Cancelling process.');
        }
    }
}
