<?php

/*
The MIT License (MIT)

Copyright (c) 2016-2017 Elastic Email, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace ElasticEmailClient {

    class ApiConfiguration
    {
        const AVAILABLE_REQUEST_METHODS = ['GET', 'POST'];

        /**
         * @var string
         */
        private $apiKey;

        /**
         * @var string
         */
        private $apiUrl = 'https://api.elasticemail.com/v2/';

        /**
         * @var float
         */
        private $timeOut = 5.0;

        /**
         * @var \GuzzleHttp\ClientInterface
         */
        private $clientInterface;

        /**
         * ApiConfiguration constructor.
         * @param array $params
         */
        public function __construct(array $params)
        {
            $this->setApiKey($params['apiKey']);
            $this->setApiUrl($params['apiUrl']);
        }

        /**
         * @param string $apiKey
         * @return $this
         */
        public function setApiKey(string $apiKey)
        {
            $this->apiKey = $apiKey;

            return $this;
        }

        /**
         * @return string
         */
        public function getApiKey()
        {
            return $this->apiKey;
        }

        /**
         * @param string $apiUrl
         * @return $this
         */
        public function setApiUrl(string $apiUrl)
        {
            $this->apiUrl = $apiUrl;

            return $this;
        }

        /**
         * @return string
         */
        public function getApiUrl()
        {
            return $this->apiUrl;
        }

        /**
         * @param $timeout
         * @return $this
         */
        public function setTimeout($timeout)
        {
            $this->timeOut = $timeout;

            return $this;
        }

        /**
         * @return float
         */
        public function getTimeout()
        {
            return $this->timeOut;
        }

        /**
         * @param \GuzzleHttp\ClientInterface $interface
         */
        public function setClientInterface(\GuzzleHttp\ClientInterface $interface)
        {
            $this->clientInterface = $interface;
        }

        public function getClientInterface()
        {
            return $this->clientInterface;
        }
    }

    class ElasticClient
    {
        /**
         * ElasticClient constructor.
         * @param \ElasticEmailClient\ApiConfiguration $apiConfiguration
         */
        public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
        {            $this->AccessToken= new \ElasticEmailApi\AccessToken($apiConfiguration);
            $this->Account= new \ElasticEmailApi\Account($apiConfiguration);
            $this->Campaign= new \ElasticEmailApi\Campaign($apiConfiguration);
            $this->Channel= new \ElasticEmailApi\Channel($apiConfiguration);
            $this->Contact= new \ElasticEmailApi\Contact($apiConfiguration);
            $this->Domain= new \ElasticEmailApi\Domain($apiConfiguration);
            $this->Email= new \ElasticEmailApi\Email($apiConfiguration);
            $this->Export= new \ElasticEmailApi\Export($apiConfiguration);
            $this->File= new \ElasticEmailApi\File($apiConfiguration);
            $this->EEList= new \ElasticEmailApi\EEList($apiConfiguration);
            $this->Log= new \ElasticEmailApi\Log($apiConfiguration);
            $this->Segment= new \ElasticEmailApi\Segment($apiConfiguration);
            $this->SMS= new \ElasticEmailApi\SMS($apiConfiguration);
            $this->Template= new \ElasticEmailApi\Template($apiConfiguration);

        }
        public $AccessToken;
        public $Account;
        public $Campaign;
        public $Channel;
        public $Contact;
        public $Domain;
        public $Email;
        public $Export;
        public $File;
        public $EEList;
        public $Log;
        public $Segment;
        public $SMS;
        public $Template;

    }

    abstract class ElasticRequest {
        /**
         * @var \ElasticEmailClient\ApiConfiguration ApiConfiguration
         */
        protected $configuration;

        /**
         * @var \GuzzleHttp\ClientInterface
         */
        private $httpClient;

        public function __construct(\ElasticEmailClient\ApiConfiguration $conf)
        {
            $this->configuration = $conf;

            if ($conf->getClientInterface()) {
                $this->httpClient = $conf->getClientInterface();
            } else {
                $this->httpClient = new \GuzzleHttp\Client([
                    'timeout'  => $this->configuration->getTimeout()
                ]);
            }
        }

        /**
         * @param string $url
         * @param array $data
         * @param string $method
         * @param array $attachments
         * @return \Psr\Http\Message\ResponseInterface
         * @throws \Exception
         */
        protected function sendRequest(string $url, array $data = [], string $method = 'POST', array $attachments = [])
        {
            $method = strtoupper($method);

                    if (!in_array($method, \ElasticEmailClient\ApiConfiguration::AVAILABLE_REQUEST_METHODS))
                    {
                        throw new \Exception('Unallowed request method type');
                    }

            $options = [];
            $data['apikey'] = $this->configuration->getApiKey();

            if (!empty($attachments) && $method === 'POST') {
                $options['multipart'] = $this->parseMultipart($attachments, $data);
            } else {
                if (!empty($data) && empty($attachments) && $method === 'POST') {
                    $options['form_params'] = $data;
                } else {
                    $url.= '?'.http_build_query($data, null, '&');
                }
            }

            $url = $this->configuration->getApiUrl(). $url;

            try
            {
                $response = $this->httpClient->request($method, $url, $options);
                $resp = json_decode($response->getBody()->getContents());
            } catch (\Exception $e) {
                throw $e;
            }

            if (!$resp->success) {
                throw new \Exception($resp->error);
            }

            if ($resp->data) { return $resp->data; }
             
            return $resp;
        }

        /**
            * @param array $attachments
            * @param array $params
            * @return array
            */
        private function parseMultipart(array $attachments, array $params): array
        {
            $result = [];

            foreach ($attachments as $key => $attachment) {
                $result[] = [
                    'name' => 'file_'.$key,
                    'contents' => fopen($attachment, 'r'),
                    'filename' => basename($attachment)
                ];
            }

            foreach ($params as $key => $param) {
                $result[] = [
                    'name' => $key,
                    'contents' => $param
                ];
            }

            return $result;
        }

        public function setHttpClient($client)
        {
            $this->httpClient = $client;
            return $this;
        }

        /**
        * @return \GuzzleHttp\ClientInterface
        */
        public function getHttpClient()
        {
            return $this->httpClient;
        }

        /**
            * @param \ElasticEmailClient\ApiConfiguration $config
            * @return $this
            */
        public function setConfiguration(\ElasticEmailClient\ApiConfiguration $config)
        {
            $this->configuration = $config;

            return $this;
        }

        /**
            * @return \ElasticEmailClient\ApiConfiguration
            */
        public function getConfiguration()
        {
            return $this->configuration;
        }

    }
}

namespace ElasticEmailApi {
/**
 * Manage your AccessTokens (ApiKeys)
 */
class AccessToken extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Add new AccessToken
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $tokenName 
     * @param \ElasticEmailEnums\AccessLevel $accessLevel 
     * @return string
     */
    public function Add($tokenName, $accessLevel) {
        return $this->sendRequest('accesstoken/add', array(
                    'tokenName' => $tokenName,
                    'accessLevel' => $accessLevel));
    }

    /**
     * Permanently delete AccessToken.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $tokenName 
     */
    public function EEDelete($tokenName) {
        return $this->sendRequest('accesstoken/delete', array(
                    'tokenName' => $tokenName));
    }

    /**
     * Get AccessToken list.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return Array<\ElasticEmailEnums\AccessToken>
     */
    public function EEList() {
        return $this->sendRequest('accesstoken/list', array());
    }

    /**
     * Edit AccessToken.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $tokenName 
     * @param \ElasticEmailEnums\AccessLevel $accessLevel 
     * @param string $tokenNameNew 
     */
    public function Update($tokenName, $accessLevel, $tokenNameNew = null) {
        return $this->sendRequest('accesstoken/update', array(
                    'tokenName' => $tokenName,
                    'accessLevel' => $accessLevel,
                    'tokenNameNew' => $tokenNameNew));
    }

}

/**
 * Methods for managing your account and subaccounts.
 */
class Account extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Request premium support for your account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param \ElasticEmailEnums\SupportPlan $supportPlan 
     */
    public function AddDedicatedSupport($supportPlan) {
        return $this->sendRequest('account/adddedicatedsupport', array(
                    'supportPlan' => $supportPlan));
    }

    /**
     * Create new subaccount and provide most important data about it.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @param string $password Current password.
     * @param string $confirmPassword Repeat new password.
     * @param bool $allow2fa 
     * @param bool $requiresEmailCredits True, if account needs credits to send emails. Otherwise, false
     * @param int $maxContacts Maximum number of contacts the account can have
     * @param bool $enablePrivateIPRequest True, if account can request for private IP on its own. Otherwise, false
     * @param bool $sendActivation True, if you want to send activation email to this account. Otherwise, false
     * @param string $returnUrl URL to navigate to after account creation
     * @param ?\ElasticEmailEnums\SendingPermission $sendingPermission Sending permission setting for account
     * @param ?bool $enableContactFeatures Private IP required. Name of the custom IP Pool which Sub Account should use to send its emails. Leave empty for the default one or if no Private IPs have been bought
     * @param string $poolName Name of your custom IP Pool to be used in the sending process
     * @param int $emailSizeLimit Maximum size of email including attachments in MB's
     * @param ?int $dailySendLimit Amount of emails account can send daily
     * @return string
     */
    public function AddSubAccount($email, $password, $confirmPassword, $allow2fa = false, $requiresEmailCredits = false, $maxContacts = 0, $enablePrivateIPRequest = true, $sendActivation = false, $returnUrl = null, $sendingPermission = null, $enableContactFeatures = null, $poolName = null, $emailSizeLimit = 10, $dailySendLimit = null) {
        return $this->sendRequest('account/addsubaccount', array(
                    'email' => $email,
                    'password' => $password,
                    'confirmPassword' => $confirmPassword,
                    'allow2fa' => $allow2fa,
                    'requiresEmailCredits' => $requiresEmailCredits,
                    'maxContacts' => $maxContacts,
                    'enablePrivateIPRequest' => $enablePrivateIPRequest,
                    'sendActivation' => $sendActivation,
                    'returnUrl' => $returnUrl,
                    'sendingPermission' => $sendingPermission,
                    'enableContactFeatures' => $enableContactFeatures,
                    'poolName' => $poolName,
                    'emailSizeLimit' => $emailSizeLimit,
                    'dailySendLimit' => $dailySendLimit));
    }

    /**
     * Add email, template or litmus credits to a sub-account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $credits Amount of credits to add
     * @param string $notes Specific notes about the transaction
     * @param \ElasticEmailEnums\CreditType $creditType Type of credits to add (Email or Litmus)
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to add credits to. Use subAccountEmail or publicAccountID not both.
     */
    public function AddSubAccountCredits($credits, $notes, $creditType = \ElasticEmailEnums\CreditType::Email, $subAccountEmail = null, $publicAccountID = null) {
        return $this->sendRequest('account/addsubaccountcredits', array(
                    'credits' => $credits,
                    'notes' => $notes,
                    'creditType' => $creditType,
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID));
    }

    /**
     * Add notifications webhook
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $webNotificationUrl URL address to receive web notifications to parse and process.
     * @param string $name Filename
     * @param ?bool $notifyOncePerEmail 
     * @param ?bool $notificationForSent 
     * @param ?bool $notificationForOpened 
     * @param ?bool $notificationForClicked 
     * @param ?bool $notificationForUnsubscribed 
     * @param ?bool $notificationForAbuseReport 
     * @param ?bool $notificationForError 
     * @return string
     */
    public function AddWebhook($webNotificationUrl, $name, $notifyOncePerEmail = null, $notificationForSent = null, $notificationForOpened = null, $notificationForClicked = null, $notificationForUnsubscribed = null, $notificationForAbuseReport = null, $notificationForError = null) {
        return $this->sendRequest('account/addwebhook', array(
                    'webNotificationUrl' => $webNotificationUrl,
                    'name' => $name,
                    'notifyOncePerEmail' => $notifyOncePerEmail,
                    'notificationForSent' => $notificationForSent,
                    'notificationForOpened' => $notificationForOpened,
                    'notificationForClicked' => $notificationForClicked,
                    'notificationForUnsubscribed' => $notificationForUnsubscribed,
                    'notificationForAbuseReport' => $notificationForAbuseReport,
                    'notificationForError' => $notificationForError));
    }

    /**
     * Change your email address. Remember, that your email address is used as login!
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $newEmail New email address.
     * @param string $confirmEmail New email address.
     * @param string $sourceUrl URL from which request was sent.
     * @return string
     */
    public function ChangeEmail($newEmail, $confirmEmail, $sourceUrl = "https://elasticemail.com/account/") {
        return $this->sendRequest('account/changeemail', array(
                    'newEmail' => $newEmail,
                    'confirmEmail' => $confirmEmail,
                    'sourceUrl' => $sourceUrl));
    }

    /**
     * Create new password for your account. Password needs to be at least 6 characters long.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $newPassword New password for account.
     * @param string $confirmPassword Repeat new password.
     * @param bool $resetApiKey 
     * @param string $currentPassword Current password.
     */
    public function ChangePassword($newPassword, $confirmPassword, $resetApiKey = false, $currentPassword = null) {
        return $this->sendRequest('account/changepassword', array(
                    'newPassword' => $newPassword,
                    'confirmPassword' => $confirmPassword,
                    'resetApiKey' => $resetApiKey,
                    'currentPassword' => $currentPassword));
    }

    /**
     * Create new password for subaccount. Password needs to be at least 6 characters long.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $newPassword New password for account.
     * @param string $confirmPassword Repeat new password.
     * @param string $subAccountEmail Email address of sub-account
     * @param bool $resetApiKey 
     */
    public function ChangeSubAccountPassword($newPassword, $confirmPassword, $subAccountEmail, $resetApiKey = false) {
        return $this->sendRequest('account/changesubaccountpassword', array(
                    'newPassword' => $newPassword,
                    'confirmPassword' => $confirmPassword,
                    'subAccountEmail' => $subAccountEmail,
                    'resetApiKey' => $resetApiKey));
    }

    /**
     * Deletes specified Subaccount
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to delete. Use subAccountEmail or publicAccountID not both.
     */
    public function DeleteSubAccount($subAccountEmail = null, $publicAccountID = null) {
        return $this->sendRequest('account/deletesubaccount', array(
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID));
    }

    /**
     * Delete notifications webhook
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $webhookID 
     */
    public function DeleteWebhook($webhookID) {
        return $this->sendRequest('account/deletewebhook', array(
                    'webhookID' => $webhookID));
    }

    /**
     * Returns API Key for the given Sub Account.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to retrieve sub-account API Key. Use subAccountEmail or publicAccountID not both.
     * @return string
     */
    public function GetSubAccountApiKey($subAccountEmail = null, $publicAccountID = null) {
        return $this->sendRequest('account/getsubaccountapikey', array(
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID));
    }

    /**
     * Lists all of your subaccounts
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\SubAccount>
     */
    public function GetSubAccountList($limit = 0, $offset = 0) {
        return $this->sendRequest('account/getsubaccountlist', array(
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Loads your account. Returns detailed information about your account.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\Account
     */
    public function Load() {
        return $this->sendRequest('account/load', array());
    }

    /**
     * Load advanced options of your account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\AdvancedOptions
     */
    public function LoadAdvancedOptions() {
        return $this->sendRequest('account/loadadvancedoptions', array());
    }

    /**
     * Lists email credits history
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return Array<\ElasticEmailEnums\EmailCredits>
     */
    public function LoadEmailCreditsHistory() {
        return $this->sendRequest('account/loademailcreditshistory', array());
    }

    /**
     * Load inbound options of your account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\InboundOptions
     */
    public function LoadInboundOptions() {
        return $this->sendRequest('account/loadinboundoptions', array());
    }

    /**
     * Lists all payments
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param DateTime $fromDate Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param DateTime $toDate Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @return Array<\ElasticEmailEnums\Payment>
     */
    public function LoadPaymentHistory($limit, $offset, $fromDate, $toDate) {
        return $this->sendRequest('account/loadpaymenthistory', array(
                    'limit' => $limit,
                    'offset' => $offset,
                    'fromDate' => $fromDate,
                    'toDate' => $toDate));
    }

    /**
     * Lists all referral payout history
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return Array<\ElasticEmailEnums\Payment>
     */
    public function LoadPayoutHistory() {
        return $this->sendRequest('account/loadpayouthistory', array());
    }

    /**
     * Shows information about your referral details
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\Referral
     */
    public function LoadReferralDetails() {
        return $this->sendRequest('account/loadreferraldetails', array());
    }

    /**
     * Shows latest changes in your sending reputation
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\ReputationHistory>
     */
    public function LoadReputationHistory($limit = 20, $offset = 0) {
        return $this->sendRequest('account/loadreputationhistory', array(
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Shows detailed information about your actual reputation score
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\ReputationDetail
     */
    public function LoadReputationImpact() {
        return $this->sendRequest('account/loadreputationimpact', array());
    }

    /**
     * Returns detailed spam check.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\SpamCheck>
     */
    public function LoadSpamCheck($limit = 20, $offset = 0) {
        return $this->sendRequest('account/loadspamcheck', array(
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Lists email credits history for sub-account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to list history for. Use subAccountEmail or publicAccountID not both.
     * @return Array<\ElasticEmailEnums\EmailCredits>
     */
    public function LoadSubAccountsEmailCreditsHistory($subAccountEmail = null, $publicAccountID = null) {
        return $this->sendRequest('account/loadsubaccountsemailcreditshistory', array(
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID));
    }

    /**
     * Loads settings of subaccount
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to load settings for. Use subAccountEmail or publicAccountID not both.
     * @return \ElasticEmailEnums\SubAccountSettings
     */
    public function LoadSubAccountSettings($subAccountEmail = null, $publicAccountID = null) {
        return $this->sendRequest('account/loadsubaccountsettings', array(
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID));
    }

    /**
     * Shows usage of your account in given time.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param bool $loadSubaccountsUsage 
     * @return Array<\ElasticEmailEnums\Usage>
     */
    public function LoadUsage($from, $to, $loadSubaccountsUsage = true) {
        return $this->sendRequest('account/loadusage', array(
                    'from' => $from,
                    'to' => $to,
                    'loadSubaccountsUsage' => $loadSubaccountsUsage));
    }

    /**
     * Load notifications webhooks
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\Webhook>
     */
    public function LoadWebhook($limit = 0, $offset = 0) {
        return $this->sendRequest('account/loadwebhook', array(
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Load web notification options of your account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\WebNotificationOptions
     */
    public function LoadWebNotificationOptions() {
        return $this->sendRequest('account/loadwebnotificationoptions', array());
    }

    /**
     * Shows summary for your account.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\AccountOverview
     */
    public function Overview() {
        return $this->sendRequest('account/overview', array());
    }

    /**
     * Shows you account's profile basic overview
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\Profile
     */
    public function ProfileOverview() {
        return $this->sendRequest('account/profileoverview', array());
    }

    /**
     * Remove email, template or litmus credits from a sub-account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param \ElasticEmailEnums\CreditType $creditType Type of credits to add (Email or Litmus)
     * @param string $notes Specific notes about the transaction
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to remove credits from. Use subAccountEmail or publicAccountID not both.
     * @param ?int $credits Amount of credits to remove
     * @param bool $removeAll Remove all credits of this type from sub-account (overrides credits if provided)
     */
    public function RemoveSubAccountCredits($creditType, $notes, $subAccountEmail = null, $publicAccountID = null, $credits = null, $removeAll = false) {
        return $this->sendRequest('account/removesubaccountcredits', array(
                    'creditType' => $creditType,
                    'notes' => $notes,
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID,
                    'credits' => $credits,
                    'removeAll' => $removeAll));
    }

    /**
     * Request a new default APIKey.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return string
     */
    public function RequestNewApiKey() {
        return $this->sendRequest('account/requestnewapikey', array());
    }

    /**
     * Request premium support for your account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     */
    public function RequestPremiumSupport() {
        return $this->sendRequest('account/requestpremiumsupport', array());
    }

    /**
     * Request a private IP for your Account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $count Number of items.
     * @param string $notes Free form field of notes
     */
    public function RequestPrivateIP($count, $notes) {
        return $this->sendRequest('account/requestprivateip', array(
                    'count' => $count,
                    'notes' => $notes));
    }

    /**
     * Update sending and tracking options of your account.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?bool $enableClickTracking True, if you want to track clicks. Otherwise, false
     * @param ?bool $enableLinkClickTracking True, if you want to track by link tracking. Otherwise, false
     * @param ?bool $manageSubscriptions True, if you want to display your labels on your unsubscribe form. Otherwise, false
     * @param ?bool $manageSubscribedOnly True, if you want to only display labels that the contact is subscribed to on your unsubscribe form. Otherwise, false
     * @param ?bool $transactionalOnUnsubscribe True, if you want to display an option for the contact to opt into transactional email only on your unsubscribe form. Otherwise, false
     * @param ?bool $skipListUnsubscribe True, if you do not want to use list-unsubscribe headers. Otherwise, false
     * @param ?bool $autoTextFromHtml True, if text BODY of message should be created automatically. Otherwise, false
     * @param ?bool $allowCustomHeaders True, if you want to apply custom headers to your emails. Otherwise, false
     * @param string $bccEmail Email address to send a copy of all email to.
     * @param string $contentTransferEncoding Type of content encoding
     * @param ?bool $emailNotificationForError True, if you want bounce notifications returned. Otherwise, false
     * @param string $emailNotificationEmail Specific email address to send bounce email notifications to.
     * @param ?bool $lowCreditNotification True, if you want to receive low credit email notifications. Otherwise, false
     * @param ?bool $enableUITooltips True, if account has tooltips active. Otherwise, false
     * @param string $notificationsEmails Email addresses to send a copy of all notifications from our system. Separated by semicolon
     * @param string $unsubscribeNotificationsEmails Emails, separated by semicolon, to which the notification about contact unsubscribing should be sent to
     * @param string $logoUrl URL to your logo image.
     * @param ?bool $enableTemplateScripting True, if you want to use template scripting in your emails {{}}. Otherwise, false
     * @param ?int $staleContactScore (0 means this functionality is NOT enabled) Score, depending on the number of times you have sent to a recipient, at which the given recipient should be moved to the Stale status
     * @param ?int $staleContactInactiveDays (0 means this functionality is NOT enabled) Number of days of inactivity for a contact after which the given recipient should be moved to the Stale status
     * @param string $deliveryReason Why your clients are receiving your emails.
     * @param ?bool $tutorialsEnabled True, if you want to enable Dashboard Tutotials
     * @param ?bool $enableOpenTracking True, if you want to track opens. Otherwise, false
     * @param ?bool $consentTrackingOnUnsubscribe 
     * @return \ElasticEmailEnums\AdvancedOptions
     */
    public function UpdateAdvancedOptions($enableClickTracking = null, $enableLinkClickTracking = null, $manageSubscriptions = null, $manageSubscribedOnly = null, $transactionalOnUnsubscribe = null, $skipListUnsubscribe = null, $autoTextFromHtml = null, $allowCustomHeaders = null, $bccEmail = null, $contentTransferEncoding = null, $emailNotificationForError = null, $emailNotificationEmail = null, $lowCreditNotification = null, $enableUITooltips = null, $notificationsEmails = null, $unsubscribeNotificationsEmails = null, $logoUrl = null, $enableTemplateScripting = true, $staleContactScore = null, $staleContactInactiveDays = null, $deliveryReason = null, $tutorialsEnabled = null, $enableOpenTracking = null, $consentTrackingOnUnsubscribe = null) {
        return $this->sendRequest('account/updateadvancedoptions', array(
                    'enableClickTracking' => $enableClickTracking,
                    'enableLinkClickTracking' => $enableLinkClickTracking,
                    'manageSubscriptions' => $manageSubscriptions,
                    'manageSubscribedOnly' => $manageSubscribedOnly,
                    'transactionalOnUnsubscribe' => $transactionalOnUnsubscribe,
                    'skipListUnsubscribe' => $skipListUnsubscribe,
                    'autoTextFromHtml' => $autoTextFromHtml,
                    'allowCustomHeaders' => $allowCustomHeaders,
                    'bccEmail' => $bccEmail,
                    'contentTransferEncoding' => $contentTransferEncoding,
                    'emailNotificationForError' => $emailNotificationForError,
                    'emailNotificationEmail' => $emailNotificationEmail,
                    'lowCreditNotification' => $lowCreditNotification,
                    'enableUITooltips' => $enableUITooltips,
                    'notificationsEmails' => $notificationsEmails,
                    'unsubscribeNotificationsEmails' => $unsubscribeNotificationsEmails,
                    'logoUrl' => $logoUrl,
                    'enableTemplateScripting' => $enableTemplateScripting,
                    'staleContactScore' => $staleContactScore,
                    'staleContactInactiveDays' => $staleContactInactiveDays,
                    'deliveryReason' => $deliveryReason,
                    'tutorialsEnabled' => $tutorialsEnabled,
                    'enableOpenTracking' => $enableOpenTracking,
                    'consentTrackingOnUnsubscribe' => $consentTrackingOnUnsubscribe));
    }

    /**
     * Update settings of your private branding. These settings are needed, if you want to use Elastic Email under your brand.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param bool $enablePrivateBranding True: Turn on or off ability to send mails under your brand. Otherwise, false
     * @param string $logoUrl URL to your logo image.
     * @param string $supportLink Address to your support.
     * @param string $privateBrandingUrl Subdomain for your rebranded service
     * @param string $smtpAddress Address of SMTP server.
     * @param string $smtpAlternative Address of alternative SMTP server.
     * @param string $paymentUrl URL for making payments.
     */
    public function UpdateCustomBranding($enablePrivateBranding = false, $logoUrl = null, $supportLink = null, $privateBrandingUrl = null, $smtpAddress = null, $smtpAlternative = null, $paymentUrl = null) {
        return $this->sendRequest('account/updatecustombranding', array(
                    'enablePrivateBranding' => $enablePrivateBranding,
                    'logoUrl' => $logoUrl,
                    'supportLink' => $supportLink,
                    'privateBrandingUrl' => $privateBrandingUrl,
                    'smtpAddress' => $smtpAddress,
                    'smtpAlternative' => $smtpAlternative,
                    'paymentUrl' => $paymentUrl));
    }

    /**
     * Update inbound notifications options of your account.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?bool $inboundContactsOnly True, if you want inbound email to only process contacts from your account. Otherwise, false
     * @param string $hubCallBackUrl URL used for tracking action of inbound emails
     * @param string $inboundDomain Domain you use as your inbound domain
     * @return \ElasticEmailEnums\InboundOptions
     */
    public function UpdateInboundNotifications($inboundContactsOnly = null, $hubCallBackUrl = "", $inboundDomain = null) {
        return $this->sendRequest('account/updateinboundnotifications', array(
                    'inboundContactsOnly' => $inboundContactsOnly,
                    'hubCallBackUrl' => $hubCallBackUrl,
                    'inboundDomain' => $inboundDomain));
    }

    /**
     * Update your profile.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $firstName First name.
     * @param string $lastName Last name.
     * @param string $address1 First line of address.
     * @param string $city City.
     * @param string $state State or province.
     * @param string $zip Zip/postal code.
     * @param int $countryID Numeric ID of country. A file with the list of countries is available <a href="http://api.elasticemail.com/public/countries"><b>here</b></a>
     * @param ?bool $marketingConsent True if you want to receive newsletters from Elastic Email. Otherwise, false. Empty to leave the current value.
     * @param string $address2 Second line of address.
     * @param string $company Company name.
     * @param string $website HTTP address of your website.
     * @param string $logoUrl URL to your logo image.
     * @param string $taxCode Code used for tax purposes.
     * @param string $phone Phone number
     */
    public function UpdateProfile($firstName, $lastName, $address1, $city, $state, $zip, $countryID, $marketingConsent = null, $address2 = null, $company = null, $website = null, $logoUrl = null, $taxCode = null, $phone = null) {
        return $this->sendRequest('account/updateprofile', array(
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'address1' => $address1,
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                    'countryID' => $countryID,
                    'marketingConsent' => $marketingConsent,
                    'address2' => $address2,
                    'company' => $company,
                    'website' => $website,
                    'logoUrl' => $logoUrl,
                    'taxCode' => $taxCode,
                    'phone' => $phone));
    }

    /**
     * Updates settings of specified subaccount
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param bool $requiresEmailCredits True, if account needs credits to send emails. Otherwise, false
     * @param ?bool $allow2fa 
     * @param int $monthlyRefillCredits Amount of credits added to account automatically
     * @param ?int $dailySendLimit Amount of emails account can send daily
     * @param int $emailSizeLimit Maximum size of email including attachments in MB's
     * @param bool $enablePrivateIPRequest True, if account can request for private IP on its own. Otherwise, false
     * @param int $maxContacts Maximum number of contacts the account can have
     * @param string $subAccountEmail Email address of sub-account
     * @param string $publicAccountID Public key of sub-account to update. Use subAccountEmail or publicAccountID not both.
     * @param ?\ElasticEmailEnums\SendingPermission $sendingPermission Sending permission setting for account
     * @param ?bool $enableContactFeatures True, if you want to use Contact Delivery Tools.  Otherwise, false
     * @param string $poolName Name of your custom IP Pool to be used in the sending process
     */
    public function UpdateSubAccountSettings($requiresEmailCredits = false, $allow2fa = null, $monthlyRefillCredits = 0, $dailySendLimit = null, $emailSizeLimit = 10, $enablePrivateIPRequest = false, $maxContacts = 0, $subAccountEmail = null, $publicAccountID = null, $sendingPermission = null, $enableContactFeatures = null, $poolName = null) {
        return $this->sendRequest('account/updatesubaccountsettings', array(
                    'requiresEmailCredits' => $requiresEmailCredits,
                    'allow2fa' => $allow2fa,
                    'monthlyRefillCredits' => $monthlyRefillCredits,
                    'dailySendLimit' => $dailySendLimit,
                    'emailSizeLimit' => $emailSizeLimit,
                    'enablePrivateIPRequest' => $enablePrivateIPRequest,
                    'maxContacts' => $maxContacts,
                    'subAccountEmail' => $subAccountEmail,
                    'publicAccountID' => $publicAccountID,
                    'sendingPermission' => $sendingPermission,
                    'enableContactFeatures' => $enableContactFeatures,
                    'poolName' => $poolName));
    }

    /**
     * Update notification webhook
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $webhookID 
     * @param string $name Filename
     * @param string $webNotificationUrl URL address to receive web notifications to parse and process.
     * @param ?bool $notifyOncePerEmail 
     * @param ?bool $notificationForSent 
     * @param ?bool $notificationForOpened 
     * @param ?bool $notificationForClicked 
     * @param ?bool $notificationForUnsubscribed 
     * @param ?bool $notificationForAbuseReport 
     * @param ?bool $notificationForError 
     */
    public function UpdateWebhook($webhookID, $name = null, $webNotificationUrl = null, $notifyOncePerEmail = null, $notificationForSent = null, $notificationForOpened = null, $notificationForClicked = null, $notificationForUnsubscribed = null, $notificationForAbuseReport = null, $notificationForError = null) {
        return $this->sendRequest('account/updatewebhook', array(
                    'webhookID' => $webhookID,
                    'name' => $name,
                    'webNotificationUrl' => $webNotificationUrl,
                    'notifyOncePerEmail' => $notifyOncePerEmail,
                    'notificationForSent' => $notificationForSent,
                    'notificationForOpened' => $notificationForOpened,
                    'notificationForClicked' => $notificationForClicked,
                    'notificationForUnsubscribed' => $notificationForUnsubscribed,
                    'notificationForAbuseReport' => $notificationForAbuseReport,
                    'notificationForError' => $notificationForError));
    }

}

/**
 * Sending and monitoring progress of your Campaigns
 */
class Campaign extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Adds a campaign to the queue for processing based on the configuration
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param \ElasticEmailEnums\Campaign $campaign Json representation of a campaign
     * @return int
     */
    public function Add($campaign) {
        return $this->sendRequest('campaign/add', array(
                    'campaign' => $campaign));
    }

    /**
     * Copy selected campaign
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $channelID ID number of selected Channel.
     * @param string $newCampaignName 
     * @return int
     */
    public function EECopy($channelID, $newCampaignName = null) {
        return $this->sendRequest('campaign/copy', array(
                    'channelID' => $channelID,
                    'newCampaignName' => $newCampaignName));
    }

    /**
     * Delete selected campaign
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $channelID ID number of selected Channel.
     */
    public function EEDelete($channelID) {
        return $this->sendRequest('campaign/delete', array(
                    'channelID' => $channelID));
    }

    /**
     * Export selected campaigns to chosen file format.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<int> $channelIDs List of campaign IDs used for processing
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function Export(array $channelIDs = array(), $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('campaign/export', array(
                    'channelIDs' => (count($channelIDs) === 0) ? null : join(';', $channelIDs),
                    'fileFormat' => $fileFormat,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * List all of your campaigns
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $search Text fragment used for searching.
     * @param int $offset How many items should be loaded ahead.
     * @param int $limit Maximum of loaded items.
     * @return Array<\ElasticEmailEnums\CampaignChannel>
     */
    public function EEList($search = null, $offset = 0, $limit = 0) {
        return $this->sendRequest('campaign/list', array(
                    'search' => $search,
                    'offset' => $offset,
                    'limit' => $limit));
    }

    /**
     * Updates a previously added campaign.  Only Active and Paused campaigns can be updated.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param \ElasticEmailEnums\Campaign $campaign Json representation of a campaign
     * @return int
     */
    public function Update($campaign) {
        return $this->sendRequest('campaign/update', array(
                    'campaign' => $campaign));
    }

}

/**
 * SMTP and HTTP API channels for grouping email delivery.
 */
class Channel extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Manually add a channel to your account to group email
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $name Descriptive name of the channel
     * @return string
     */
    public function Add($name) {
        return $this->sendRequest('channel/add', array(
                    'name' => $name));
    }

    /**
     * Delete the channel.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $name The name of the channel to delete.
     */
    public function EEDelete($name) {
        return $this->sendRequest('channel/delete', array(
                    'name' => $name));
    }

    /**
     * Export selected channels to chosen file format.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<string> $channelNames List of channel names used for processing
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function Export($channelNames, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('channel/export', array(
                    'channelNames' => (count($channelNames) === 0) ? null : join(';', $channelNames),
                    'fileFormat' => $fileFormat,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Lists your channels
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\Channel>
     */
    public function EEList($limit = 0, $offset = 0) {
        return $this->sendRequest('channel/list', array(
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Rename an existing channel.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $name The name of the channel to update.
     * @param string $newName The new name for the channel.
     * @return string
     */
    public function Update($name, $newName) {
        return $this->sendRequest('channel/update', array(
                    'name' => $name,
                    'newName' => $newName));
    }

}

/**
 * Methods used to manage your Contacts.
 */
class Contact extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Add a new contact and optionally to one of your lists.  Note that your API KEY is not required for this call.
     * @param string $publicAccountID Public key for limited access to your account such as contact/add so you can use it safely on public websites.
     * @param string $email Proper email address.
     * @param array<string> $publicListID ID code of list
     * @param array<string> $listName Name of your list.
     * @param string $firstName First name.
     * @param string $lastName Last name.
     * @param \ElasticEmailEnums\ContactSource $source Specifies the way of uploading the contact
     * @param string $returnUrl URL to navigate to after account creation
     * @param string $sourceUrl URL from which request was sent.
     * @param string $activationReturnUrl The url to return the contact to after activation.
     * @param string $activationTemplate Custom template to use for sending double opt-in activation emails.
     * @param bool $sendActivation True, if you want to send activation email to this contact. Otherwise, false
     * @param ?DateTime $consentDate Date of consent to send this contact(s) your email. If not provided current date is used for consent.
     * @param string $consentIP IP address of consent to send this contact(s) your email. If not provided your current public IP address is used for consent.
     * @param array<string, string> $field Custom contact field like companyname, customernumber, city etc. Request parameters prefixed by field_ like field_companyname, field_customernumber, field_city
     * @param string $notifyEmail Emails, separated by semicolon, to which the notification about contact subscribing should be sent to
     * @param string $alreadyActiveUrl Url to navigate to if contact already is subscribed
     * @param \ElasticEmailEnums\ConsentTracking $consentTracking 
     * @return string
     */
    public function Add($publicAccountID, $email, array $publicListID = array(), array $listName = array(), $firstName = null, $lastName = null, $source = \ElasticEmailEnums\ContactSource::ContactApi, $returnUrl = null, $sourceUrl = null, $activationReturnUrl = null, $activationTemplate = null, $sendActivation = true, $consentDate = null, $consentIP = null, array $field = array(), $notifyEmail = null, $alreadyActiveUrl = null, $consentTracking = \ElasticEmailEnums\ConsentTracking::Unknown) {
        $arr = array(
                    'publicAccountID' => $publicAccountID,
                    'email' => $email,
                    'publicListID' => (count($publicListID) === 0) ? null : join(';', $publicListID),
                    'listName' => (count($listName) === 0) ? null : join(';', $listName),
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'source' => $source,
                    'returnUrl' => $returnUrl,
                    'sourceUrl' => $sourceUrl,
                    'activationReturnUrl' => $activationReturnUrl,
                    'activationTemplate' => $activationTemplate,
                    'sendActivation' => $sendActivation,
                    'consentDate' => $consentDate,
                    'consentIP' => $consentIP,
                    'notifyEmail' => $notifyEmail,
                    'alreadyActiveUrl' => $alreadyActiveUrl,
                    'consentTracking' => $consentTracking);
        foreach(array_keys($field) as $key) {
            $arr['field_'.$key] = $field[$key]; 
        }
        return $this->sendRequest('contact/add', $arr);
    }

    /**
     * Manually add or update a contacts status to Abuse or Unsubscribed status (blocked).
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @param \ElasticEmailEnums\ContactStatus $status Status of the given resource
     */
    public function AddBlocked($email, $status) {
        return $this->sendRequest('contact/addblocked', array(
                    'email' => $email,
                    'status' => $status));
    }

    /**
     * Change any property on the contact record.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @param string $name Name of the contact property you want to change.
     * @param string $value Value you would like to change the contact property to.
     */
    public function ChangeProperty($email, $name, $value) {
        return $this->sendRequest('contact/changeproperty', array(
                    'email' => $email,
                    'name' => $name,
                    'value' => $value));
    }

    /**
     * Changes status of selected Contacts. You may provide RULE for selection or specify list of Contact IDs.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param \ElasticEmailEnums\ContactStatus $status Status of the given resource
     * @param string $rule Query used for filtering.
     * @param array<string> $emails Comma delimited list of contact emails
     */
    public function ChangeStatus($status, $rule = null, array $emails = array()) {
        return $this->sendRequest('contact/changestatus', array(
                    'status' => $status,
                    'rule' => $rule,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails)));
    }

    /**
     * Returns number of Contacts, RULE specifies contact Status.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $rule Query used for filtering.
     * @return \ElasticEmailEnums\ContactStatusCounts
     */
    public function CountByStatus($rule = null) {
        return $this->sendRequest('contact/countbystatus', array(
                    'rule' => $rule));
    }

    /**
     * Returns count of unsubscribe reasons for unsubscribed and complaint contacts.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @return \ElasticEmailEnums\ContactUnsubscribeReasonCounts
     */
    public function CountByUnsubscribeReason($from = null, $to = null) {
        return $this->sendRequest('contact/countbyunsubscribereason', array(
                    'from' => $from,
                    'to' => $to));
    }

    /**
     * Permanantly deletes the contacts provided.  You can provide either a qualified rule or a list of emails (comma separated string).
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $rule Query used for filtering.
     * @param array<string> $emails Comma delimited list of contact emails
     */
    public function EEDelete($rule = null, array $emails = array()) {
        return $this->sendRequest('contact/delete', array(
                    'rule' => $rule,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails)));
    }

    /**
     * Export selected Contacts to file.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param string $rule Query used for filtering.
     * @param array<string> $emails Comma delimited list of contact emails
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function Export($fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $rule = null, array $emails = array(), $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('contact/export', array(
                    'fileFormat' => $fileFormat,
                    'rule' => $rule,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails),
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Export contacts' unsubscribe reasons count to file.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function ExportUnsubscribeReasonCount($from = null, $to = null, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('contact/exportunsubscribereasoncount', array(
                    'from' => $from,
                    'to' => $to,
                    'fileFormat' => $fileFormat,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Finds all Lists and Segments this email belongs to.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @return \ElasticEmailEnums\ContactCollection
     */
    public function FindContact($email) {
        return $this->sendRequest('contact/findcontact', array(
                    'email' => $email));
    }

    /**
     * List of Contacts for provided List
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\Contact>
     */
    public function GetContactsByList($listName, $limit = 20, $offset = 0) {
        return $this->sendRequest('contact/getcontactsbylist', array(
                    'listName' => $listName,
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * List of Contacts for provided Segment
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $segmentName Name of your segment.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\Contact>
     */
    public function GetContactsBySegment($segmentName, $limit = 20, $offset = 0) {
        return $this->sendRequest('contact/getcontactsbysegment', array(
                    'segmentName' => $segmentName,
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * List of all contacts. If you have not specified RULE, all Contacts will be listed.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $rule Query used for filtering.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param ?\ElasticEmailEnums\ContactSort $sort 
     * @return Array<\ElasticEmailEnums\Contact>
     */
    public function EEList($rule = null, $limit = 20, $offset = 0, $sort = null) {
        return $this->sendRequest('contact/list', array(
                    'rule' => $rule,
                    'limit' => $limit,
                    'offset' => $offset,
                    'sort' => $sort));
    }

    /**
     * Load blocked contacts
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<\ElasticEmailEnums\ContactStatus> $statuses List of blocked statuses: Abuse, Bounced or Unsubscribed
     * @param string $search Text fragment used for searching.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\BlockedContact>
     */
    public function LoadBlocked($statuses, $search = null, $limit = 0, $offset = 0) {
        return $this->sendRequest('contact/loadblocked', array(
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'search' => $search,
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Load detailed contact information
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @return \ElasticEmailEnums\Contact
     */
    public function LoadContact($email) {
        return $this->sendRequest('contact/loadcontact', array(
                    'email' => $email));
    }

    /**
     * Shows detailed history of chosen Contact.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\ContactHistory>
     */
    public function LoadHistory($email, $limit = 0, $offset = 0) {
        return $this->sendRequest('contact/loadhistory', array(
                    'email' => $email,
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Add new Contact to one of your Lists.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<string> $emails Comma delimited list of contact emails
     * @param string $firstName First name.
     * @param string $lastName Last name.
     * @param string $publicListID ID code of list
     * @param string $listName Name of your list.
     * @param \ElasticEmailEnums\ContactStatus $status Status of the given resource
     * @param string $notes Free form field of notes
     * @param ?DateTime $consentDate Date of consent to send this contact(s) your email. If not provided current date is used for consent.
     * @param string $consentIP IP address of consent to send this contact(s) your email. If not provided your current public IP address is used for consent.
     * @param array<string, string> $field Custom contact field like companyname, customernumber, city etc. Request parameters prefixed by field_ like field_companyname, field_customernumber, field_city
     * @param string $notifyEmail Emails, separated by semicolon, to which the notification about contact subscribing should be sent to
     * @param \ElasticEmailEnums\ConsentTracking $consentTracking 
     * @param \ElasticEmailEnums\ContactSource $source Specifies the way of uploading the contact
     */
    public function QuickAdd($emails, $firstName = null, $lastName = null, $publicListID = null, $listName = null, $status = \ElasticEmailEnums\ContactStatus::Active, $notes = null, $consentDate = null, $consentIP = null, array $field = array(), $notifyEmail = null, $consentTracking = \ElasticEmailEnums\ConsentTracking::Unknown, $source = \ElasticEmailEnums\ContactSource::ManualInput) {
        $arr = array(
                    'emails' => (count($emails) === 0) ? null : join(';', $emails),
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'publicListID' => $publicListID,
                    'listName' => $listName,
                    'status' => $status,
                    'notes' => $notes,
                    'consentDate' => $consentDate,
                    'consentIP' => $consentIP,
                    'notifyEmail' => $notifyEmail,
                    'consentTracking' => $consentTracking,
                    'source' => $source);
        foreach(array_keys($field) as $key) {
            $arr['field_'.$key] = $field[$key]; 
        }
        return $this->sendRequest('contact/quickadd', $arr);
    }

    /**
     * Basic double opt-in email subscribe form for your account.  This can be used for contacts that need to re-subscribe as well.
     * @param string $publicAccountID Public key for limited access to your account such as contact/add so you can use it safely on public websites.
     * @return string
     */
    public function Subscribe($publicAccountID) {
        return $this->sendRequest('contact/subscribe', array(
                    'publicAccountID' => $publicAccountID));
    }

    /**
     * Update selected contact. Omitted contact's fields will be reset by default (see the clearRestOfFields parameter)
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $email Proper email address.
     * @param string $firstName First name.
     * @param string $lastName Last name.
     * @param bool $clearRestOfFields States if the fields that were omitted in this request are to be reset or should they be left with their current value
     * @param array<string, string> $field Custom contact field like companyname, customernumber, city etc. Request parameters prefixed by field_ like field_companyname, field_customernumber, field_city
     * @param string $customFields Custom contact field like companyname, customernumber, city etc. JSON serialized text like { "city":"london" } 
     * @return \ElasticEmailEnums\Contact
     */
    public function Update($email, $firstName = null, $lastName = null, $clearRestOfFields = true, array $field = array(), $customFields = null) {
        $arr = array(
                    'email' => $email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'clearRestOfFields' => $clearRestOfFields,
                    'customFields' => $customFields);
        foreach(array_keys($field) as $key) {
            $arr['field_'.$key] = $field[$key]; 
        }
        return $this->sendRequest('contact/update', $arr);
    }

    /**
     * Upload contacts in CSV file.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param File $contactFile Name of CSV file with Contacts.
     * @param bool $allowUnsubscribe True: Allow unsubscribing from this (optional) newly created list. Otherwise, false
     * @param ?int $listID ID number of selected list.
     * @param string $listName Name of your list to upload contacts to, or how the new, automatically created list should be named
     * @param \ElasticEmailEnums\ContactStatus $status Status of the given resource
     * @param ?DateTime $consentDate Date of consent to send this contact(s) your email. If not provided current date is used for consent.
     * @param string $consentIP IP address of consent to send this contact(s) your email. If not provided your current public IP address is used for consent.
     * @param \ElasticEmailEnums\ConsentTracking $consentTracking 
     * @return int
     */
    public function Upload($contactFile, $allowUnsubscribe = false, $listID = null, $listName = null, $status = \ElasticEmailEnums\ContactStatus::Active, $consentDate = null, $consentIP = null, $consentTracking = \ElasticEmailEnums\ConsentTracking::Unknown) {
        return $this->sendRequest('contact/upload', array(
                    'allowUnsubscribe' => $allowUnsubscribe,
                    'listID' => $listID,
                    'listName' => $listName,
                    'status' => $status,
                    'consentDate' => $consentDate,
                    'consentIP' => $consentIP,
                    'consentTracking' => $consentTracking), "POST", $contactFile);
    }

}

/**
 * Managing sender domains. Creating new entries and validating domain records.
 */
class Domain extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Add new domain to account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Name of selected domain.
     * @param \ElasticEmailEnums\TrackingType $trackingType 
     * @param bool $setAsDefault 
     */
    public function Add($domain, $trackingType = \ElasticEmailEnums\TrackingType::Http, $setAsDefault = false) {
        return $this->sendRequest('domain/add', array(
                    'domain' => $domain,
                    'trackingType' => $trackingType,
                    'setAsDefault' => $setAsDefault));
    }

    /**
     * Deletes configured domain from account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Name of selected domain.
     */
    public function EEDelete($domain) {
        return $this->sendRequest('domain/delete', array(
                    'domain' => $domain));
    }

    /**
     * Lists all domains configured for this account.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return Array<\ElasticEmailEnums\DomainDetail>
     */
    public function EEList() {
        return $this->sendRequest('domain/list', array());
    }

    /**
     * Verification of email addres set for domain.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Default email sender, example: mail@yourdomain.com
     */
    public function SetDefault($domain) {
        return $this->sendRequest('domain/setdefault', array(
                    'domain' => $domain));
    }

    /**
     * Verification of DKIM record for domain
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Name of selected domain.
     * @return string
     */
    public function VerifyDkim($domain) {
        return $this->sendRequest('domain/verifydkim', array(
                    'domain' => $domain));
    }

    /**
     * Verification of MX record for domain
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Name of selected domain.
     * @return string
     */
    public function VerifyMX($domain) {
        return $this->sendRequest('domain/verifymx', array(
                    'domain' => $domain));
    }

    /**
     * Verification of SPF record for domain
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Name of selected domain.
     * @return \ElasticEmailEnums\ValidationStatus
     */
    public function VerifySpf($domain) {
        return $this->sendRequest('domain/verifyspf', array(
                    'domain' => $domain));
    }

    /**
     * Verification of tracking CNAME record for domain
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $domain Name of selected domain.
     * @param \ElasticEmailEnums\TrackingType $trackingType 
     * @return string
     */
    public function VerifyTracking($domain, $trackingType = \ElasticEmailEnums\TrackingType::Http) {
        return $this->sendRequest('domain/verifytracking', array(
                    'domain' => $domain,
                    'trackingType' => $trackingType));
    }

}

/**
 * Send your emails and see their statuses
 */
class Email extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Get email batch status
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $transactionID Transaction identifier
     * @param bool $showFailed Include Bounced email addresses.
     * @param bool $showSent Include Sent email addresses.
     * @param bool $showDelivered Include all delivered email addresses.
     * @param bool $showPending Include Ready to send email addresses.
     * @param bool $showOpened Include Opened email addresses.
     * @param bool $showClicked Include Clicked email addresses.
     * @param bool $showAbuse Include Reported as abuse email addresses.
     * @param bool $showUnsubscribed Include Unsubscribed email addresses.
     * @param bool $showErrors Include error messages for bounced emails.
     * @param bool $showMessageIDs Include all MessageIDs for this transaction
     * @return \ElasticEmailEnums\EmailJobStatus
     */
    public function GetStatus($transactionID, $showFailed = false, $showSent = false, $showDelivered = false, $showPending = false, $showOpened = false, $showClicked = false, $showAbuse = false, $showUnsubscribed = false, $showErrors = false, $showMessageIDs = false) {
        return $this->sendRequest('email/getstatus', array(
                    'transactionID' => $transactionID,
                    'showFailed' => $showFailed,
                    'showSent' => $showSent,
                    'showDelivered' => $showDelivered,
                    'showPending' => $showPending,
                    'showOpened' => $showOpened,
                    'showClicked' => $showClicked,
                    'showAbuse' => $showAbuse,
                    'showUnsubscribed' => $showUnsubscribed,
                    'showErrors' => $showErrors,
                    'showMessageIDs' => $showMessageIDs));
    }

    /**
     * Submit emails. The HTTP POST request is suggested. The default, maximum (accepted by us) size of an email is 10 MB in total, with or without attachments included. For suggested implementations please refer to https://elasticemail.com/support/http-api/
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $subject Email subject
     * @param string $from From email address
     * @param string $fromName Display name for from email address
     * @param string $sender Email address of the sender
     * @param string $senderName Display name sender
     * @param string $msgFrom Optional parameter. Sets FROM MIME header.
     * @param string $msgFromName Optional parameter. Sets FROM name of MIME header.
     * @param string $replyTo Email address to reply to
     * @param string $replyToName Display name of the reply to address
     * @param array<string> $to List of email recipients (each email is treated separately, like a BCC). Separated by comma or semicolon. We suggest using the "msgTo" parameter if backward compatibility with API version 1 is not a must.
     * @param array<string> $msgTo Optional parameter. Will be ignored if the 'to' parameter is also provided. List of email recipients (visible to all other recipients of the message as TO MIME header). Separated by comma or semicolon.
     * @param array<string> $msgCC Optional parameter. Will be ignored if the 'to' parameter is also provided. List of email recipients (visible to all other recipients of the message as CC MIME header). Separated by comma or semicolon.
     * @param array<string> $msgBcc Optional parameter. Will be ignored if the 'to' parameter is also provided. List of email recipients (each email is treated seperately). Separated by comma or semicolon.
     * @param array<string> $lists The name of a contact list you would like to send to. Separate multiple contact lists by commas or semicolons.
     * @param array<string> $segments The name of a segment you would like to send to. Separate multiple segments by comma or semicolon. Insert "0" for all Active contacts.
     * @param string $mergeSourceFilename File name one of attachments which is a CSV list of Recipients.
     * @param string $dataSource Name or ID of the previously uploaded file (via the File/Upload request) which should be a CSV list of Recipients.
     * @param string $channel An ID field (max 191 chars) that can be used for reporting [will default to HTTP API or SMTP API]
     * @param string $bodyHtml Html email body
     * @param string $bodyText Text email body
     * @param string $charset Text value of charset encoding for example: iso-8859-1, windows-1251, utf-8, us-ascii, windows-1250 and more…
     * @param string $charsetBodyHtml Sets charset for body html MIME part (overrides default value from charset parameter)
     * @param string $charsetBodyText Sets charset for body text MIME part (overrides default value from charset parameter)
     * @param \ElasticEmailEnums\EncodingType $encodingType 0 for None, 1 for Raw7Bit, 2 for Raw8Bit, 3 for QuotedPrintable, 4 for Base64 (Default), 5 for Uue  note that you can also provide the text version such as "Raw7Bit" for value 1.  NOTE: Base64 or QuotedPrintable is recommended if you are validating your domain(s) with DKIM.
     * @param string $template The ID of an email template you have created in your account.
     * @param array<File> $attachmentFiles Attachment files. These files should be provided with the POST multipart file upload and not directly in the request's URL. Can also include merge CSV file
     * @param array<string, string> $headers Optional Custom Headers. Request parameters prefixed by headers_ like headers_customheader1, headers_customheader2. Note: a space is required after the colon before the custom header value. headers_xmailer=xmailer: header-value1
     * @param string $postBack Optional header returned in notifications.
     * @param array<string, string> $merge Request parameters prefixed by merge_ like merge_firstname, merge_lastname. If sending to a template you can send merge_ fields to merge data with the template. Template fields are entered with {firstname}, {lastname} etc.
     * @param string $timeOffSetMinutes Number of minutes in the future this email should be sent up to a maximum of 1 year (524160 minutes)
     * @param string $poolName Name of your custom IP Pool to be used in the sending process
     * @param bool $isTransactional True, if email is transactional (non-bulk, non-marketing, non-commercial). Otherwise, false
     * @param array<string> $attachments Names or IDs of attachments previously uploaded to your account (via the File/Upload request) that should be sent with this e-mail.
     * @param ?bool $trackOpens Should the opens be tracked? If no value has been provided, account's default setting will be used.
     * @param ?bool $trackClicks Should the clicks be tracked? If no value has been provided, account's default setting will be used.
     * @param string $utmSource The utm_source marketing parameter appended to each link in the campaign.
     * @param string $utmMedium The utm_medium marketing parameter appended to each link in the campaign.
     * @param string $utmCampaign The utm_campaign marketing parameter appended to each link in the campaign.
     * @param string $utmContent The utm_content marketing parameter appended to each link in the campaign.
     * @param string $bodyAmp AMP email body
     * @param string $charsetBodyAmp Sets charset for body AMP MIME part (overrides default value from charset parameter)
     * @return \ElasticEmailEnums\EmailSend
     */
    public function Send($subject = null, $from = null, $fromName = null, $sender = null, $senderName = null, $msgFrom = null, $msgFromName = null, $replyTo = null, $replyToName = null, array $to = array(), array $msgTo = array(), array $msgCC = array(), array $msgBcc = array(), array $lists = array(), array $segments = array(), $mergeSourceFilename = null, $dataSource = null, $channel = null, $bodyHtml = null, $bodyText = null, $charset = null, $charsetBodyHtml = null, $charsetBodyText = null, $encodingType = \ElasticEmailEnums\EncodingType::None, $template = null, array $attachmentFiles = array(), array $headers = array(), $postBack = null, array $merge = array(), $timeOffSetMinutes = null, $poolName = null, $isTransactional = false, array $attachments = array(), $trackOpens = null, $trackClicks = null, $utmSource = null, $utmMedium = null, $utmCampaign = null, $utmContent = null, $bodyAmp = null, $charsetBodyAmp = null) {
        $arr = array(
                    'subject' => $subject,
                    'from' => $from,
                    'fromName' => $fromName,
                    'sender' => $sender,
                    'senderName' => $senderName,
                    'msgFrom' => $msgFrom,
                    'msgFromName' => $msgFromName,
                    'replyTo' => $replyTo,
                    'replyToName' => $replyToName,
                    'to' => (count($to) === 0) ? null : join(';', $to),
                    'msgTo' => (count($msgTo) === 0) ? null : join(';', $msgTo),
                    'msgCC' => (count($msgCC) === 0) ? null : join(';', $msgCC),
                    'msgBcc' => (count($msgBcc) === 0) ? null : join(';', $msgBcc),
                    'lists' => (count($lists) === 0) ? null : join(';', $lists),
                    'segments' => (count($segments) === 0) ? null : join(';', $segments),
                    'mergeSourceFilename' => $mergeSourceFilename,
                    'dataSource' => $dataSource,
                    'channel' => $channel,
                    'bodyHtml' => $bodyHtml,
                    'bodyText' => $bodyText,
                    'charset' => $charset,
                    'charsetBodyHtml' => $charsetBodyHtml,
                    'charsetBodyText' => $charsetBodyText,
                    'encodingType' => $encodingType,
                    'template' => $template,
                    'postBack' => $postBack,
                    'timeOffSetMinutes' => $timeOffSetMinutes,
                    'poolName' => $poolName,
                    'isTransactional' => $isTransactional,
                    'attachments' => (count($attachments) === 0) ? null : join(';', $attachments),
                    'trackOpens' => $trackOpens,
                    'trackClicks' => $trackClicks,
                    'utmSource' => $utmSource,
                    'utmMedium' => $utmMedium,
                    'utmCampaign' => $utmCampaign,
                    'utmContent' => $utmContent,
                    'bodyAmp' => $bodyAmp,
                    'charsetBodyAmp' => $charsetBodyAmp);
        foreach(array_keys($headers) as $key) {
            $arr['headers_'.$key] = $key.': '.$headers[$key]; 
        }
        foreach(array_keys($merge) as $key) {
            $arr['merge_'.$key] = $merge[$key]; 
        }
        return $this->sendRequest('email/send', $arr, "POST", $attachmentFiles);
    }

    /**
     * Detailed status of a unique email sent through your account. Returns a 'Email has expired and the status is unknown.' error, if the email has not been fully processed yet.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $messageID Unique identifier for this email.
     * @return \ElasticEmailEnums\EmailStatus
     */
    public function Status($messageID) {
        return $this->sendRequest('email/status', array(
                    'messageID' => $messageID));
    }

    /**
     * View email
     * @param string $messageID Message identifier
     * @param bool $enableTracking 
     * @return \ElasticEmailEnums\EmailView
     */
    public function View($messageID, $enableTracking = false) {
        return $this->sendRequest('email/view', array(
                    'messageID' => $messageID,
                    'enableTracking' => $enableTracking));
    }

}

/**
 * Manage your exports
 */
class Export extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Check the current status of the export.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param Guid $publicExportID ID of the exported file
     * @return \ElasticEmailEnums\ExportStatus
     */
    public function CheckStatus($publicExportID) {
        return $this->sendRequest('export/checkstatus', array(
                    'publicExportID' => $publicExportID));
    }

    /**
     * Summary of export type counts.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\ExportTypeCounts
     */
    public function CountByType() {
        return $this->sendRequest('export/countbytype', array());
    }

    /**
     * Delete the specified export.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param Guid $publicExportID ID of the exported file
     */
    public function EEDelete($publicExportID) {
        return $this->sendRequest('export/delete', array(
                    'publicExportID' => $publicExportID));
    }

    /**
     * Returns a list of all exported data.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return Array<\ElasticEmailEnums\Export>
     */
    public function EEList($limit = 0, $offset = 0) {
        return $this->sendRequest('export/list', array(
                    'limit' => $limit,
                    'offset' => $offset));
    }

}

/**
 * Manage the files on your account
 */
class File extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Permanently deletes the file from your account
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?int $fileID 
     * @param string $filename Name of your file.
     */
    public function EEDelete($fileID = null, $filename = null) {
        return $this->sendRequest('file/delete', array(
                    'fileID' => $fileID,
                    'filename' => $filename));
    }

    /**
     * Gets content of the chosen File
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $filename Name of your file.
     * @param ?int $fileID 
     * @return File
     */
    public function Download($filename = null, $fileID = null) {
        return $this->sendRequest('file/download', array(
                    'filename' => $filename,
                    'fileID' => $fileID));
    }

    /**
     * Lists your available Attachments in the given email
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $msgID ID number of selected message.
     * @return Array<\ElasticEmailEnums\File>
     */
    public function EEList($msgID) {
        return $this->sendRequest('file/list', array(
                    'msgID' => $msgID));
    }

    /**
     * Lists all your available files
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return Array<\ElasticEmailEnums\File>
     */
    public function ListAll() {
        return $this->sendRequest('file/listall', array());
    }

    /**
     * Gets chosen File
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $filename Name of your file.
     * @return \ElasticEmailEnums\File
     */
    public function Load($filename) {
        return $this->sendRequest('file/load', array(
                    'filename' => $filename));
    }

    /**
     * Uploads selected file to the server using http form upload format (MIME multipart/form-data) or PUT method.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param File $file 
     * @param string $name Filename
     * @param ?int $expiresAfterDays After how many days should the file be deleted.
     * @param bool $throwIfExists 
     * @return \ElasticEmailEnums\File
     */
    public function Upload($file, $name = null, $expiresAfterDays = 35, $throwIfExists = false) {
        return $this->sendRequest('file/upload', array(
                    'name' => $name,
                    'expiresAfterDays' => $expiresAfterDays,
                    'throwIfExists' => $throwIfExists), "POST", $file);
    }

}

/**
 * API methods for managing your Lists
 */
class EEList extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Create new list, based on filtering rule or list of IDs
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param bool $createEmptyList True to create an empty list, otherwise false. Ignores rule and emails parameters if provided.
     * @param bool $allowUnsubscribe True: Allow unsubscribing from this list. Otherwise, false
     * @param string $rule Query used for filtering.
     * @param array<string> $emails Comma delimited list of contact emails
     * @param bool $allContacts True: Include every Contact in your Account. Otherwise, false
     * @return int
     */
    public function Add($listName, $createEmptyList = false, $allowUnsubscribe = false, $rule = null, array $emails = array(), $allContacts = false) {
        return $this->sendRequest('list/add', array(
                    'listName' => $listName,
                    'createEmptyList' => $createEmptyList,
                    'allowUnsubscribe' => $allowUnsubscribe,
                    'rule' => $rule,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails),
                    'allContacts' => $allContacts));
    }

    /**
     * Add existing Contacts to chosen list
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param string $rule Query used for filtering.
     * @param array<string> $emails Comma delimited list of contact emails
     * @param bool $allContacts True: Include every Contact in your Account. Otherwise, false
     */
    public function AddContacts($listName, $rule = null, array $emails = array(), $allContacts = false) {
        return $this->sendRequest('list/addcontacts', array(
                    'listName' => $listName,
                    'rule' => $rule,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails),
                    'allContacts' => $allContacts));
    }

    /**
     * Copy your existing List with the option to provide new settings to it. Some fields, when left empty, default to the source list's settings
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $sourceListName The name of the list you want to copy
     * @param string $newlistName Name of your list if you want to change it.
     * @param ?bool $createEmptyList True to create an empty list, otherwise false. Ignores rule and emails parameters if provided.
     * @param ?bool $allowUnsubscribe True: Allow unsubscribing from this list. Otherwise, false
     * @param string $rule Query used for filtering.
     * @return int
     */
    public function EECopy($sourceListName, $newlistName = null, $createEmptyList = null, $allowUnsubscribe = null, $rule = null) {
        return $this->sendRequest('list/copy', array(
                    'sourceListName' => $sourceListName,
                    'newlistName' => $newlistName,
                    'createEmptyList' => $createEmptyList,
                    'allowUnsubscribe' => $allowUnsubscribe,
                    'rule' => $rule));
    }

    /**
     * Create a new list from the recipients of the given campaign, using the given statuses of Messages
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $campaignID ID of the campaign which recipients you want to copy
     * @param string $listName Name of your list.
     * @param array<\ElasticEmailEnums\LogJobStatus> $statuses Statuses of a campaign's emails you want to include in the new list (but NOT the contacts' statuses)
     */
    public function CreateFromCampaign($campaignID, $listName, array $statuses = array()) {
        return $this->sendRequest('list/createfromcampaign', array(
                    'campaignID' => $campaignID,
                    'listName' => $listName,
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses)));
    }

    /**
     * Create a series of nth selection lists from an existing list or segment
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param int $numberOfLists The number of evenly distributed lists to create.
     * @param bool $excludeBlocked True if you want to exclude contacts that are currently in a blocked status of either unsubscribe, complaint or bounce. Otherwise, false.
     * @param bool $allowUnsubscribe True: Allow unsubscribing from this list. Otherwise, false
     * @param string $rule Query used for filtering.
     * @param bool $allContacts True: Include every Contact in your Account. Otherwise, false
     */
    public function CreateNthSelectionLists($listName, $numberOfLists, $excludeBlocked = true, $allowUnsubscribe = false, $rule = null, $allContacts = false) {
        return $this->sendRequest('list/createnthselectionlists', array(
                    'listName' => $listName,
                    'numberOfLists' => $numberOfLists,
                    'excludeBlocked' => $excludeBlocked,
                    'allowUnsubscribe' => $allowUnsubscribe,
                    'rule' => $rule,
                    'allContacts' => $allContacts));
    }

    /**
     * Create a new list with randomized contacts from an existing list or segment
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param int $count Number of items.
     * @param bool $excludeBlocked True if you want to exclude contacts that are currently in a blocked status of either unsubscribe, complaint or bounce. Otherwise, false.
     * @param bool $allowUnsubscribe True: Allow unsubscribing from this list. Otherwise, false
     * @param string $rule Query used for filtering.
     * @param bool $allContacts True: Include every Contact in your Account. Otherwise, false
     * @return int
     */
    public function CreateRandomList($listName, $count, $excludeBlocked = true, $allowUnsubscribe = false, $rule = null, $allContacts = false) {
        return $this->sendRequest('list/createrandomlist', array(
                    'listName' => $listName,
                    'count' => $count,
                    'excludeBlocked' => $excludeBlocked,
                    'allowUnsubscribe' => $allowUnsubscribe,
                    'rule' => $rule,
                    'allContacts' => $allContacts));
    }

    /**
     * Deletes List and removes all the Contacts from it (does not delete Contacts).
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     */
    public function EEDelete($listName) {
        return $this->sendRequest('list/delete', array(
                    'listName' => $listName));
    }

    /**
     * Exports all the contacts from the provided list
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function Export($listName, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('list/export', array(
                    'listName' => $listName,
                    'fileFormat' => $fileFormat,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Shows all your existing lists
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @return Array<\ElasticEmailEnums\List>
     */
    public function EElist($from = null, $to = null) {
        return $this->sendRequest('list/list', array(
                    'from' => $from,
                    'to' => $to));
    }

    /**
     * Returns detailed information about specific list.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @return \ElasticEmailEnums\List
     */
    public function Load($listName) {
        return $this->sendRequest('list/load', array(
                    'listName' => $listName));
    }

    /**
     * Move selected contacts from one List to another
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $oldListName The name of the list from which the contacts will be copied from
     * @param string $newListName The name of the list to copy the contacts to
     * @param array<string> $emails Comma delimited list of contact emails
     * @param ?bool $moveAll TRUE - moves all contacts; FALSE - moves contacts provided in the 'emails' parameter. This is ignored if the 'statuses' parameter has been provided
     * @param array<\ElasticEmailEnums\ContactStatus> $statuses List of contact statuses which are eligible to move. This ignores the 'moveAll' parameter
     * @param string $rule Query used for filtering.
     */
    public function MoveContacts($oldListName, $newListName, array $emails = array(), $moveAll = null, array $statuses = array(), $rule = null) {
        return $this->sendRequest('list/movecontacts', array(
                    'oldListName' => $oldListName,
                    'newListName' => $newListName,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails),
                    'moveAll' => $moveAll,
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'rule' => $rule));
    }

    /**
     * Remove selected Contacts from your list
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param string $rule Query used for filtering.
     * @param array<string> $emails Comma delimited list of contact emails
     */
    public function RemoveContacts($listName, $rule = null, array $emails = array()) {
        return $this->sendRequest('list/removecontacts', array(
                    'listName' => $listName,
                    'rule' => $rule,
                    'emails' => (count($emails) === 0) ? null : join(';', $emails)));
    }

    /**
     * Update existing list
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $listName Name of your list.
     * @param string $newListName Name of your list if you want to change it.
     * @param bool $allowUnsubscribe True: Allow unsubscribing from this list. Otherwise, false
     */
    public function Update($listName, $newListName = null, $allowUnsubscribe = false) {
        return $this->sendRequest('list/update', array(
                    'listName' => $listName,
                    'newListName' => $newListName,
                    'allowUnsubscribe' => $allowUnsubscribe));
    }

}

/**
 * Methods to check logs of your campaigns
 */
class Log extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Cancels emails that are waiting to be sent.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $channelName Name of selected channel.
     * @param string $transactionID ID number of transaction
     */
    public function CancelInProgress($channelName = null, $transactionID = null) {
        return $this->sendRequest('log/cancelinprogress', array(
                    'channelName' => $channelName,
                    'transactionID' => $transactionID));
    }

    /**
     * Returns log of delivery events filtered by specified parameters.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<\ElasticEmailEnums\LogEventStatus> $statuses List of comma separated message statuses: 0 for all, 1 for ReadyToSend, 2 for InProgress, 4 for Bounced, 5 for Sent, 6 for Opened, 7 for Clicked, 8 for Unsubscribed, 9 for Abuse Report
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param string $channelName Name of selected channel.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @return \ElasticEmailEnums\EventLog
     */
    public function Events(array $statuses = array(), $from = null, $to = null, $channelName = null, $limit = 0, $offset = 0) {
        return $this->sendRequest('log/events', array(
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'from' => $from,
                    'to' => $to,
                    'channelName' => $channelName,
                    'limit' => $limit,
                    'offset' => $offset));
    }

    /**
     * Export email log information to the specified file format.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<\ElasticEmailEnums\LogJobStatus> $statuses List of comma separated message statuses: 0 for all, 1 for ReadyToSend, 2 for InProgress, 4 for Bounced, 5 for Sent, 6 for Opened, 7 for Clicked, 8 for Unsubscribed, 9 for Abuse Report
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param ?DateTime $from Start date.
     * @param ?DateTime $to End date.
     * @param string $channelName Name of selected channel.
     * @param bool $includeEmail True: Search includes emails. Otherwise, false.
     * @param bool $includeSms True: Search includes SMS. Otherwise, false.
     * @param array<\ElasticEmailEnums\MessageCategory> $messageCategory ID of message category
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @param string $email Proper email address.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function Export($statuses, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $from = null, $to = null, $channelName = null, $includeEmail = true, $includeSms = true, array $messageCategory = array(), $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null, $email = null) {
        return $this->sendRequest('log/export', array(
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'fileFormat' => $fileFormat,
                    'from' => $from,
                    'to' => $to,
                    'channelName' => $channelName,
                    'includeEmail' => $includeEmail,
                    'includeSms' => $includeSms,
                    'messageCategory' => (count($messageCategory) === 0) ? null : join(';', $messageCategory),
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName,
                    'email' => $email));
    }

    /**
     * Export delivery events log information to the specified file format.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<\ElasticEmailEnums\LogEventStatus> $statuses List of comma separated message statuses: 0 for all, 1 for ReadyToSend, 2 for InProgress, 4 for Bounced, 5 for Sent, 6 for Opened, 7 for Clicked, 8 for Unsubscribed, 9 for Abuse Report
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param string $channelName Name of selected channel.
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function ExportEvents(array $statuses = array(), $from = null, $to = null, $channelName = null, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('log/exportevents', array(
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'from' => $from,
                    'to' => $to,
                    'channelName' => $channelName,
                    'fileFormat' => $fileFormat,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Export detailed link tracking information to the specified file format.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param string $channelName Name of selected channel.
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function ExportLinkTracking($from, $to, $channelName = null, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $limit = 0, $offset = 0, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('log/exportlinktracking', array(
                    'from' => $from,
                    'to' => $to,
                    'channelName' => $channelName,
                    'fileFormat' => $fileFormat,
                    'limit' => $limit,
                    'offset' => $offset,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Track link clicks
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param string $channelName Name of selected channel.
     * @return \ElasticEmailEnums\LinkTrackingDetails
     */
    public function LinkTracking($from = null, $to = null, $limit = 0, $offset = 0, $channelName = null) {
        return $this->sendRequest('log/linktracking', array(
                    'from' => $from,
                    'to' => $to,
                    'limit' => $limit,
                    'offset' => $offset,
                    'channelName' => $channelName));
    }

    /**
     * Returns logs filtered by specified parameters.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<\ElasticEmailEnums\LogJobStatus> $statuses List of comma separated message statuses: 0 for all, 1 for ReadyToSend, 2 for InProgress, 4 for Bounced, 5 for Sent, 6 for Opened, 7 for Clicked, 8 for Unsubscribed, 9 for Abuse Report
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param string $channelName Name of selected channel.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param bool $includeEmail True: Search includes emails. Otherwise, false.
     * @param bool $includeSms True: Search includes SMS. Otherwise, false.
     * @param array<\ElasticEmailEnums\MessageCategory> $messageCategory ID of message category
     * @param string $email Proper email address.
     * @param string $ipaddress Search for recipients that we sent through this IP address
     * @return \ElasticEmailEnums\Log
     */
    public function Load($statuses, $from = null, $to = null, $channelName = null, $limit = 0, $offset = 0, $includeEmail = true, $includeSms = true, array $messageCategory = array(), $email = null, $ipaddress = null) {
        return $this->sendRequest('log/load', array(
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'from' => $from,
                    'to' => $to,
                    'channelName' => $channelName,
                    'limit' => $limit,
                    'offset' => $offset,
                    'includeEmail' => $includeEmail,
                    'includeSms' => $includeSms,
                    'messageCategory' => (count($messageCategory) === 0) ? null : join(';', $messageCategory),
                    'email' => $email,
                    'ipaddress' => $ipaddress));
    }

    /**
     * Returns notification logs filtered by specified parameters.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<\ElasticEmailEnums\LogJobStatus> $statuses List of comma separated message statuses: 0 for all, 1 for ReadyToSend, 2 for InProgress, 4 for Bounced, 5 for Sent, 6 for Opened, 7 for Clicked, 8 for Unsubscribed, 9 for Abuse Report
     * @param ?DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param array<\ElasticEmailEnums\MessageCategory> $messageCategory ID of message category
     * @param bool $useStatusChangeDate True, if 'from' and 'to' parameters should resolve to the Status Change date. To resolve to the creation date - false
     * @param \ElasticEmailEnums\NotificationType $notificationType 
     * @return \ElasticEmailEnums\Log
     */
    public function LoadNotifications($statuses, $from = null, $to = null, $limit = 0, $offset = 0, array $messageCategory = array(), $useStatusChangeDate = false, $notificationType = \ElasticEmailEnums\NotificationType::All) {
        return $this->sendRequest('log/loadnotifications', array(
                    'statuses' => (count($statuses) === 0) ? null : join(';', $statuses),
                    'from' => $from,
                    'to' => $to,
                    'limit' => $limit,
                    'offset' => $offset,
                    'messageCategory' => (count($messageCategory) === 0) ? null : join(';', $messageCategory),
                    'useStatusChangeDate' => $useStatusChangeDate,
                    'notificationType' => $notificationType));
    }

    /**
     * Loads summary information about activity in chosen date range.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param DateTime $from Starting date for search in YYYY-MM-DDThh:mm:ss format.
     * @param DateTime $to Ending date for search in YYYY-MM-DDThh:mm:ss format.
     * @param string $channelName Name of selected channel.
     * @param \ElasticEmailEnums\IntervalType $interval 'Hourly' for detailed information, 'summary' for daily overview
     * @param string $transactionID ID number of transaction
     * @return \ElasticEmailEnums\LogSummary
     */
    public function Summary($from, $to, $channelName = null, $interval = \ElasticEmailEnums\IntervalType::Summary, $transactionID = null) {
        return $this->sendRequest('log/summary', array(
                    'from' => $from,
                    'to' => $to,
                    'channelName' => $channelName,
                    'interval' => $interval,
                    'transactionID' => $transactionID));
    }

}

/**
 * Manages your segments - dynamically created lists of contacts
 */
class Segment extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Create new segment, based on specified RULE.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $segmentName Name of your segment.
     * @param string $rule Query used for filtering.
     * @return \ElasticEmailEnums\Segment
     */
    public function Add($segmentName, $rule) {
        return $this->sendRequest('segment/add', array(
                    'segmentName' => $segmentName,
                    'rule' => $rule));
    }

    /**
     * Copy your existing Segment with the optional new rule and custom name
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $sourceSegmentName The name of the segment you want to copy
     * @param string $newSegmentName New name of your segment if you want to change it.
     * @param string $rule Query used for filtering.
     * @return \ElasticEmailEnums\Segment
     */
    public function EECopy($sourceSegmentName, $newSegmentName = null, $rule = null) {
        return $this->sendRequest('segment/copy', array(
                    'sourceSegmentName' => $sourceSegmentName,
                    'newSegmentName' => $newSegmentName,
                    'rule' => $rule));
    }

    /**
     * Delete existing segment.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $segmentName Name of your segment.
     */
    public function EEDelete($segmentName) {
        return $this->sendRequest('segment/delete', array(
                    'segmentName' => $segmentName));
    }

    /**
     * Exports all the contacts from the provided segment
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $segmentName Name of your segment.
     * @param \ElasticEmailEnums\ExportFileFormats $fileFormat Format of the exported file
     * @param \ElasticEmailEnums\CompressionFormat $compressionFormat FileResponse compression format. None or Zip.
     * @param string $fileName Name of your file.
     * @return \ElasticEmailEnums\ExportLink
     */
    public function Export($segmentName, $fileFormat = \ElasticEmailEnums\ExportFileFormats::Csv, $compressionFormat = \ElasticEmailEnums\CompressionFormat::None, $fileName = null) {
        return $this->sendRequest('segment/export', array(
                    'segmentName' => $segmentName,
                    'fileFormat' => $fileFormat,
                    'compressionFormat' => $compressionFormat,
                    'fileName' => $fileName));
    }

    /**
     * Lists all your available Segments
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param bool $includeHistory True: Include history of last 30 days. Otherwise, false.
     * @param ?DateTime $from From what date should the segment history be shown. In YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to To what date should the segment history be shown. In YYYY-MM-DDThh:mm:ss format.
     * @return Array<\ElasticEmailEnums\Segment>
     */
    public function EEList($includeHistory = false, $from = null, $to = null) {
        return $this->sendRequest('segment/list', array(
                    'includeHistory' => $includeHistory,
                    'from' => $from,
                    'to' => $to));
    }

    /**
     * Lists your available Segments using the provided names
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<string> $segmentNames Names of segments you want to load. Will load all contacts if left empty or the 'All Contacts' name has been provided
     * @param bool $includeHistory True: Include history of last 30 days. Otherwise, false.
     * @param ?DateTime $from From what date should the segment history be shown. In YYYY-MM-DDThh:mm:ss format.
     * @param ?DateTime $to To what date should the segment history be shown. In YYYY-MM-DDThh:mm:ss format.
     * @return Array<\ElasticEmailEnums\Segment>
     */
    public function LoadByName($segmentNames, $includeHistory = false, $from = null, $to = null) {
        return $this->sendRequest('segment/loadbyname', array(
                    'segmentNames' => (count($segmentNames) === 0) ? null : join(';', $segmentNames),
                    'includeHistory' => $includeHistory,
                    'from' => $from,
                    'to' => $to));
    }

    /**
     * Rename or change RULE for your segment
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $segmentName Name of your segment.
     * @param string $newSegmentName New name of your segment if you want to change it.
     * @param string $rule Query used for filtering.
     * @return \ElasticEmailEnums\Segment
     */
    public function Update($segmentName, $newSegmentName = null, $rule = null) {
        return $this->sendRequest('segment/update', array(
                    'segmentName' => $segmentName,
                    'newSegmentName' => $newSegmentName,
                    'rule' => $rule));
    }

}

/**
 * Managing texting to your clients.
 */
class SMS extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Send a short SMS Message (maximum of 1600 characters) to any mobile phone.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $to Mobile number you want to message. Can be any valid mobile number in E.164 format. To provide the country code you need to provide "+" before the number.  If your URL is not encoded then you need to replace the "+" with "%2B" instead.
     * @param string $body Body of your message. The maximum body length is 160 characters.  If the message body is greater than 160 characters it is split into multiple messages and you are charged per message for the number of message required to send your length
     */
    public function Send($to, $body) {
        return $this->sendRequest('sms/send', array(
                    'to' => $to,
                    'body' => $body));
    }

}

/**
 * Managing and editing templates of your emails
 */
class Template extends \ElasticEmailClient\ElasticRequest
{
    public function __construct(\ElasticEmailClient\ApiConfiguration $apiConfiguration)
    {
        parent::__construct($apiConfiguration);
    }
    /**
     * Create new Template. Needs to be sent using POST method
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $name Filename
     * @param string $subject Default subject of email.
     * @param string $fromEmail Default From: email address.
     * @param string $fromName Default From: name.
     * @param \ElasticEmailEnums\TemplateScope $templateScope Enum: 0 - private, 1 - public, 2 - mockup
     * @param string $bodyHtml HTML code of email (needs escaping).
     * @param string $bodyText Text body of email.
     * @param string $css CSS style
     * @param int $originalTemplateID ID number of original template.
     * @param array<string> $tags 
     * @param string $bodyAmp AMP code of email (needs escaping).
     * @return int
     */
    public function Add($name, $subject, $fromEmail, $fromName, $templateScope = \ElasticEmailEnums\TemplateScope::EEPrivate, $bodyHtml = null, $bodyText = null, $css = null, $originalTemplateID = 0, array $tags = array(), $bodyAmp = null) {
        return $this->sendRequest('template/add', array(
                    'name' => $name,
                    'subject' => $subject,
                    'fromEmail' => $fromEmail,
                    'fromName' => $fromName,
                    'templateScope' => $templateScope,
                    'bodyHtml' => $bodyHtml,
                    'bodyText' => $bodyText,
                    'css' => $css,
                    'originalTemplateID' => $originalTemplateID,
                    'tags' => (count($tags) === 0) ? null : join(';', $tags),
                    'bodyAmp' => $bodyAmp));
    }

    /**
     * Create a new Tag to be used in your Templates
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $tag Tag's value
     * @return \ElasticEmailEnums\TemplateTag
     */
    public function AddTag($tag) {
        return $this->sendRequest('template/addtag', array(
                    'tag' => $tag));
    }

    /**
     * Check if template is used by campaign.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $templateID ID number of template.
     * @return bool
     */
    public function CheckUsage($templateID) {
        return $this->sendRequest('template/checkusage', array(
                    'templateID' => $templateID));
    }

    /**
     * Copy Selected Template
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $templateID ID number of template.
     * @param string $name Filename
     * @param string $subject Default subject of email.
     * @param string $fromEmail Default From: email address.
     * @param string $fromName Default From: name.
     * @return \ElasticEmailEnums\Template
     */
    public function EECopy($templateID, $name, $subject, $fromEmail, $fromName) {
        return $this->sendRequest('template/copy', array(
                    'templateID' => $templateID,
                    'name' => $name,
                    'subject' => $subject,
                    'fromEmail' => $fromEmail,
                    'fromName' => $fromName));
    }

    /**
     * Delete template with the specified ID
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $templateID ID number of template.
     */
    public function EEDelete($templateID) {
        return $this->sendRequest('template/delete', array(
                    'templateID' => $templateID));
    }

    /**
     * Delete templates with the specified ID
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<int> $templateIDs 
     */
    public function DeleteBulk($templateIDs) {
        return $this->sendRequest('template/deletebulk', array(
                    'templateIDs' => (count($templateIDs) === 0) ? null : join(';', $templateIDs)));
    }

    /**
     * Delete a tag, removing it from all Templates
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param string $tag 
     */
    public function DeleteTag($tag) {
        return $this->sendRequest('template/deletetag', array(
                    'tag' => $tag));
    }

    /**
     * Lists your templates, optionally searching by Tags
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $limit Maximum of loaded items.
     * @param int $offset How many items should be loaded ahead.
     * @param array<string> $tags If provided, returns templates with these tags
     * @return \ElasticEmailEnums\TemplateList
     */
    public function GetList($limit = 500, $offset = 0, array $tags = array()) {
        return $this->sendRequest('template/getlist', array(
                    'limit' => $limit,
                    'offset' => $offset,
                    'tags' => (count($tags) === 0) ? null : join(';', $tags)));
    }

    /**
     * Retrieve a list of your Tags
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @return \ElasticEmailEnums\TemplateTagList
     */
    public function GetTagList() {
        return $this->sendRequest('template/gettaglist', array());
    }

    /**
     * Load template with content
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $templateID ID number of template.
     * @return \ElasticEmailEnums\Template
     */
    public function LoadTemplate($templateID) {
        return $this->sendRequest('template/loadtemplate', array(
                    'templateID' => $templateID));
    }

    /**
     * Update existing template, overwriting existing data. Needs to be sent using POST method.
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param int $templateID ID number of template.
     * @param \ElasticEmailEnums\TemplateScope $templateScope Enum: 0 - private, 1 - public, 2 - mockup
     * @param string $name Filename
     * @param string $subject Default subject of email.
     * @param string $fromEmail Default From: email address.
     * @param string $fromName Default From: name.
     * @param string $bodyHtml HTML code of email (needs escaping).
     * @param string $bodyText Text body of email.
     * @param string $css CSS style
     * @param bool $removeScreenshot 
     * @param array<string> $tags 
     * @param string $bodyAmp AMP code of email (needs escaping).
     */
    public function Update($templateID, $templateScope = \ElasticEmailEnums\TemplateScope::EEPrivate, $name = null, $subject = null, $fromEmail = null, $fromName = null, $bodyHtml = null, $bodyText = null, $css = null, $removeScreenshot = true, array $tags = array(), $bodyAmp = null) {
        return $this->sendRequest('template/update', array(
                    'templateID' => $templateID,
                    'templateScope' => $templateScope,
                    'name' => $name,
                    'subject' => $subject,
                    'fromEmail' => $fromEmail,
                    'fromName' => $fromName,
                    'bodyHtml' => $bodyHtml,
                    'bodyText' => $bodyText,
                    'css' => $css,
                    'removeScreenshot' => $removeScreenshot,
                    'tags' => (count($tags) === 0) ? null : join(';', $tags),
                    'bodyAmp' => $bodyAmp));
    }

    /**
     * Bulk change default options and the scope of your templates
     * @param string $apikey ApiKey that gives you access to our SMTP and HTTP API's.
     * @param array<int> $templateIDs 
     * @param string $subject Default subject of email.
     * @param string $fromEmail Default From: email address.
     * @param string $fromName Default From: name.
     * @param \ElasticEmailEnums\TemplateScope $templateScope Enum: 0 - private, 1 - public, 2 - mockup
     */
    public function UpdateDefaultOptions($templateIDs, $subject = null, $fromEmail = null, $fromName = null, $templateScope = \ElasticEmailEnums\TemplateScope::EEPrivate) {
        return $this->sendRequest('template/updatedefaultoptions', array(
                    'templateIDs' => (count($templateIDs) === 0) ? null : join(';', $templateIDs),
                    'subject' => $subject,
                    'fromEmail' => $fromEmail,
                    'fromName' => $fromName,
                    'templateScope' => $templateScope));
    }

}

}

namespace ElasticEmailEnums {

/**
 * 
 * Enum class
 */
abstract class AccessLevel
{
    /**
     * 
     */
    const None = 0;

    /**
     * 
     */
    const ViewAccount = 1;

    /**
     * 
     */
    const ViewContacts = 2;

    /**
     * 
     */
    const ViewForms = 4;

    /**
     * 
     */
    const ViewTemplates = 8;

    /**
     * 
     */
    const ViewCampaigns = 16;

    /**
     * 
     */
    const ViewChannels = 32;

    /**
     * 
     */
    const ViewAutomations = 64;

    /**
     * 
     */
    const ViewSurveys = 128;

    /**
     * 
     */
    const ViewSettings = 256;

    /**
     * 
     */
    const ViewBilling = 512;

    /**
     * 
     */
    const ViewSubAccounts = 1024;

    /**
     * 
     */
    const ViewUsers = 2048;

    /**
     * 
     */
    const ViewFiles = 4096;

    /**
     * 
     */
    const ViewReports = 8192;

    /**
     * 
     */
    const ModifyAccount = 16384;

    /**
     * 
     */
    const ModifyContacts = 32768;

    /**
     * 
     */
    const ModifyForms = 65536;

    /**
     * 
     */
    const ModifyTemplates = 131072;

    /**
     * 
     */
    const ModifyCampaigns = 262144;

    /**
     * 
     */
    const ModifyChannels = 524288;

    /**
     * 
     */
    const ModifyAutomations = 1048576;

    /**
     * 
     */
    const ModifySurveys = 2097152;

    /**
     * 
     */
    const ModifyFiles = 4194304;

    /**
     * 
     */
    const Export = 8388608;

    /**
     * 
     */
    const SendSmtp = 16777216;

    /**
     * 
     */
    const SendSMS = 33554432;

    /**
     * 
     */
    const ModifySettings = 67108864;

    /**
     * 
     */
    const ModifyBilling = 134217728;

    /**
     * 
     */
    const ModifyProfile = 268435456;

    /**
     * 
     */
    const ModifySubAccounts = 536870912;

    /**
     * 
     */
    const ModifyUsers = 1073741824;

    /**
     * 
     */
    const Security = 2147483648;

    /**
     * 
     */
    const ModifyLanguage = 4294967296;

    /**
     * 
     */
    const ViewSupport = 8589934592;

    /**
     * 
     */
    const SendHttp = 17179869184;

    /**
     * 
     */
    const Modify2FA = 34359738368;

    /**
     * 
     */
    const ModifySupport = 68719476736;

    /**
     * 
     */
    const ViewCustomFields = 137438953472;

    /**
     * 
     */
    const ModifyCustomFields = 274877906944;

    /**
     * 
     */
    const ModifyWebNotifications = 549755813888;

    /**
     * 
     */
    const ExtendedLogs = 1099511627776;

}

/**
 * 
 */
class AccessToken
{
    /**
     * Access which this Token grants
     */
    public /*\ElasticEmailEnums\AccessLevel*/ $AccessLevel;

    /**
     * Filename
     */
    public /*string*/ $Name;

    /**
     * When was this AccessToken used last
     */
    public /*?DateTime*/ $LastUse;

}

/**
 * Detailed information about your account
 */
class Account
{
    /**
     * Code used for tax purposes.
     */
    public /*string*/ $TaxCode;

    /**
     * Public key for limited access to your account such as contact/add so you can use it safely on public websites.
     */
    public /*string*/ $PublicAccountID;

    /**
     * ApiKey that gives you access to our SMTP and HTTP API's.
     */
    public /*string*/ $ApiKey;

    /**
     * True, if account is a subaccount. Otherwise, false
     */
    public /*bool*/ $IsSub;

    /**
     * 
     */
    public /*bool*/ $IsUser;

    /**
     * The number of subaccounts this account has.
     */
    public /*long*/ $SubAccountsCount;

    /**
     * Number of status: 1 - Active
     */
    public /*int*/ $StatusNumber;

    /**
     * Account status: Active
     */
    public /*string*/ $StatusFormatted;

    /**
     * URL form for payments.
     */
    public /*string*/ $PaymentFormUrl;

    /**
     * URL to your logo image.
     */
    public /*string*/ $LogoUrl;

    /**
     * HTTP address of your website.
     */
    public /*string*/ $Website;

    /**
     * True: Turn on or off ability to send mails under your brand. Otherwise, false
     */
    public /*bool*/ $EnablePrivateBranding;

    /**
     * Address to your support.
     */
    public /*string*/ $SupportLink;

    /**
     * Subdomain for your rebranded service
     */
    public /*string*/ $PrivateBrandingUrl;

    /**
     * First name.
     */
    public /*string*/ $FirstName;

    /**
     * Last name.
     */
    public /*string*/ $LastName;

    /**
     * Company name.
     */
    public /*string*/ $Company;

    /**
     * First line of address.
     */
    public /*string*/ $Address1;

    /**
     * Second line of address.
     */
    public /*string*/ $Address2;

    /**
     * City.
     */
    public /*string*/ $City;

    /**
     * State or province.
     */
    public /*string*/ $State;

    /**
     * Zip/postal code.
     */
    public /*string*/ $Zip;

    /**
     * Numeric ID of country. A file with the list of countries is available <a href="http://api.elasticemail.com/public/countries"><b>here</b></a>
     */
    public /*?int*/ $CountryID;

    /**
     * Phone number
     */
    public /*string*/ $Phone;

    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * URL for affiliating.
     */
    public /*string*/ $AffiliateLink;

    /**
     * Numeric reputation
     */
    public /*double*/ $Reputation;

    /**
     * Amount of emails sent from this account
     */
    public /*long*/ $TotalEmailsSent;

    /**
     * 
     */
    public /*long*/ $SubaccountTotalEmailsSent;

    /**
     * Amount of emails sent from this account
     */
    public /*?long*/ $MonthlyEmailsSent;

    /**
     * Amount of emails sent from this account
     */
    public /*decimal*/ $Credit;

    /**
     * Amount of email credits
     */
    public /*int*/ $EmailCredits;

    /**
     * Amount of emails sent from this account
     */
    public /*decimal*/ $PricePerEmail;

    /**
     * Why your clients are receiving your emails.
     */
    public /*string*/ $DeliveryReason;

    /**
     * URL for making payments.
     */
    public /*string*/ $AccountPaymentUrl;

    /**
     * Address of SMTP server.
     */
    public /*string*/ $Smtp;

    /**
     * Address of alternative SMTP server.
     */
    public /*string*/ $SmtpAlternative;

    /**
     * Status of automatic payments configuration.
     */
    public /*string*/ $AutoCreditStatus;

    /**
     * When AutoCreditStatus is Enabled, the credit level that triggers the credit to be recharged.
     */
    public /*decimal*/ $AutoCreditLevel;

    /**
     * When AutoCreditStatus is Enabled, the amount of credit to be recharged.
     */
    public /*decimal*/ $AutoCreditAmount;

    /**
     * Amount of emails account can send daily
     */
    public /*int*/ $DailySendLimit;

    /**
     * Creation date.
     */
    public /*DateTime*/ $DateCreated;

    /**
     * True, if you have enabled link tracking. Otherwise, false
     */
    public /*bool*/ $LinkTracking;

    /**
     * Type of content encoding
     */
    public /*string*/ $ContentTransferEncoding;

    /**
     * Enable contact delivery and optimization tools on your Account.
     */
    public /*bool*/ $EnableContactFeatures;

    /**
     * 
     */
    public /*bool*/ $NeedsSMSVerification;

    /**
     * 
     */
    public /*bool*/ $DisableGlobalContacts;

    /**
     * 
     */
    public /*bool*/ $UntrustedDeviceAlertDisabled;

}

/**
 * Basic overview of your account
 */
class AccountOverview
{
    /**
     * Amount of emails sent from this account
     */
    public /*long*/ $TotalEmailsSent;

    /**
     * Amount of emails sent from this account
     */
    public /*decimal*/ $Credit;

    /**
     * Cost of 1000 emails
     */
    public /*decimal*/ $CostPerThousand;

    /**
     * Number of messages in progress
     */
    public /*long*/ $InProgressCount;

    /**
     * Number of contacts currently with blocked status of Unsubscribed, Complaint, Bounced or InActive
     */
    public /*long*/ $BlockedContactsCount;

    /**
     * Numeric reputation
     */
    public /*double*/ $Reputation;

    /**
     * Number of contacts
     */
    public /*long*/ $ContactCount;

    /**
     * Number of created campaigns
     */
    public /*long*/ $CampaignCount;

    /**
     * Number of available templates
     */
    public /*long*/ $TemplateCount;

    /**
     * Number of created subaccounts
     */
    public /*long*/ $SubAccountCount;

    /**
     * Number of active referrals
     */
    public /*long*/ $ReferralCount;

}

/**
 * Lists advanced sending options of your account.
 */
class AdvancedOptions
{
    /**
     * True, if you want to track clicks. Otherwise, false
     */
    public /*bool*/ $EnableClickTracking;

    /**
     * True, if you want to track by link tracking. Otherwise, false
     */
    public /*bool*/ $EnableLinkClickTracking;

    /**
     * True, if you want to use template scripting in your emails {{}}. Otherwise, false
     */
    public /*bool*/ $EnableTemplateScripting;

    /**
     * True, if text BODY of message should be created automatically. Otherwise, false
     */
    public /*bool*/ $AutoTextFormat;

    /**
     * True, if you want bounce notifications returned. Otherwise, false
     */
    public /*bool*/ $EmailNotificationForError;

    /**
     * True, if you want to receive low credit email notifications. Otherwise, false
     */
    public /*bool*/ $LowCreditNotification;

    /**
     * True, if this account is a sub-account. Otherwise, false
     */
    public /*bool*/ $IsSubAccount;

    /**
     * True, if this account resells Elastic Email. Otherwise, false.
     */
    public /*bool*/ $IsOwnedByReseller;

    /**
     * True, if you want to enable list-unsubscribe header. Otherwise, false
     */
    public /*bool*/ $EnableUnsubscribeHeader;

    /**
     * True, if you want to display your labels on your unsubscribe form. Otherwise, false
     */
    public /*bool*/ $ManageSubscriptions;

    /**
     * True, if you want to only display labels that the contact is subscribed to on your unsubscribe form. Otherwise, false
     */
    public /*bool*/ $ManageSubscribedOnly;

    /**
     * True, if you want to display an option for the contact to opt into transactional email only on your unsubscribe form. Otherwise, false
     */
    public /*bool*/ $TransactionalOnUnsubscribe;

    /**
     * 
     */
    public /*bool*/ $ConsentTrackingOnUnsubscribe;

    /**
     * 
     */
    public /*string*/ $PreviewMessageID;

    /**
     * True, if you want to apply custom headers to your emails. Otherwise, false
     */
    public /*bool*/ $AllowCustomHeaders;

    /**
     * Email address to send a copy of all email to.
     */
    public /*string*/ $BccEmail;

    /**
     * Type of content encoding
     */
    public /*string*/ $ContentTransferEncoding;

    /**
     * True, if you want to receive bounce email notifications. Otherwise, false
     */
    public /*string*/ $EmailNotification;

    /**
     * Email addresses to send a copy of all notifications from our system. Separated by semicolon
     */
    public /*string*/ $NotificationsEmails;

    /**
     * Emails, separated by semicolon, to which the notification about contact unsubscribing should be sent to
     */
    public /*string*/ $UnsubscribeNotificationEmails;

    /**
     * True, if account has tooltips active. Otherwise, false
     */
    public /*bool*/ $EnableUITooltips;

    /**
     * True, if you want to use Contact Delivery Tools.  Otherwise, false
     */
    public /*bool*/ $EnableContactFeatures;

    /**
     * URL to your logo image.
     */
    public /*string*/ $LogoUrl;

    /**
     * (0 means this functionality is NOT enabled) Score, depending on the number of times you have sent to a recipient, at which the given recipient should be moved to the Stale status
     */
    public /*int*/ $StaleContactScore;

    /**
     * (0 means this functionality is NOT enabled) Number of days of inactivity for a contact after which the given recipient should be moved to the Stale status
     */
    public /*int*/ $StaleContactInactiveDays;

    /**
     * Why your clients are receiving your emails.
     */
    public /*string*/ $DeliveryReason;

    /**
     * True, if you want to enable Dashboard Tutotials
     */
    public /*?bool*/ $TutorialsEnabled;

}

/**
 * Blocked Contact - Contact returning Hard Bounces
 */
class BlockedContact
{
    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * Status of the given resource
     */
    public /*string*/ $Status;

    /**
     * RFC error message
     */
    public /*string*/ $FriendlyErrorMessage;

    /**
     * Last change date
     */
    public /*string*/ $DateUpdated;

}

/**
 * Summary of bounced categories, based on specified date range.
 */
class BouncedCategorySummary
{
    /**
     * Number of messages marked as SPAM
     */
    public /*long*/ $Spam;

    /**
     * Number of blacklisted messages
     */
    public /*long*/ $BlackListed;

    /**
     * Number of messages flagged with 'No Mailbox'
     */
    public /*long*/ $NoMailbox;

    /**
     * Number of messages flagged with 'Grey Listed'
     */
    public /*long*/ $GreyListed;

    /**
     * Number of messages flagged with 'Throttled'
     */
    public /*long*/ $Throttled;

    /**
     * Number of messages flagged with 'Timeout'
     */
    public /*long*/ $Timeout;

    /**
     * Number of messages flagged with 'Connection Problem'
     */
    public /*long*/ $ConnectionProblem;

    /**
     * Number of messages flagged with 'SPF Problem'
     */
    public /*long*/ $SpfProblem;

    /**
     * Number of messages flagged with 'Account Problem'
     */
    public /*long*/ $AccountProblem;

    /**
     * Number of messages flagged with 'DNS Problem'
     */
    public /*long*/ $DnsProblem;

    /**
     * Number of messages flagged with 'WhiteListing Problem'
     */
    public /*long*/ $WhitelistingProblem;

    /**
     * Number of messages flagged with 'Code Error'
     */
    public /*long*/ $CodeError;

    /**
     * Number of messages flagged with 'Not Delivered'
     */
    public /*long*/ $NotDelivered;

    /**
     * Number of manually cancelled messages
     */
    public /*long*/ $ManualCancel;

    /**
     * Number of messages flagged with 'Connection terminated'
     */
    public /*long*/ $ConnectionTerminated;

}

/**
 * Campaign
 */
class Campaign
{
    /**
     * ID number of selected Channel.
     */
    public /*?int*/ $ChannelID;

    /**
     * Campaign's name
     */
    public /*string*/ $Name;

    /**
     * Name of campaign's status
     */
    public /*\ElasticEmailEnums\CampaignStatus*/ $Status;

    /**
     * List of Segment and List IDs, preceded with 'l' for Lists and 's' for Segments, comma separated
     */
    public /*Array<string>*/ $Targets;

    /**
     * Number of event, triggering mail sending
     */
    public /*\ElasticEmailEnums\CampaignTriggerType*/ $TriggerType;

    /**
     * Date of triggered send
     */
    public /*?DateTime*/ $TriggerDate;

    /**
     * How far into the future should the campaign be sent, in minutes
     */
    public /*double*/ $TriggerDelay;

    /**
     * When your next automatic mail will be sent, in minutes
     */
    public /*double*/ $TriggerFrequency;

    /**
     * How many times should the campaign be sent
     */
    public /*int*/ $TriggerCount;

    /**
     * Which Channel's event should trigger this Campaign
     */
    public /*?int*/ $TriggerChannelID;

    /**
     * 
     */
    public /*string*/ $TriggerChannelName;

    /**
     * Data for filtering event campaigns such as specific link addresses.
     */
    public /*string*/ $TriggerData;

    /**
     * What should be checked for choosing the winner: opens or clicks
     */
    public /*\ElasticEmailEnums\SplitOptimization*/ $SplitOptimization;

    /**
     * Number of minutes between sends during optimization period
     */
    public /*int*/ $SplitOptimizationMinutes;

    /**
     * 
     */
    public /*int*/ $TimingOption;

    /**
     * Should the opens be tracked? If no value has been provided, account's default setting will be used.
     */
    public /*?bool*/ $TrackOpens;

    /**
     * Should the clicks be tracked? If no value has been provided, account's default setting will be used.
     */
    public /*?bool*/ $TrackClicks;

    /**
     * 
     */
    public /*Array<\ElasticEmailEnums\CampaignTemplate>*/ $CampaignTemplates;

}

/**
 * Channel
 */
class CampaignChannel
{
    /**
     * ID number of selected Channel.
     */
    public /*int*/ $ChannelID;

    /**
     * Filename
     */
    public /*string*/ $Name;

    /**
     * True, if you are sending a campaign. Otherwise, false.
     */
    public /*bool*/ $IsCampaign;

    /**
     * Name of your custom IP Pool to be used in the sending process
     */
    public /*string*/ $PoolName;

    /**
     * Date of creation in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $DateAdded;

    /**
     * Name of campaign's status
     */
    public /*\ElasticEmailEnums\CampaignStatus*/ $Status;

    /**
     * Date of last activity on account
     */
    public /*?DateTime*/ $LastActivity;

    /**
     * Datetime of last action done on campaign.
     */
    public /*?DateTime*/ $LastProcessed;

    /**
     * Id number of parent channel
     */
    public /*int*/ $ParentChannelID;

    /**
     * 
     */
    public /*string*/ $ParentChannelName;

    /**
     * List of Segment and List IDs, preceded with 'l' for Lists and 's' for Segments, comma separated
     */
    public /*Array<string>*/ $Targets;

    /**
     * Number of event, triggering mail sending
     */
    public /*\ElasticEmailEnums\CampaignTriggerType*/ $TriggerType;

    /**
     * Date of triggered send
     */
    public /*?DateTime*/ $TriggerDate;

    /**
     * How far into the future should the campaign be sent, in minutes
     */
    public /*double*/ $TriggerDelay;

    /**
     * When your next automatic mail will be sent, in minutes
     */
    public /*double*/ $TriggerFrequency;

    /**
     * How many times should the campaign be sent
     */
    public /*int*/ $TriggerCount;

    /**
     * Which Channel's event should trigger this Campaign
     */
    public /*int*/ $TriggerChannelID;

    /**
     * 
     */
    public /*string*/ $TriggerChannelName;

    /**
     * Data for filtering event campaigns such as specific link addresses.
     */
    public /*string*/ $TriggerData;

    /**
     * What should be checked for choosing the winner: opens or clicks
     */
    public /*\ElasticEmailEnums\SplitOptimization*/ $SplitOptimization;

    /**
     * Number of minutes between sends during optimization period
     */
    public /*int*/ $SplitOptimizationMinutes;

    /**
     * 
     */
    public /*int*/ $TimingOption;

    /**
     * ID number of template.
     */
    public /*?int*/ $TemplateID;

    /**
     * Name of template.
     */
    public /*string*/ $TemplateName;

    /**
     * Default subject of email.
     */
    public /*string*/ $TemplateSubject;

    /**
     * Default From: email address.
     */
    public /*string*/ $TemplateFromEmail;

    /**
     * Default From: name.
     */
    public /*string*/ $TemplateFromName;

    /**
     * Default Reply: email address.
     */
    public /*string*/ $TemplateReplyEmail;

    /**
     * Default Reply: name.
     */
    public /*string*/ $TemplateReplyName;

    /**
     * Total emails clicked
     */
    public /*int*/ $ClickedCount;

    /**
     * Total emails opened.
     */
    public /*int*/ $OpenedCount;

    /**
     * Overall number of recipients
     */
    public /*int*/ $RecipientCount;

    /**
     * Total emails sent.
     */
    public /*int*/ $SentCount;

    /**
     * Total emails failed.
     */
    public /*int*/ $FailedCount;

    /**
     * Total emails unsubscribed
     */
    public /*int*/ $UnsubscribedCount;

    /**
     * Abuses - mails sent to user without their consent
     */
    public /*int*/ $FailedAbuse;

    /**
     * List of CampaignTemplate for sending A-X split testing.
     */
    public /*Array<\ElasticEmailEnums\CampaignChannel>*/ $TemplateChannels;

    /**
     * Should the opens be tracked? If no value has been provided, account's default setting will be used.
     */
    public /*?bool*/ $TrackOpens;

    /**
     * Should the clicks be tracked? If no value has been provided, account's default setting will be used.
     */
    public /*?bool*/ $TrackClicks;

    /**
     * The utm_source marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmSource;

    /**
     * The utm_medium marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmMedium;

    /**
     * The utm_campaign marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmCampaign;

    /**
     * The utm_content marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmContent;

}

/**
 * 
 * Enum class
 */
abstract class CampaignStatus
{
    /**
     * Campaign is logically deleted and not returned by API or interface calls.
     */
    const Deleted = -1;

    /**
     * Campaign is curently active and available.
     */
    const Active = 0;

    /**
     * Campaign is currently being processed for delivery.
     */
    const Processing = 1;

    /**
     * Campaign is currently sending.
     */
    const Sending = 2;

    /**
     * Campaign has completed sending.
     */
    const Completed = 3;

    /**
     * Campaign is currently paused and not sending.
     */
    const Paused = 4;

    /**
     * Campaign has been cancelled during delivery.
     */
    const Cancelled = 5;

    /**
     * Campaign is save as draft and not processing.
     */
    const Draft = 6;

}

/**
 * 
 */
class CampaignTemplate
{
    /**
     * 
     */
    public /*?int*/ $CampaignTemplateID;

    /**
     * 
     */
    public /*string*/ $CampaignTemplateName;

    /**
     * Name of campaign's status
     */
    public /*\ElasticEmailEnums\CampaignStatus*/ $Status;

    /**
     * Name of your custom IP Pool to be used in the sending process
     */
    public /*string*/ $PoolName;

    /**
     * ID number of template.
     */
    public /*?int*/ $TemplateID;

    /**
     * Name of template.
     */
    public /*string*/ $TemplateName;

    /**
     * Default subject of email.
     */
    public /*string*/ $TemplateSubject;

    /**
     * Default From: email address.
     */
    public /*string*/ $TemplateFromEmail;

    /**
     * Default From: name.
     */
    public /*string*/ $TemplateFromName;

    /**
     * Default Reply: email address.
     */
    public /*string*/ $TemplateReplyEmail;

    /**
     * Default Reply: name.
     */
    public /*string*/ $TemplateReplyName;

    /**
     * The utm_source marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmSource;

    /**
     * The utm_medium marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmMedium;

    /**
     * The utm_campaign marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmCampaign;

    /**
     * The utm_content marketing parameter appended to each link in the campaign.
     */
    public /*string*/ $UtmContent;

}

/**
 * 
 * Enum class
 */
abstract class CampaignTriggerType
{
    /**
     * 
     */
    const SendNow = 1;

    /**
     * 
     */
    const FutureScheduled = 2;

    /**
     * 
     */
    const OnAdd = 3;

    /**
     * 
     */
    const OnOpen = 4;

    /**
     * 
     */
    const OnClick = 5;

}

/**
 * 
 * Enum class
 */
abstract class CertificateValidationStatus
{
    /**
     * 
     */
    const ErrorOccured = -2;

    /**
     * 
     */
    const CertNotSet = 0;

    /**
     * 
     */
    const Valid = 1;

    /**
     * 
     */
    const NotValid = 2;

}

/**
 * SMTP and HTTP API channel for grouping email delivery
 */
class Channel
{
    /**
     * Descriptive name of the channel.
     */
    public /*string*/ $Name;

    /**
     * The date the channel was added to your account.
     */
    public /*DateTime*/ $DateAdded;

    /**
     * The date the channel was last sent through.
     */
    public /*?DateTime*/ $LastActivity;

    /**
     * The number of email jobs this channel has been used with.
     */
    public /*int*/ $JobCount;

    /**
     * The number of emails that have been clicked within this channel.
     */
    public /*int*/ $ClickedCount;

    /**
     * The number of emails that have been opened within this channel.
     */
    public /*int*/ $OpenedCount;

    /**
     * The number of emails attempted to be sent within this channel.
     */
    public /*int*/ $RecipientCount;

    /**
     * The number of emails that have been sent within this channel.
     */
    public /*int*/ $SentCount;

    /**
     * The number of emails that have been bounced within this channel.
     */
    public /*int*/ $FailedCount;

    /**
     * The number of emails that have been unsubscribed within this channel.
     */
    public /*int*/ $UnsubscribedCount;

    /**
     * The number of emails that have been marked as abuse or complaint within this channel.
     */
    public /*int*/ $FailedAbuse;

    /**
     * The total cost for emails/attachments within this channel.
     */
    public /*decimal*/ $Cost;

}

/**
 * FileResponse compression format
 * Enum class
 */
abstract class CompressionFormat
{
    /**
     * No compression
     */
    const None = 0;

    /**
     * Zip compression
     */
    const Zip = 1;

}

/**
 * 
 * Enum class
 */
abstract class ConsentTracking
{
    /**
     * 
     */
    const Unknown = 0;

    /**
     * 
     */
    const Allow = 1;

    /**
     * 
     */
    const Deny = 2;

}

/**
 * Contact
 */
class Contact
{
    /**
     * 
     */
    public /*int*/ $ContactScore;

    /**
     * Date of creation in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $DateAdded;

    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * First name.
     */
    public /*string*/ $FirstName;

    /**
     * Last name.
     */
    public /*string*/ $LastName;

    /**
     * Status of the given resource
     */
    public /*\ElasticEmailEnums\ContactStatus*/ $Status;

    /**
     * RFC Error code
     */
    public /*?int*/ $BouncedErrorCode;

    /**
     * RFC error message
     */
    public /*string*/ $BouncedErrorMessage;

    /**
     * Total emails sent.
     */
    public /*int*/ $TotalSent;

    /**
     * Total emails failed.
     */
    public /*int*/ $TotalFailed;

    /**
     * Total emails opened.
     */
    public /*int*/ $TotalOpened;

    /**
     * Total emails clicked
     */
    public /*int*/ $TotalClicked;

    /**
     * Date of first failed message
     */
    public /*?DateTime*/ $FirstFailedDate;

    /**
     * Number of fails in sending to this Contact
     */
    public /*int*/ $LastFailedCount;

    /**
     * Last change date
     */
    public /*DateTime*/ $DateUpdated;

    /**
     * Source of URL of payment
     */
    public /*\ElasticEmailEnums\ContactSource*/ $Source;

    /**
     * RFC Error code
     */
    public /*?int*/ $ErrorCode;

    /**
     * RFC error message
     */
    public /*string*/ $FriendlyErrorMessage;

    /**
     * IP address
     */
    public /*string*/ $CreatedFromIP;

    /**
     * IP address of consent to send this contact(s) your email. If not provided your current public IP address is used for consent.
     */
    public /*string*/ $ConsentIP;

    /**
     * Date of consent to send this contact(s) your email. If not provided current date is used for consent.
     */
    public /*?DateTime*/ $ConsentDate;

    /**
     * 
     */
    public /*\ElasticEmailEnums\ConsentTracking*/ $ConsentTracking;

    /**
     * Unsubscribed date in YYYY-MM-DD format
     */
    public /*?DateTime*/ $UnsubscribedDate;

    /**
     * Free form field of notes
     */
    public /*string*/ $Notes;

    /**
     * Website of contact
     */
    public /*string*/ $WebsiteUrl;

    /**
     * Date this contact last opened an email
     */
    public /*?DateTime*/ $LastOpened;

    /**
     * 
     */
    public /*?DateTime*/ $LastClicked;

    /**
     * Custom contact field like companyname, customernumber, city etc. JSON serialized text like { "city":"london" } 
     */
    public /*array<string, string>*/ $CustomFields;

}

/**
 * Collection of lists and segments
 */
class ContactCollection
{
    /**
     * Lists which contain the requested contact
     */
    public /*Array<\ElasticEmailEnums\ContactContainer>*/ $Lists;

    /**
     * Segments which contain the requested contact
     */
    public /*Array<\ElasticEmailEnums\ContactContainer>*/ $Segments;

}

/**
 * List's or segment's short info
 */
class ContactContainer
{
    /**
     * ID of the list/segment
     */
    public /*int*/ $ID;

    /**
     * Name of the list/segment
     */
    public /*string*/ $Name;

}

/**
 * 
 * Enum class
 */
abstract class ContactHistEventType
{
    /**
     * Contact opened an e-mail
     */
    const Opened = 2;

    /**
     * Contact clicked an e-mail
     */
    const Clicked = 3;

    /**
     * E-mail sent to the contact bounced
     */
    const Bounced = 10;

    /**
     * Contact unsubscribed
     */
    const Unsubscribed = 11;

    /**
     * Contact complained to an e-mail
     */
    const Complained = 12;

    /**
     * Contact clicked an activation link
     */
    const Activated = 20;

    /**
     * Contact has opted to receive Transactional-only e-mails
     */
    const TransactionalUnsubscribed = 21;

    /**
     * Contact's status was changed manually
     */
    const ManualStatusChange = 22;

    /**
     * An Activation e-mail was sent
     */
    const ActivationSent = 24;

    /**
     * Contact was deleted
     */
    const Deleted = 28;

}

/**
 * History of chosen Contact
 */
class ContactHistory
{
    /**
     * ID of history of selected Contact.
     */
    public /*long*/ $ContactHistoryID;

    /**
     * Type of event occured on this Contact.
     */
    public /*string*/ $EventType;

    /**
     * Numeric code of event occured on this Contact.
     */
    public /*\ElasticEmailEnums\ContactHistEventType*/ $EventTypeValue;

    /**
     * Formatted date of event.
     */
    public /*string*/ $EventDate;

    /**
     * Name of selected channel.
     */
    public /*string*/ $ChannelName;

    /**
     * Name of template.
     */
    public /*string*/ $TemplateName;

    /**
     * IP Address of the event.
     */
    public /*string*/ $IPAddress;

    /**
     * Country of the event.
     */
    public /*string*/ $Country;

    /**
     * Information about the event
     */
    public /*string*/ $Data;

}

/**
 * 
 * Enum class
 */
abstract class ContactSort
{
    /**
     * 
     */
    const Unknown = 0;

    /**
     * Sort by date added ascending order
     */
    const DateAddedAsc = 1;

    /**
     * Sort by date added descending order
     */
    const DateAddedDesc = 2;

    /**
     * Sort by date updated ascending order
     */
    const DateUpdatedAsc = 3;

    /**
     * Sort by date updated descending order
     */
    const DateUpdatedDesc = 4;

}

/**
 * 
 * Enum class
 */
abstract class ContactSource
{
    /**
     * Source of the contact is from sending an email via our SMTP or HTTP API's
     */
    const DeliveryApi = 0;

    /**
     * Contact was manually entered from the interface.
     */
    const ManualInput = 1;

    /**
     * Contact was uploaded via a file such as CSV.
     */
    const FileUpload = 2;

    /**
     * Contact was added from a public web form.
     */
    const WebForm = 3;

    /**
     * Contact was added from the contact api.
     */
    const ContactApi = 4;

}

/**
 * 
 * Enum class
 */
abstract class ContactStatus
{
    /**
     * Only transactional email can be sent to contacts with this status.
     */
    const Transactional = -2;

    /**
     * Contact has had an open or click in the last 6 months.
     */
    const Engaged = -1;

    /**
     * Contact is eligible to be sent to.
     */
    const Active = 0;

    /**
     * Contact has had a hard bounce and is no longer eligible to be sent to.
     */
    const Bounced = 1;

    /**
     * Contact has unsubscribed and is no longer eligible to be sent to.
     */
    const Unsubscribed = 2;

    /**
     * Contact has complained and is no longer eligible to be sent to.
     */
    const Abuse = 3;

    /**
     * Contact has not been activated or has been de-activated and is not eligible to be sent to.
     */
    const Inactive = 4;

    /**
     * Contact has not been opening emails for a long period of time and is not eligible to be sent to.
     */
    const Stale = 5;

    /**
     * Contact has not confirmed their double opt-in activation and is not eligible to be sent to.
     */
    const NotConfirmed = 6;

}

/**
 * Number of Contacts, grouped by Status;
 */
class ContactStatusCounts
{
    /**
     * Number of engaged contacts
     */
    public /*long*/ $Engaged;

    /**
     * Number of active contacts
     */
    public /*long*/ $Active;

    /**
     * Number of complaint messages
     */
    public /*long*/ $Complaint;

    /**
     * Number of unsubscribed messages
     */
    public /*long*/ $Unsubscribed;

    /**
     * Number of bounced messages
     */
    public /*long*/ $Bounced;

    /**
     * Number of inactive contacts
     */
    public /*long*/ $Inactive;

    /**
     * Number of transactional contacts
     */
    public /*long*/ $Transactional;

    /**
     * 
     */
    public /*long*/ $Stale;

    /**
     * 
     */
    public /*long*/ $NotConfirmed;

}

/**
 * Number of Unsubscribed or Complaint Contacts, grouped by Unsubscribe Reason;
 */
class ContactUnsubscribeReasonCounts
{
    /**
     * 
     */
    public /*long*/ $Unknown;

    /**
     * 
     */
    public /*long*/ $NoLongerWant;

    /**
     * 
     */
    public /*long*/ $IrrelevantContent;

    /**
     * 
     */
    public /*long*/ $TooFrequent;

    /**
     * 
     */
    public /*long*/ $NeverConsented;

    /**
     * 
     */
    public /*long*/ $DeceptiveContent;

    /**
     * 
     */
    public /*long*/ $AbuseReported;

    /**
     * 
     */
    public /*long*/ $ThirdParty;

    /**
     * 
     */
    public /*long*/ $ListUnsubscribe;

}

/**
 * Type of credits
 * Enum class
 */
abstract class CreditType
{
    /**
     * Used to send emails.  One credit = one email.
     */
    const Email = 9;

}

/**
 * Daily summary of log status, based on specified date range.
 */
class DailyLogStatusSummary
{
    /**
     * Date in YYYY-MM-DDThh:ii:ss format
     */
    public /*string*/ $Date;

    /**
     * Proper email address.
     */
    public /*int*/ $Email;

    /**
     * Number of SMS
     */
    public /*int*/ $Sms;

    /**
     * Number of delivered messages
     */
    public /*int*/ $Delivered;

    /**
     * Number of opened messages
     */
    public /*int*/ $Opened;

    /**
     * Number of clicked messages
     */
    public /*int*/ $Clicked;

    /**
     * Number of unsubscribed messages
     */
    public /*int*/ $Unsubscribed;

    /**
     * Number of complaint messages
     */
    public /*int*/ $Complaint;

    /**
     * Number of bounced messages
     */
    public /*int*/ $Bounced;

    /**
     * Number of inbound messages
     */
    public /*int*/ $Inbound;

    /**
     * Number of manually cancelled messages
     */
    public /*int*/ $ManualCancel;

    /**
     * Number of messages flagged with 'Not Delivered'
     */
    public /*int*/ $NotDelivered;

}

/**
 * Domain data, with information about domain records.
 */
class DomainDetail
{
    /**
     * Name of selected domain.
     */
    public /*string*/ $Domain;

    /**
     * True, if domain is used as default. Otherwise, false,
     */
    public /*bool*/ $DefaultDomain;

    /**
     * True, if SPF record is verified
     */
    public /*bool*/ $Spf;

    /**
     * True, if DKIM record is verified
     */
    public /*bool*/ $Dkim;

    /**
     * True, if MX record is verified
     */
    public /*bool*/ $MX;

    /**
     * 
     */
    public /*bool*/ $DMARC;

    /**
     * True, if tracking CNAME record is verified
     */
    public /*bool*/ $IsRewriteDomainValid;

    /**
     * True, if verification is available
     */
    public /*bool*/ $Verify;

    /**
     * 
     */
    public /*\ElasticEmailEnums\TrackingType*/ $Type;

    /**
     * 0 - Validated successfully, 1 - NotValidated , 2 - Invalid, 3 - Broken (tracking was frequnetly verfied in given period and still is invalid). For statuses: 0, 1, 3 tracking will be verified in normal periods. For status 2 tracking will be verified in high frequent periods.
     */
    public /*\ElasticEmailEnums\TrackingValidationStatus*/ $TrackingStatus;

    /**
     * 
     */
    public /*\ElasticEmailEnums\CertificateValidationStatus*/ $CertificateStatus;

    /**
     * 
     */
    public /*string*/ $CertificateValidationError;

    /**
     * 
     */
    public /*?\ElasticEmailEnums\TrackingType*/ $TrackingTypeUserRequest;

}

/**
 * Detailed information about email credits
 */
class EmailCredits
{
    /**
     * Date in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $Date;

    /**
     * Amount of money in transaction
     */
    public /*decimal*/ $Amount;

    /**
     * Source of URL of payment
     */
    public /*string*/ $Source;

    /**
     * Free form field of notes
     */
    public /*string*/ $Notes;

}

/**
 * 
 */
class EmailJobFailedStatus
{
    /**
     * 
     */
    public /*string*/ $Address;

    /**
     * 
     */
    public /*string*/ $Error;

    /**
     * RFC Error code
     */
    public /*int*/ $ErrorCode;

    /**
     * 
     */
    public /*string*/ $Category;

}

/**
 * 
 */
class EmailJobStatus
{
    /**
     * ID number of your attachment
     */
    public /*string*/ $ID;

    /**
     * Name of status: submitted, complete, in_progress
     */
    public /*string*/ $Status;

    /**
     * 
     */
    public /*int*/ $RecipientsCount;

    /**
     * 
     */
    public /*Array<\ElasticEmailEnums\EmailJobFailedStatus>*/ $Failed;

    /**
     * Total emails failed.
     */
    public /*int*/ $FailedCount;

    /**
     * 
     */
    public /*Array<string>*/ $Sent;

    /**
     * Total emails sent.
     */
    public /*int*/ $SentCount;

    /**
     * Number of delivered messages
     */
    public /*Array<string>*/ $Delivered;

    /**
     * 
     */
    public /*int*/ $DeliveredCount;

    /**
     * 
     */
    public /*Array<string>*/ $Pending;

    /**
     * 
     */
    public /*int*/ $PendingCount;

    /**
     * Number of opened messages
     */
    public /*Array<string>*/ $Opened;

    /**
     * Total emails opened.
     */
    public /*int*/ $OpenedCount;

    /**
     * Number of clicked messages
     */
    public /*Array<string>*/ $Clicked;

    /**
     * Total emails clicked
     */
    public /*int*/ $ClickedCount;

    /**
     * Number of unsubscribed messages
     */
    public /*Array<string>*/ $Unsubscribed;

    /**
     * Total emails unsubscribed
     */
    public /*int*/ $UnsubscribedCount;

    /**
     * 
     */
    public /*Array<string>*/ $AbuseReports;

    /**
     * 
     */
    public /*int*/ $AbuseReportsCount;

    /**
     * List of all MessageIDs for this job.
     */
    public /*Array<string>*/ $MessageIDs;

}

/**
 * 
 */
class EmailSend
{
    /**
     * ID number of transaction
     */
    public /*string*/ $TransactionID;

    /**
     * Unique identifier for this email.
     */
    public /*string*/ $MessageID;

}

/**
 * Status information of the specified email
 */
class EmailStatus
{
    /**
     * Email address this email was sent from.
     */
    public /*string*/ $From;

    /**
     * Email address this email was sent to.
     */
    public /*string*/ $To;

    /**
     * Date the email was submitted.
     */
    public /*DateTime*/ $Date;

    /**
     * Value of email's status
     */
    public /*\ElasticEmailEnums\LogJobStatus*/ $Status;

    /**
     * Name of email's status
     */
    public /*string*/ $StatusName;

    /**
     * Date of last status change.
     */
    public /*DateTime*/ $StatusChangeDate;

    /**
     * Date when the email was sent
     */
    public /*DateTime*/ $DateSent;

    /**
     * Date when the email changed the status to 'opened'
     */
    public /*?DateTime*/ $DateOpened;

    /**
     * Date when the email changed the status to 'clicked'
     */
    public /*?DateTime*/ $DateClicked;

    /**
     * Detailed error or bounced message.
     */
    public /*string*/ $ErrorMessage;

    /**
     * ID number of transaction
     */
    public /*Guid*/ $TransactionID;

}

/**
 * Email details formatted in json
 */
class EmailView
{
    /**
     * Body (text) of your message.
     */
    public /*string*/ $Body;

    /**
     * Default subject of email.
     */
    public /*string*/ $Subject;

    /**
     * Starting date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*string*/ $From;

}

/**
 * Encoding type for the email headers
 * Enum class
 */
abstract class EncodingType
{
    /**
     * Encoding of the email is provided by the sender and not altered.
     */
    const UserProvided = -1;

    /**
     * No endcoding is set for the email.
     */
    const None = 0;

    /**
     * Encoding of the email is in Raw7bit format.
     */
    const Raw7bit = 1;

    /**
     * Encoding of the email is in Raw8bit format.
     */
    const Raw8bit = 2;

    /**
     * Encoding of the email is in QuotedPrintable format.
     */
    const QuotedPrintable = 3;

    /**
     * Encoding of the email is in Base64 format.
     */
    const Base64 = 4;

    /**
     * Encoding of the email is in Uue format.
     */
    const Uue = 5;

}

/**
 * Event logs for selected date range
 */
class EventLog
{
    /**
     * Starting date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*?DateTime*/ $From;

    /**
     * Ending date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*?DateTime*/ $To;

    /**
     * Number of recipients
     */
    public /*Array<\ElasticEmailEnums\RecipientEvent>*/ $Recipients;

}

/**
 * Record of exported data from the system.
 */
class Export
{
    /**
     * ID of the exported file
     */
    public /*Guid*/ $PublicExportID;

    /**
     * Date the export was created
     */
    public /*DateTime*/ $DateAdded;

    /**
     * Type of export
     */
    public /*string*/ $Type;

    /**
     * Current status of export
     */
    public /*string*/ $Status;

    /**
     * Long description of the export
     */
    public /*string*/ $Info;

    /**
     * Name of the file
     */
    public /*string*/ $Filename;

    /**
     * Link to download the export
     */
    public /*string*/ $Link;

    /**
     * Log start date (for Type = Log only)
     */
    public /*?DateTime*/ $LogFrom;

    /**
     * Log end date (for Type = Log only)
     */
    public /*?DateTime*/ $LogTo;

}

/**
 * Type of export
 * Enum class
 */
abstract class ExportFileFormats
{
    /**
     * Export in comma separated values format.
     */
    const Csv = 1;

    /**
     * Export in xml format
     */
    const Xml = 2;

    /**
     * Export in json format
     */
    const Json = 3;

}

/**
 * 
 */
class ExportLink
{
    /**
     * Direct URL to the exported file
     */
    public /*string*/ $Link;

    /**
     * ID of the exported file
     */
    public /*Guid*/ $PublicExportID;

}

/**
 * Current status of export
 * Enum class
 */
abstract class ExportStatus
{
    /**
     * Export had an error and can not be downloaded.
     */
    const Error = -1;

    /**
     * Export is currently loading and can not be downloaded.
     */
    const Loading = 0;

    /**
     * Export is currently available for downloading.
     */
    const Ready = 1;

    /**
     * Export is no longer available for downloading.
     */
    const Expired = 2;

}

/**
 * Number of Exports, grouped by export type
 */
class ExportTypeCounts
{
    /**
     * 
     */
    public /*long*/ $Log;

    /**
     * 
     */
    public /*long*/ $Contact;

    /**
     * Json representation of a campaign
     */
    public /*long*/ $Campaign;

    /**
     * True, if you have enabled link tracking. Otherwise, false
     */
    public /*long*/ $LinkTracking;

    /**
     * Json representation of a survey
     */
    public /*long*/ $Survey;

}

/**
 * 
 */
class File
{
    /**
     * Name of your file.
     */
    public /*string*/ $FileName;

    /**
     * Size of your attachment (in bytes).
     */
    public /*?int*/ $Size;

    /**
     * Date of creation in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $DateAdded;

    /**
     * When will the file be deleted from the system
     */
    public /*?DateTime*/ $ExpirationDate;

    /**
     * Content type of the file
     */
    public /*string*/ $ContentType;

}

/**
 * Lists inbound options of your account.
 */
class InboundOptions
{
    /**
     * URL used for tracking action of inbound emails
     */
    public /*string*/ $HubCallbackUrl;

    /**
     * Domain you use as your inbound domain
     */
    public /*string*/ $InboundDomain;

    /**
     * True, if you want inbound email to only process contacts from your account. Otherwise, false
     */
    public /*bool*/ $InboundContactsOnly;

}

/**
 * 
 * Enum class
 */
abstract class IntervalType
{
    /**
     * Daily overview
     */
    const Summary = 0;

    /**
     * Hourly, detailed information
     */
    const Hourly = 1;

}

/**
 * Object containig tracking data.
 */
class LinkTrackingDetails
{
    /**
     * Number of items.
     */
    public /*int*/ $Count;

    /**
     * True, if there are more detailed data available. Otherwise, false
     */
    public /*bool*/ $MoreAvailable;

    /**
     * 
     */
    public /*Array<\ElasticEmailEnums\TrackedLink>*/ $TrackedLink;

}

/**
 * List of Lists, with detailed data about its contents.
 */
class EEList
{
    /**
     * ID number of selected list.
     */
    public /*int*/ $ListID;

    /**
     * Name of your list.
     */
    public /*string*/ $ListName;

    /**
     * Number of items.
     */
    public /*int*/ $Count;

    /**
     * ID code of list
     */
    public /*?Guid*/ $PublicListID;

    /**
     * Date of creation in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $DateAdded;

    /**
     * True: Allow unsubscribing from this list. Otherwise, false
     */
    public /*bool*/ $AllowUnsubscribe;

    /**
     * Query used for filtering.
     */
    public /*string*/ $Rule;

}

/**
 * Logs for selected date range
 */
class Log
{
    /**
     * Starting date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*?DateTime*/ $From;

    /**
     * Ending date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*?DateTime*/ $To;

    /**
     * Number of recipients
     */
    public /*Array<\ElasticEmailEnums\Recipient>*/ $Recipients;

}

/**
 * 
 * Enum class
 */
abstract class LogEventStatus
{
    /**
     * Email is queued for sending.
     */
    const ReadyToSend = 1;

    /**
     * Email has soft bounced and is scheduled to retry.
     */
    const WaitingToRetry = 2;

    /**
     * Email is currently sending.
     */
    const Sending = 3;

    /**
     * Email has errored or bounced for some reason.
     */
    const Error = 4;

    /**
     * Email has been successfully delivered.
     */
    const Sent = 5;

    /**
     * Email has been opened by the recipient.
     */
    const Opened = 6;

    /**
     * Email has had at least one link clicked by the recipient.
     */
    const Clicked = 7;

    /**
     * Email has been unsubscribed by the recipient.
     */
    const Unsubscribed = 8;

    /**
     * Email has been complained about or marked as spam by the recipient.
     */
    const AbuseReport = 9;

}

/**
 * 
 * Enum class
 */
abstract class LogJobStatus
{
    /**
     * All emails
     */
    const All = 0;

    /**
     * Email has been submitted successfully and is queued for sending.
     */
    const ReadyToSend = 1;

    /**
     * Email has soft bounced and is scheduled to retry.
     */
    const WaitingToRetry = 2;

    /**
     * Email is currently sending.
     */
    const Sending = 3;

    /**
     * Email has errored or bounced for some reason.
     */
    const Error = 4;

    /**
     * Email has been successfully delivered.
     */
    const Sent = 5;

    /**
     * Email has been opened by the recipient.
     */
    const Opened = 6;

    /**
     * Email has had at least one link clicked by the recipient.
     */
    const Clicked = 7;

    /**
     * Email has been unsubscribed by the recipient.
     */
    const Unsubscribed = 8;

    /**
     * Email has been complained about or marked as spam by the recipient.
     */
    const AbuseReport = 9;

}

/**
 * Summary of log status, based on specified date range.
 */
class LogStatusSummary
{
    /**
     * Starting date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*string*/ $From;

    /**
     * Ending date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*string*/ $To;

    /**
     * Overall duration
     */
    public /*double*/ $Duration;

    /**
     * Number of recipients
     */
    public /*long*/ $Recipients;

    /**
     * Number of emails
     */
    public /*long*/ $EmailTotal;

    /**
     * Number of SMS
     */
    public /*long*/ $SmsTotal;

    /**
     * Number of delivered messages
     */
    public /*long*/ $Delivered;

    /**
     * Number of bounced messages
     */
    public /*long*/ $Bounced;

    /**
     * Number of messages in progress
     */
    public /*long*/ $InProgress;

    /**
     * Number of opened messages
     */
    public /*long*/ $Opened;

    /**
     * Number of clicked messages
     */
    public /*long*/ $Clicked;

    /**
     * Number of unsubscribed messages
     */
    public /*long*/ $Unsubscribed;

    /**
     * Number of complaint messages
     */
    public /*long*/ $Complaints;

    /**
     * Number of inbound messages
     */
    public /*long*/ $Inbound;

    /**
     * Number of manually cancelled messages
     */
    public /*long*/ $ManualCancel;

    /**
     * Number of messages flagged with 'Not Delivered'
     */
    public /*long*/ $NotDelivered;

    /**
     * ID number of template used
     */
    public /*bool*/ $TemplateChannel;

}

/**
 * Overall log summary information.
 */
class LogSummary
{
    /**
     * Summary of log status, based on specified date range.
     */
    public /*\ElasticEmailEnums\LogStatusSummary*/ $LogStatusSummary;

    /**
     * Summary of bounced categories, based on specified date range.
     */
    public /*\ElasticEmailEnums\BouncedCategorySummary*/ $BouncedCategorySummary;

    /**
     * Daily summary of log status, based on specified date range.
     */
    public /*Array<\ElasticEmailEnums\DailyLogStatusSummary>*/ $DailyLogStatusSummary;

    /**
     * 
     */
    public /*\ElasticEmailEnums\SubaccountSummary*/ $SubaccountSummary;

}

/**
 * 
 * Enum class
 */
abstract class MessageCategory
{
    /**
     * 
     */
    const Unknown = 0;

    /**
     * 
     */
    const Ignore = 1;

    /**
     * Number of messages marked as SPAM
     */
    const Spam = 2;

    /**
     * Number of blacklisted messages
     */
    const BlackListed = 3;

    /**
     * Number of messages flagged with 'No Mailbox'
     */
    const NoMailbox = 4;

    /**
     * Number of messages flagged with 'Grey Listed'
     */
    const GreyListed = 5;

    /**
     * Number of messages flagged with 'Throttled'
     */
    const Throttled = 6;

    /**
     * Number of messages flagged with 'Timeout'
     */
    const Timeout = 7;

    /**
     * Number of messages flagged with 'Connection Problem'
     */
    const ConnectionProblem = 8;

    /**
     * Number of messages flagged with 'SPF Problem'
     */
    const SPFProblem = 9;

    /**
     * Number of messages flagged with 'Account Problem'
     */
    const AccountProblem = 10;

    /**
     * Number of messages flagged with 'DNS Problem'
     */
    const DNSProblem = 11;

    /**
     * 
     */
    const NotDeliveredCancelled = 12;

    /**
     * Number of messages flagged with 'Code Error'
     */
    const CodeError = 13;

    /**
     * Number of manually cancelled messages
     */
    const ManualCancel = 14;

    /**
     * Number of messages flagged with 'Connection terminated'
     */
    const ConnectionTerminated = 15;

    /**
     * Number of messages flagged with 'Not Delivered'
     */
    const NotDelivered = 16;

}

/**
 * 
 * Enum class
 */
abstract class NotificationType
{
    /**
     * Both, email and web, notifications
     */
    const All = 0;

    /**
     * Only email notifications
     */
    const Email = 1;

    /**
     * Only web notifications
     */
    const Web = 2;

}

/**
 * Detailed information about existing money transfers.
 */
class Payment
{
    /**
     * Date in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $Date;

    /**
     * Amount of money in transaction
     */
    public /*decimal*/ $Amount;

    /**
     * 
     */
    public /*decimal*/ $RegularAmount;

    /**
     * 
     */
    public /*decimal*/ $DiscountPercent;

    /**
     * Source of URL of payment
     */
    public /*string*/ $Source;

}

/**
 * Basic information about your profile
 */
class Profile
{
    /**
     * First name.
     */
    public /*string*/ $FirstName;

    /**
     * Last name.
     */
    public /*string*/ $LastName;

    /**
     * Company name.
     */
    public /*string*/ $Company;

    /**
     * First line of address.
     */
    public /*string*/ $Address1;

    /**
     * Second line of address.
     */
    public /*string*/ $Address2;

    /**
     * City.
     */
    public /*string*/ $City;

    /**
     * State or province.
     */
    public /*string*/ $State;

    /**
     * Zip/postal code.
     */
    public /*string*/ $Zip;

    /**
     * Numeric ID of country. A file with the list of countries is available <a href="http://api.elasticemail.com/public/countries"><b>here</b></a>
     */
    public /*?int*/ $CountryID;

    /**
     * Phone number
     */
    public /*string*/ $Phone;

    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * Code used for tax purposes.
     */
    public /*string*/ $TaxCode;

    /**
     * Why your clients are receiving your emails.
     */
    public /*string*/ $DeliveryReason;

    /**
     * True if you want to receive newsletters from Elastic Email. Otherwise, false. Empty to leave the current value.
     */
    public /*?bool*/ $MarketingConsent;

    /**
     * HTTP address of your website.
     */
    public /*string*/ $Website;

    /**
     * URL to your logo image.
     */
    public /*string*/ $LogoUrl;

}

/**
 * Detailed information about message recipient
 */
class Recipient
{
    /**
     * True, if message is SMS. Otherwise, false
     */
    public /*bool*/ $IsSms;

    /**
     * ID number of selected message.
     */
    public /*string*/ $MsgID;

    /**
     * Ending date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*string*/ $To;

    /**
     * Name of recipient's status: Submitted, ReadyToSend, WaitingToRetry, Sending, Bounced, Sent, Opened, Clicked, Unsubscribed, AbuseReport
     */
    public /*string*/ $Status;

    /**
     * Name of selected Channel.
     */
    public /*string*/ $Channel;

    /**
     * Creation date
     */
    public /*string*/ $Date;

    /**
     * Date when the email was sent
     */
    public /*string*/ $DateSent;

    /**
     * Date when the email changed the status to 'opened'
     */
    public /*string*/ $DateOpened;

    /**
     * Date when the email changed the status to 'clicked'
     */
    public /*string*/ $DateClicked;

    /**
     * Content of message, HTML encoded
     */
    public /*string*/ $Message;

    /**
     * True, if message category should be shown. Otherwise, false
     */
    public /*bool*/ $ShowCategory;

    /**
     * Name of message category
     */
    public /*string*/ $MessageCategory;

    /**
     * ID of message category
     */
    public /*?\ElasticEmailEnums\MessageCategory*/ $MessageCategoryID;

    /**
     * Date of last status change.
     */
    public /*string*/ $StatusChangeDate;

    /**
     * Date of next try
     */
    public /*string*/ $NextTryOn;

    /**
     * Default subject of email.
     */
    public /*string*/ $Subject;

    /**
     * Default From: email address.
     */
    public /*string*/ $FromEmail;

    /**
     * 
     */
    public /*string*/ $EnvelopeFrom;

    /**
     * ID of certain mail job
     */
    public /*string*/ $JobID;

    /**
     * True, if message is a SMS and status is not yet confirmed. Otherwise, false
     */
    public /*bool*/ $SmsUpdateRequired;

    /**
     * Content of message
     */
    public /*string*/ $TextMessage;

    /**
     * Comma separated ID numbers of messages.
     */
    public /*string*/ $MessageSid;

    /**
     * Recipient's last bounce error because of which this e-mail was suppressed
     */
    public /*string*/ $ContactLastError;

    /**
     * 
     */
    public /*string*/ $IPAddress;

}

/**
 * Detailed information about message recipient
 */
class RecipientEvent
{
    /**
     * ID of certain mail job
     */
    public /*string*/ $JobID;

    /**
     * ID number of selected message.
     */
    public /*string*/ $MsgID;

    /**
     * Default From: email address.
     */
    public /*string*/ $FromEmail;

    /**
     * Ending date for search in YYYY-MM-DDThh:mm:ss format.
     */
    public /*string*/ $To;

    /**
     * Default subject of email.
     */
    public /*string*/ $Subject;

    /**
     * Name of recipient's status: Submitted, ReadyToSend, WaitingToRetry, Sending, Bounced, Sent, Opened, Clicked, Unsubscribed, AbuseReport
     */
    public /*string*/ $EventType;

    /**
     * Creation date
     */
    public /*string*/ $EventDate;

    /**
     * Name of selected Channel.
     */
    public /*string*/ $Channel;

    /**
     * ID number of selected Channel.
     */
    public /*?int*/ $ChannelID;

    /**
     * Name of message category
     */
    public /*string*/ $MessageCategory;

    /**
     * Date of next try
     */
    public /*string*/ $NextTryOn;

    /**
     * Content of message, HTML encoded
     */
    public /*string*/ $Message;

    /**
     * 
     */
    public /*string*/ $IPAddress;

    /**
     * 
     */
    public /*string*/ $IPPoolName;

}

/**
 * Referral details for this account.
 */
class Referral
{
    /**
     * Current amount of dolars you have from referring.
     */
    public /*decimal*/ $CurrentReferralCredit;

    /**
     * Number of active referrals.
     */
    public /*long*/ $CurrentReferralCount;

}

/**
 * Detailed sending reputation of your account.
 */
class ReputationDetail
{
    /**
     * Overall reputation impact, based on the most important factors.
     */
    public /*\ElasticEmailEnums\ReputationImpact*/ $Impact;

    /**
     * Percent of Complaining users - those, who do not want to receive email from you.
     */
    public /*double*/ $AbusePercent;

    /**
     * Percent of Unknown users - users that couldn't be found
     */
    public /*double*/ $UnknownUsersPercent;

    /**
     * 
     */
    public /*double*/ $OpenedPercent;

    /**
     * 
     */
    public /*double*/ $ClickedPercent;

    /**
     * Penalty from messages marked as spam.
     */
    public /*double*/ $AverageSpamScore;

    /**
     * Percent of Bounced users
     */
    public /*double*/ $FailedSpamPercent;

    /**
     * Points from quantity of your emails.
     */
    public /*double*/ $RepEmailsSent;

    /**
     * Average reputation.
     */
    public /*double*/ $AverageReputation;

    /**
     * Actual price level.
     */
    public /*double*/ $PriceLevelReputation;

    /**
     * Reputation needed to change pricing.
     */
    public /*double*/ $NextPriceLevelReputation;

    /**
     * Amount of emails sent from this account
     */
    public /*string*/ $PriceLevel;

    /**
     * True, if tracking domain is correctly configured. Otherwise, false.
     */
    public /*bool*/ $TrackingDomainValid;

    /**
     * True, if sending domain is correctly configured. Otherwise, false.
     */
    public /*bool*/ $SenderDomainValid;

}

/**
 * Reputation history of your account.
 */
class ReputationHistory
{
    /**
     * Creation date.
     */
    public /*string*/ $DateCreated;

    /**
     * Percent of Complaining users - those, who do not want to receive email from you.
     */
    public /*double*/ $AbusePercent;

    /**
     * Percent of Unknown users - users that couldn't be found
     */
    public /*double*/ $UnknownUsersPercent;

    /**
     * 
     */
    public /*double*/ $OpenedPercent;

    /**
     * 
     */
    public /*double*/ $ClickedPercent;

    /**
     * Penalty from messages marked as spam.
     */
    public /*double*/ $AverageSpamScore;

    /**
     * Points from proper setup of your account
     */
    public /*double*/ $SetupScore;

    /**
     * Points from quantity of your emails.
     */
    public /*double*/ $RepEmailsSent;

    /**
     * Numeric reputation
     */
    public /*double*/ $Reputation;

}

/**
 * Overall reputation impact, based on the most important factors.
 */
class ReputationImpact
{
    /**
     * Abuses - mails sent to user without their consent
     */
    public /*double*/ $Abuse;

    /**
     * Users, that could not be reached.
     */
    public /*double*/ $UnknownUsers;

    /**
     * Number of opened messages
     */
    public /*double*/ $Opened;

    /**
     * Number of clicked messages
     */
    public /*double*/ $Clicked;

    /**
     * Penalty from messages marked as spam.
     */
    public /*double*/ $AverageSpamScore;

    /**
     * Content analysis.
     */
    public /*double*/ $ServerFilter;

    /**
     * Tracking domain.
     */
    public /*double*/ $TrackingDomain;

    /**
     * Sending domain.
     */
    public /*double*/ $SenderDomain;

}

/**
 * Information about Contact Segment, selected by RULE.
 */
class Segment
{
    /**
     * ID number of your segment.
     */
    public /*int*/ $SegmentID;

    /**
     * Filename
     */
    public /*string*/ $Name;

    /**
     * Query used for filtering.
     */
    public /*string*/ $Rule;

    /**
     * Number of items from last check.
     */
    public /*long*/ $LastCount;

    /**
     * History of segment information.
     */
    public /*Array<\ElasticEmailEnums\SegmentHistory>*/ $History;

}

/**
 * Segment History
 */
class SegmentHistory
{
    /**
     * ID number of history.
     */
    public /*int*/ $SegmentHistoryID;

    /**
     * ID number of your segment.
     */
    public /*int*/ $SegmentID;

    /**
     * Date in YYYY-MM-DD format
     */
    public /*int*/ $Day;

    /**
     * Number of items.
     */
    public /*long*/ $Count;

}

/**
 * 
 * Enum class
 */
abstract class SendingPermission
{
    /**
     * Sending not allowed.
     */
    const None = 0;

    /**
     * Allow sending via SMTP only.
     */
    const Smtp = 1;

    /**
     * Allow sending via HTTP API only.
     */
    const HttpApi = 2;

    /**
     * Allow sending via SMTP and HTTP API.
     */
    const SmtpAndHttpApi = 3;

    /**
     * Allow sending via the website interface only.
     */
    const EEInterface = 4;

    /**
     * Allow sending via SMTP and the website interface.
     */
    const SmtpAndInterface = 5;

    /**
     * Allow sendnig via HTTP API and the website interface.
     */
    const HttpApiAndInterface = 6;

    /**
     * Use access level sending permission.
     */
    const UseAccessLevel = 16;

    /**
     * Sending allowed via SMTP, HTTP API and the website interface.
     */
    const All = 255;

}

/**
 * Spam check of specified message.
 */
class SpamCheck
{
    /**
     * Total spam score from
     */
    public /*string*/ $TotalScore;

    /**
     * Date in YYYY-MM-DDThh:ii:ss format
     */
    public /*string*/ $Date;

    /**
     * Default subject of email.
     */
    public /*string*/ $Subject;

    /**
     * Default From: email address.
     */
    public /*string*/ $FromEmail;

    /**
     * ID number of selected message.
     */
    public /*string*/ $MsgID;

    /**
     * Name of selected channel.
     */
    public /*string*/ $ChannelName;

    /**
     * 
     */
    public /*Array<\ElasticEmailEnums\SpamRule>*/ $Rules;

}

/**
 * Single spam score
 */
class SpamRule
{
    /**
     * Spam score
     */
    public /*string*/ $Score;

    /**
     * Name of rule
     */
    public /*string*/ $Key;

    /**
     * Description of rule.
     */
    public /*string*/ $Description;

}

/**
 * 
 * Enum class
 */
abstract class SplitOptimization
{
    /**
     * Number of opened messages
     */
    const Opened = 0;

    /**
     * Number of clicked messages
     */
    const Clicked = 1;

}

/**
 * Subaccount. Contains detailed data of your Subaccount.
 */
class SubAccount
{
    /**
     * Public key for limited access to your account such as contact/add so you can use it safely on public websites.
     */
    public /*string*/ $PublicAccountID;

    /**
     * ApiKey that gives you access to our SMTP and HTTP API's.
     */
    public /*string*/ $ApiKey;

    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * ID number of mailer
     */
    public /*string*/ $MailerID;

    /**
     * Name of your custom IP Pool to be used in the sending process
     */
    public /*string*/ $PoolName;

    /**
     * Date of last activity on account
     */
    public /*string*/ $LastActivity;

    /**
     * Amount of email credits
     */
    public /*string*/ $EmailCredits;

    /**
     * True, if account needs credits to send emails. Otherwise, false
     */
    public /*bool*/ $RequiresEmailCredits;

    /**
     * Amount of credits added to account automatically
     */
    public /*double*/ $MonthlyRefillCredits;

    /**
     * True, if account can request for private IP on its own. Otherwise, false
     */
    public /*bool*/ $EnablePrivateIPRequest;

    /**
     * Amount of emails sent from this account
     */
    public /*long*/ $TotalEmailsSent;

    /**
     * Percent of Unknown users - users that couldn't be found
     */
    public /*double*/ $UnknownUsersPercent;

    /**
     * Percent of Complaining users - those, who do not want to receive email from you.
     */
    public /*double*/ $AbusePercent;

    /**
     * Percent of Bounced users
     */
    public /*double*/ $FailedSpamPercent;

    /**
     * Numeric reputation
     */
    public /*double*/ $Reputation;

    /**
     * Amount of emails account can send daily
     */
    public /*long*/ $DailySendLimit;

    /**
     * Name of account's status: Deleted, Disabled, UnderReview, NoPaymentsAllowed, NeverSignedIn, Active, SystemPaused
     */
    public /*string*/ $Status;

    /**
     * Maximum size of email including attachments in MB's
     */
    public /*int*/ $EmailSizeLimit;

    /**
     * Maximum number of contacts the account can have
     */
    public /*int*/ $MaxContacts;

    /**
     * Sending permission setting for account
     */
    public /*\ElasticEmailEnums\SendingPermission*/ $SendingPermission;

    /**
     * 
     */
    public /*bool*/ $HasModify2FA;

    /**
     * 
     */
    public /*int*/ $ContactsCount;

}

/**
 * Detailed account settings.
 */
class SubAccountSettings
{
    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * True, if account needs credits to send emails. Otherwise, false
     */
    public /*bool*/ $RequiresEmailCredits;

    /**
     * Amount of credits added to account automatically
     */
    public /*double*/ $MonthlyRefillCredits;

    /**
     * Maximum size of email including attachments in MB's
     */
    public /*int*/ $EmailSizeLimit;

    /**
     * Amount of emails account can send daily
     */
    public /*int*/ $DailySendLimit;

    /**
     * Maximum number of contacts the account can have
     */
    public /*int*/ $MaxContacts;

    /**
     * True, if account can request for private IP on its own. Otherwise, false
     */
    public /*bool*/ $EnablePrivateIPRequest;

    /**
     * True, if you want to use Contact Delivery Tools.  Otherwise, false
     */
    public /*bool*/ $EnableContactFeatures;

    /**
     * Sending permission setting for account
     */
    public /*\ElasticEmailEnums\SendingPermission*/ $SendingPermission;

    /**
     * Name of your custom IP Pool to be used in the sending process
     */
    public /*string*/ $PoolName;

    /**
     * Public key for limited access to your account such as contact/add so you can use it safely on public websites.
     */
    public /*string*/ $PublicAccountID;

    /**
     * 
     */
    public /*?bool*/ $Allow2FA;

}

/**
 * 
 */
class SubaccountSummary
{
    /**
     * 
     */
    public /*int*/ $EmailsSentToday;

    /**
     * 
     */
    public /*int*/ $EmailsSentThisMonth;

}

/**
 * Add-on support options for your account
 * Enum class
 */
abstract class SupportPlan
{
    /**
     * In-app support option for $1/day
     */
    const Priority = 1;

    /**
     * In-app real-time chat support option for $7/day
     */
    const Premium = 2;

}

/**
 * Template
 */
class Template
{
    /**
     * ID number of template.
     */
    public /*int*/ $TemplateID;

    /**
     * 0 for API connections
     */
    public /*\ElasticEmailEnums\TemplateType*/ $TemplateType;

    /**
     * Filename
     */
    public /*string*/ $Name;

    /**
     * Date of creation in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $DateAdded;

    /**
     * CSS style
     */
    public /*string*/ $Css;

    /**
     * Default subject of email.
     */
    public /*string*/ $Subject;

    /**
     * Default From: email address.
     */
    public /*string*/ $FromEmail;

    /**
     * Default From: name.
     */
    public /*string*/ $FromName;

    /**
     * HTML code of email (needs escaping).
     */
    public /*string*/ $BodyHtml;

    /**
     * AMP code of email (needs escaping).
     */
    public /*string*/ $BodyAmp;

    /**
     * Text body of email.
     */
    public /*string*/ $BodyText;

    /**
     * ID number of original template.
     */
    public /*int*/ $OriginalTemplateID;

    /**
     * 
     */
    public /*string*/ $OriginalTemplateName;

    /**
     * Enum: 0 - private, 1 - public, 2 - mockup
     */
    public /*\ElasticEmailEnums\TemplateScope*/ $TemplateScope;

    /**
     * Template's Tags
     */
    public /*Array<string>*/ $Tags;

}

/**
 * List of templates (including drafts)
 */
class TemplateList
{
    /**
     * List of templates
     */
    public /*Array<\ElasticEmailEnums\Template>*/ $Templates;

    /**
     * Total of templates
     */
    public /*int*/ $TemplatesCount;

    /**
     * List of draft templates
     */
    public /*Array<\ElasticEmailEnums\Template>*/ $DraftTemplate;

    /**
     * Total of draft templates
     */
    public /*int*/ $DraftTemplatesCount;

}

/**
 * 
 * Enum class
 */
abstract class TemplateScope
{
    /**
     * Template is available for this account only.
     */
    const EEPrivate = 0;

    /**
     * Template is available for this account and it's sub-accounts.
     */
    const EEPublic = 1;

    /**
     * Template is a temporary draft, not to be used permanently.
     */
    const Draft = 2;

}

/**
 * Tag used for tagging multiple Templates
 */
class TemplateTag
{
    /**
     * Tag's value
     */
    public /*string*/ $Name;

}

/**
 * A list of your personal and global Template Tags
 */
class TemplateTagList
{
    /**
     * List of personal Tags
     */
    public /*Array<\ElasticEmailEnums\TemplateTag>*/ $Tags;

    /**
     * List of globally available Tags
     */
    public /*Array<\ElasticEmailEnums\TemplateTag>*/ $GlobalTags;

}

/**
 * 
 * Enum class
 */
abstract class TemplateType
{
    /**
     * Template supports any valid HTML
     */
    const RawHTML = 0;

    /**
     * Template is created and can only be modified in drag and drop editor
     */
    const DragDropEditor = 1;

}

/**
 * Information about tracking link and its clicks.
 */
class TrackedLink
{
    /**
     * URL clicked
     */
    public /*string*/ $Link;

    /**
     * Number of clicks
     */
    public /*string*/ $Clicks;

    /**
     * Percent of clicks
     */
    public /*string*/ $Percent;

}

/**
 * 
 * Enum class
 */
abstract class TrackingType
{
    /**
     * 
     */
    const None = -2;

    /**
     * 
     */
    const EEDelete = -1;

    /**
     * 
     */
    const Http = 0;

    /**
     * 
     */
    const ExternalHttps = 1;

    /**
     * 
     */
    const InternalCertHttps = 2;

    /**
     * 
     */
    const LetsEncryptCert = 3;

}

/**
 * Status of ValidDomain to determine how often tracking validation should be performed.
 * Enum class
 */
abstract class TrackingValidationStatus
{
    /**
     * 
     */
    const Validated = 0;

    /**
     * 
     */
    const NotValidated = 1;

    /**
     * 
     */
    const Invalid = 2;

    /**
     * 
     */
    const Broken = 3;

}

/**
 * Account usage
 */
class Usage
{
    /**
     * Proper email address.
     */
    public /*string*/ $Email;

    /**
     * True, if this account is a sub-account. Otherwise, false
     */
    public /*bool*/ $IsSubAccount;

    /**
     * 
     */
    public /*Array<\ElasticEmailEnums\UsageData>*/ $List;

}

/**
 * Detailed data about daily usage
 */
class UsageData
{
    /**
     * Date in YYYY-MM-DDThh:ii:ss format
     */
    public /*DateTime*/ $Date;

    /**
     * Number of finished tasks
     */
    public /*int*/ $JobCount;

    /**
     * Overall number of recipients
     */
    public /*int*/ $RecipientCount;

    /**
     * Number of inbound emails
     */
    public /*int*/ $InboundCount;

    /**
     * Number of attachments sent
     */
    public /*int*/ $AttachmentCount;

    /**
     * Size of attachments sent
     */
    public /*long*/ $AttachmentsSize;

    /**
     * Calculated cost of sending
     */
    public /*decimal*/ $Cost;

    /**
     * Number of pricate IPs
     */
    public /*?int*/ $PrivateIPCount;

    /**
     * 
     */
    public /*decimal*/ $PrivateIPCost;

    /**
     * Number of SMS
     */
    public /*?int*/ $SmsCount;

    /**
     * Overall cost of SMS
     */
    public /*decimal*/ $SmsCost;

    /**
     * Cost of email credits
     */
    public /*?int*/ $EmailCreditsCost;

    /**
     * Daily cost of Contact Delivery Tools
     */
    public /*decimal*/ $ContactCost;

    /**
     * Number of contacts
     */
    public /*long*/ $ContactCount;

    /**
     * 
     */
    public /*decimal*/ $SupportCost;

    /**
     * 
     */
    public /*decimal*/ $EmailCost;

}

/**
 * 
 */
class ValidationError
{
    /**
     * 
     */
    public /*string*/ $TXTRecord;

    /**
     * 
     */
    public /*string*/ $Error;

}

/**
 * 
 */
class ValidationStatus
{
    /**
     * 
     */
    public /*bool*/ $IsValid;

    /**
     * 
     */
    public /*Array<\ElasticEmailEnums\ValidationError>*/ $Errors;

    /**
     * 
     */
    public /*string*/ $Log;

}

/**
 * Notification webhook setting
 */
class Webhook
{
    /**
     * Public webhook ID
     */
    public /*string*/ $WebhookID;

    /**
     * Filename
     */
    public /*string*/ $Name;

    /**
     * Creation date.
     */
    public /*?DateTime*/ $DateCreated;

    /**
     * Last change date
     */
    public /*?DateTime*/ $DateUpdated;

    /**
     * URL of notification.
     */
    public /*string*/ $URL;

    /**
     * 
     */
    public /*bool*/ $NotifyOncePerEmail;

    /**
     * 
     */
    public /*bool*/ $NotificationForSent;

    /**
     * 
     */
    public /*bool*/ $NotificationForOpened;

    /**
     * 
     */
    public /*bool*/ $NotificationForClicked;

    /**
     * 
     */
    public /*bool*/ $NotificationForUnsubscribed;

    /**
     * 
     */
    public /*bool*/ $NotificationForAbuseReport;

    /**
     * 
     */
    public /*bool*/ $NotificationForError;

}

/**
 * Lists web notification options of your account.
 */
class WebNotificationOptions
{
    /**
     * URL address to receive web notifications to parse and process.
     */
    public /*string*/ $WebNotificationUrl;

    /**
     * True, if you want to send web notifications for sent email. Otherwise, false
     */
    public /*bool*/ $WebNotificationForSent;

    /**
     * True, if you want to send web notifications for opened email. Otherwise, false
     */
    public /*bool*/ $WebNotificationForOpened;

    /**
     * True, if you want to send web notifications for clicked email. Otherwise, false
     */
    public /*bool*/ $WebNotificationForClicked;

    /**
     * True, if you want to send web notifications for unsubscribed email. Otherwise, false
     */
    public /*bool*/ $WebnotificationForUnsubscribed;

    /**
     * True, if you want to send web notifications for complaint email. Otherwise, false
     */
    public /*bool*/ $WebNotificationForAbuse;

    /**
     * True, if you want to send web notifications for bounced email. Otherwise, false
     */
    public /*bool*/ $WebNotificationForError;

    /**
     * True, if you want to receive notifications for each type only once per email. Otherwise, false
     */
    public /*bool*/ $WebNotificationNotifyOncePerEmail;

}

}
