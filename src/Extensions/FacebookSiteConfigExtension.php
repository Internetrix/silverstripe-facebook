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
        'FacebookAccessToken'   => 'Text',
        'FacebookLongToken'     => 'Text',
        'FacebookExpiryDate'    => 'Varchar(10)',
        'FacebookPermanent'    => 'Boolean',
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

        $accessToken = $this->getSiteAccessToken() ? $this->getSiteAccessToken()->getValue() : null;
        $longToken = $this->getLongAccessToken() ? $this->getLongAccessToken()->getValue() : null;
        $expiresAt = $longToken ? $this->getLongAccessToken()->getExpiresAt()->format('d-m-Y') : null;

        $connection = $this->createFacebookConnection();

        $permissions = [];
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
        } else {
            $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message warning">Please define the Public and Secret token configs to enable login.</p>');
        }

        if ($this->getLongAccessToken()) {
            if ($this->getRefreshPeriod($this->getLongAccessToken())) {
                $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message warning">Your access will expire soon. Please re-authorise.</p>');
            } else {
                if (strtotime($expiresAt) < strtotime("today")) {
                    $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message warning">Your access has expired. Please re-authorise.</p>');
                } else {
                    $fields->fieldByName('Root.Facebook.SocialDesc')->setValue('<p class="message notice">Facebook has been connected.</p>');
                }
            }
        }

        $fields->addFieldsToTab('Root.Facebook', [
            HeaderField::create('DeveloperSocial', 'Developer Information'),
            CompositeField::create([
                Wrapper::create(
                    TextareaField::create('Token', 'Access Token')
                        ->setValue($accessToken)
                        ->setReadonly(true),
                    TextareaField::create('LongToken', 'Long Access Token')
                        ->setValue($longToken)
                        ->setReadonly(true),
                    ReadonlyField::create('Expiry', 'Expires')
                        ->setValue($expiresAt)
                )->hideIf('FacebookOverride')->isChecked()->end(),
                Wrapper::create(
                    TextareaField::create('FacebookAccessToken', 'Access Token'),
                    TextareaField::create('FacebookLongToken', 'Long Access Token'),
                    TextField::create('FacebookExpiryDate', 'Expires')
                )->displayIf('FacebookOverride')->isChecked()->end(),
                TextareaField::create('TokenPermissions', 'Permissions')
                    ->setValue(implode(',', $permissions))
                    ->setReadonly(true),
                CheckboxField::create('FacebookOverride', 'Manual Override'),
                CheckboxField::create('FacebookPermanent', 'Is Business Token')
            ]),
        ]);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $override = $this->owner->getField('FacebookOverride');
        if ($override) {
            $expiry = $this->owner->getField('FacebookExpiryDate');
            if ($expiry->isChanged()) {
                $this->owner->FacebookExpiryDate = strtotime($expiry);
            }
        }
    }
}
