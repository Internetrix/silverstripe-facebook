# Internetrix / Silverstripe Facebook

[![Build Status](link)]
[![Scrutinizer Code Quality](link)]
[![codecov](link)]
[![Version](link)]
[![License](link)]


## Introduction

Adds a link to the Silverstripe SiteConfig that prompts login from the User. Once logged in, an Auth Token is generated 
that is automatically extended to have a Long Access Period. This token can then be accessed by custom code for use in
generating social feeds.

A 'Manual Override' feature has been added to allow manual setting of an access token if required.

## Requirements
* SilverStripe CMS: ^4.0
* facebook/graph-sdk: ^5.7
* unclecheese/display-logic: ^2.0.1

## Installation

```
composer require internetrix/silverstripe-facebook
```

## Quickstart

This module requires the domain to be using HTTPS, as per Facebook's security requirements.
You will also need to add the following domain (replaced with your details) to the Valid OAuth Redirect URIs in the App Settings:

``` 
https://{yourdomain}/facebook/login
e.g. https://local.com/facebook/login
```

Please include the following config to a .yml file to enable the connection:

```
Internetrix\Facebook\Config:
  facebook_public_token: 'your-public-token-here'
  facebook_secret_token: 'your-secret-token-here'
```

You can access the token for your projects via the following:

```
$siteConfig = SiteConfig::current_site_config();

/** @var AccessToken $accessToken */
$accessToken = unserialize($siteConfig->FacebookAccessToken);

if ($accessToken && is_a($accessToken, AccessToken::class)) {
    $accessToken = $accessToken->getValue();
}
```
Or with the following if you implement the `FacebookHelperTrait`:
```
$this->getSiteAccessToken();
```



## TODO

* Implement proper error handling and reporting for User
* Implement script to auto-determine Profile ID
* Allow customisation of permissions?
* ~~If access expires due to password change, add notifications to prompt user to reauthenticate~~ Just add popups now
* ~~Style the Login button to look like a Facebook Login button~~
* Implement logout/de-auth function
* ~~Display Access Token field if in Dev environment/specific config enabled?~~
* ~~Create a Job for the Validation Task using the Queued Jobs module? Necessary?~~
* ~~Add below Page Access Token script to Helper Trait,~~ add error handling

