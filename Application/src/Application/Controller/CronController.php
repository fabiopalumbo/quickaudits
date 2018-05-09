<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;

/**
 * CronController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class CronController extends AbstractActionController
{    
    public function getTranslator()
    {
        return $this->getServiceLocator()->get('translator');
    }
    
    /**
     * Execute the script running the following command: "php /var/www/app/public/index.php cron execute-payment"
     * @throws \RuntimeException
     */
    public function executePaymentAction()
    {
        $request = $this->getRequest();
    
        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest){
            throw new \RuntimeException('You can only use this action from a console!');
        }
        
        $organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
        $subscriptions = $organizationTable->fetchAllSubscriptions(array('active'=>'1', 'next_billing_date'=>date('Y-m-d'),'trial'=>'0'));
        
        foreach ($subscriptions as $subscription)
        {
            /* @var $subscription \Application\Model\OrganizationSubscription */
            if (!$subscription->end_date)
            {
                // make payment if its not end date and not trial
                try {
                    
                    $organizationTable->executePayment($subscription);
                    
                } catch (\Exception $e) {
                    // log or send email
                }
            }
            else
            {
                // if end date is assigned I disable the subscription
                $organizationTable->disableSubscriptionFromCron($subscription);
            }                            
        }
        
        // log cron execution
        $writer = new \Zend\Log\Writer\Stream(APPLICATION_PATH.'/logs/cron');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('executePaymentAction');
    }    
}