<?php

namespace Internetrix\Facebook\Traits;

use Facebook\Facebook;
use SilverStripe\Core\Config\Config;

trait FacebookHelperTrait
{
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

    public function getFacebookConfig()
    {
        if (Config::inst()->exists('Internetrix\Facebook\Config', 'facebook_public_token') && Config::inst()->exists('Internetrix\Facebook\Config', 'facebook_secret_token')) {
            return [Config::inst()->get('Internetrix\Facebook\Config', 'facebook_public_token'), Config::inst()->get('Internetrix\Facebook\Config', 'facebook_secret_token')];
        } else {
            return false;
        }
    }
}
