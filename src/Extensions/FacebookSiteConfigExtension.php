<?php

namespace Internetrix\Facebook\Extensions;

use Facebook\Authentication\AccessToken;
use Facebook\HttpClients\FacebookCurl;
use Facebook\HttpClients\FacebookCurlHttpClient;
use GuzzleHttp\Client;
use Internetrix\Facebook\Traits\FacebookHelperTrait;
use League\OAuth2\Client\Provider\Facebook;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\SiteConfig\SiteConfig;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class FacebookSiteConfigExtension extends DataExtension
{
    use FacebookHelperTrait;

    private static $db = [
        'FacebookAccessToken' => 'Text'
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Facebook', [
            HeaderField::create('SocialHeader', 'Facebook Connection'),
            LiteralField::create('SocialDesc', '<p class="message">Login via the link below to allow this application to retrieve your Page posts.</p>')
        ]);

        if (Director::isDev()) {
            $fields->addFieldToTab('Root.Facebook', TextareaField::create(
                'FacebookAccessToken',
                'Access Token'
            ));
        }

        $connection = $this->createFacebookConnection();

        if ($connection) {
            $helper = $connection->getRedirectLoginHelper();

            $permissions = [
                'instagram_basic',
                'pages_show_list',
                'pages_read_engagement',
                'public_profile',
                'read_insights'
            ];
            $loginUrl = $helper->getLoginUrl(Director::absoluteBaseURL() . 'facebook/login', $permissions);

            $fields->addFieldToTab('Root.Facebook', LiteralField::create('LoginLink', '
                <div class="form-group field textarea">
                    <label id="title-Form_EditForm_FacebookLoginButton" class="form__field-label"></label>
                    <div class="form__field-holder">
                         <a href="' . $loginUrl . '">
                            <div class="fb-login-button" data-size="large" data-button-type="continue_with" data-layout="default">Continue with Facebook</div>
                         </a>
                    </div>
                </div>
            '));

            if ($this->owner->FacebookAccessToken) {
                /** @var AccessToken $accessToken */
                $accessToken = unserialize($this->owner->FacebookAccessToken);

                if (is_a($accessToken, AccessToken::class)) {
                    if ($accessToken->isExpired()) {
                        $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message warning">Facebook access has expired. Please login again to reauthorise.</p>');
                    } else {
                        $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message notice">Facebook has been successfully connected.</p>');
                    }

                    $fields->addFieldsToTab('Root.Facebook', [
                        HeaderField::create('DeveloperSocial', 'Developer Information'),
                        CompositeField::create([
                            TextareaField::create('Token', 'Token')->setValue($accessToken->getValue())->setReadonly(true),
                            ReadonlyField::create('ExpiryDate', 'Expires')->setValue($accessToken->getExpiresAt()->format('d-m-Y')),
                            TextareaField::create('TokenPermissions', 'Permissions')->setValue(implode(',', $permissions))->setReadonly(true),
                        ])
                    ]);
                } else {
                    $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message error">Error: Something went wrong with the login process. Access is not authorized.</p>');
                }
            }
        } else {
            $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message warning">Please define the Public and Secret token configs.</p>');
        }

        $fields->addFieldsToTab('Root.Facebook', [
            CompositeField::create([
                CheckboxField::create('FacebookOverride', 'Manual Override'),
                Wrapper::create(
                    TextareaField::create('ManualToken', 'Insert Manual Token')->setDescription('Developer tool; allows manual setting of an Access Token')
                )->displayIf('FacebookOverride')->isChecked()->end()
            ])
        ]);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $override = $this->owner->getField('FacebookOverride');
        if ($override) {
            $newToken = $this->owner->getField('ManualToken');

            if ($newToken) {
                $accessToken = new AccessToken($newToken, strtotime('+3 months'));
                $this->owner->FacebookAccessToken = serialize($accessToken);
            }
        }
    }
}
