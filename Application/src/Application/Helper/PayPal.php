<?php
namespace Application\Helper;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

// use PayPal\Api\PaymentExecution;

use PayPal\Api\Amount;
use PayPal\Api\CreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
// use PayPal\Api\RedirectUrls;

use Zend\ServiceManager\ServiceManager;
use PayPal\Api\PayerInfo;

class PayPal {

    protected $serviceManager;
    protected $translator;
    
    // Replace these values by entering your own ClientId and Secret by visiting https://developer.paypal.com/webapps/developer/applications/myapps
    private $clientId;
    private $clientSecret;
    private $mode;
    private $logLevel;
    
    public function __construct(ServiceManager $sm)
    {
        $this->serviceManager = $sm;
        $config = $this->serviceManager->get('config');
        $this->clientId = $config['paypal']['client_id'];
        $this->clientSecret = $config['paypal']['client_secret'];
        $this->mode = $config['paypal']['mode'];
        $this->logLevel = $config['paypal']['log_level'];
    }
    
    /**
     *
     * @return \Zend\Mvc\I18n\Translator
     */
    public function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->serviceManager->get('translator');
        }
        return $this->translator;
    }

    /**
     * Helper method for getting an APIContext for all calls
     * @param string $clientId Client ID
     * @param string $clientSecret Client Secret
     * @return PayPal\Rest\ApiContext
     */
//     function getApiContext($clientId, $clientSecret)
    private function getApiContext()
    {
    
        // #### SDK configuration
        // Register the sdk_config.ini file in current directory
        // as the configuration source.
        /*
         if(!defined("PP_CONFIG_PATH")) {
         define("PP_CONFIG_PATH", __DIR__);
         }
         */
    
    
        // ### Api context
        // Use an ApiContext object to authenticate
        // API calls. The clientId and clientSecret for the
        // OAuthTokenCredential class can be retrieved from
        // developer.paypal.com
    
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                $this->clientId,
                $this->clientSecret
            )
        );
    
        // Comment this line out and uncomment the PP_CONFIG_PATH
        // 'define' block if you want to use static file
        // based configuration
    
        $apiContext->setConfig(
            array(
                'mode' => $this->mode,
                'log.LogEnabled' => true,
                'log.FileName' => '../PayPal.log',
                'log.LogLevel' => $this->logLevel, // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'validation.level' => 'log',
                'cache.enabled' => false,
                // 'http.CURLOPT_CONNECTTIMEOUT' => 30
                // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            )
        );
    
        // Partner Attribution Id
        // Use this header if you are a PayPal partner. Specify a unique BN Code to receive revenue attribution.
        // To learn more or to request a BN Code, contact your Partner Manager or visit the PayPal Partner Portal
        // $apiContext->addRequestHeader('PayPal-Partner-Attribution-Id', '123123123');
    
        return $apiContext;
    }

    /**
     * Save a credit card with paypal
     *
     * This helps you avoid the hassle of securely storing credit
     * card information on your site. PayPal provides a credit card
     * id that you can use for charging future payments.
     *
     * @param array $params	credit card parameters
     * @throws \Exception
     * @return \PayPal\Api\CreditCard
     */
    public function saveCreditCard($params) {
        
        try {

            $card = new CreditCard();
            
            $card->setType($params['type']);
            $card->setNumber($params['number']);
            $card->setExpireMonth($params['expire_month']);
            $card->setExpireYear($params['expire_year']);
            $card->setCvv2($params['cvv2']);
             
            $card->create($this->getApiContext());
            
            return $card->getId();
            
        } catch (\Exception $e) {
            throw new \Exception($this->getTranslator()->translate('An error ocurred processing your credit card. Please check the credit card details and try again.'));
        }
    }

    /**
     *
     * @param string $cardId credit card id obtained from
     * a previous create API call.
     * @return \PayPal\Api\CreditCard
     */
    public function getCreditCard($cardId) {
        return CreditCard::get($cardId, $this->getApiContext());
    }
    
    public function deleteCreditCard($cardId) {
        $card = $this->getCreditCard($cardId);
        $card->delete($this->getApiContext());
    }
    
    /**
     * Create a payment using a previously obtained
     * credit card id. The corresponding credit
     * card is used as the funding instrument.
     *
     * @param string $creditCardId credit card id
     * @param string $total Payment amount with 2 decimal points
     * @param string $currency 3 letter ISO code for currency
     * @param string $paymentDesc
     */
    function makePaymentUsingCC($creditCardId, $total, $currency, $paymentDesc, $firstName, $lastName, $email) {
    
        $ccToken = new CreditCardToken();
        $ccToken->setCreditCardId($creditCardId);
    
        $fi = new FundingInstrument();
        $fi->setCreditCardToken($ccToken);
        
        $payerInfo = new PayerInfo();
        $payerInfo->setFirstName($firstName);
        $payerInfo->setLastName($lastName);
        $payerInfo->setEmail($email);
        
        $payer = new Payer();
        $payer->setPaymentMethod("credit_card");
        $payer->setFundingInstruments(array($fi));
        $payer->setPayerInfo($payerInfo);
    
        // Specify the payment amount.
        $amount = new Amount();
        $amount->setCurrency($currency);
        $amount->setTotal($total);
        // ###Transaction
        // A transaction defines the contract of a
        // payment - what is the payment for and who
        // is fulfilling it. Transaction is created with
        // a `Payee` and `Amount` types
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setDescription($paymentDesc);
    
        $payment = new Payment();
        $payment->setIntent("sale");
        $payment->setPayer($payer);
        $payment->setTransactions(array($transaction));
    
        $payment->create($this->getApiContext());
        return $payment;
    }
    
    /**
     * Retrieves the payment information based on PaymentID from Paypal APIs
     *
     * @param $paymentId
     *
     * @return Payment
     */
    function getPaymentDetails($paymentId) {
        $payment = Payment::get($paymentId, $this->getApiContext());
        return $payment;
    }
}