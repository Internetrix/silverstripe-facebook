<?php

namespace Internetrix\Facebook\Traits;

use Facebook\Facebook;
use SilverStripe\Core\Config\Config;

trait FacebookHelperTrait
{
    public function createFacebookConnection()
    {
        if (Config::inst()->exists('Internetrix\Facebook\Config', 'facebook_public_token') && Config::inst()->get('Internetrix\Facebook\Config', 'facebook_secret_token')) {
            return new Facebook([
                'app_id' => Config::inst()->get('Internetrix\Facebook\Config', 'facebook_public_token'),
                'app_secret' => Config::inst()->get('Internetrix\Facebook\Config', 'facebook_secret_token'),
                'default_graph_version' => 'v8.0',
            ]);
        } else {
            return false;
        }
    }
}
