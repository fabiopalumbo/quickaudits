<?php
namespace Application\Filter;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Authentication\AuthenticationService;

class SubscriptionFilter extends BillingDetailFilter
{
    /**
     * 
     * @var ServiceLocatorInterface
     */
    private $serviceManager;
    
    /**
     * 
     * @var AuthenticationService
     */
    private $auth;
    
    protected $translator;
    
    /**
     * 
     * @param ServiceLocatorInterface
     */
    public function __construct($sm) {
        
        parent::__construct();

        $this->auth = new AuthenticationService();
        $this->serviceManager = $sm;
        
        $this->add(array(
            'name'     => 'id_membership',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
        
        $this->add(array(
            'name'     => 'max_users',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
            'filters'  => array(
                array('name' => 'Int'),
            ),
        ));
        
        
        $this->add(array(
                'name'     => 'password',
                'required' => true,
                'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 6,
                            'max'      => 10,
                        ),
                    ),
                ),
        ));
        
    }
    
    public function isValid($context = null)
    {
        if (!$this->get('update_cc')->getValue())
        {
            // set required false to billing details inputs if there is no update on the credit card
            $this->get('address')->setRequired(false);
            $this->get('city')->setRequired(false);
            $this->get('id_country')->setRequired(false);
            $this->get('id_state')->setRequired(false);
            $this->get('postcode')->setRequired(false);
            $this->get('cardtype')->setRequired(false);
            $this->get('cardnumber')->setRequired(false);
            $this->get('exp_month')->setRequired(false);
            $this->get('exp_year')->setRequired(false);
            $this->get('cardholder_name')->setRequired(false);
        }
        
        // validate max and min amount of users allowed by the organization and the selected product
        // get total organization active users
        $organizationTable = $this->serviceManager->get('Application\Model\OrganizationTable');
        $currentSubscription = $organizationTable->fetchCurrentSubscription($this->auth->getIdentity()->id_organization);
        $minUsersAllowed = $currentSubscription->max_users;
        
        // get min users allowed for selected membership
        $membershipTable = $this->serviceManager->get('Application\Model\MembershipTable');
        $membership = $membershipTable->getById($this->get('id_membership')->getValue());

        // validate total users selection
        $validatorBorders = array();
        if (!$currentSubscription->end_date)
            $validatorBorders['min'] = $minUsersAllowed > $membership->min_users ? $minUsersAllowed : $membership->min_users;
        else 
            $validatorBorders['min'] = $membership->min_users;
        
        $validator = new \Zend\Validator\GreaterThan($validatorBorders);
        $validator->setInclusive(true);
        if ($membership->min_users > $this->get('max_users')->getValue())
            $validator->setMessage(sprintf($this->getTranslator()->translate('The minimal amount of users for the selected Product is %s.'),$membership->min_users));
        elseif ($minUsersAllowed > $this->get('max_users')->getValue())
            $validator->setMessage(sprintf($this->getTranslator()->translate('Please contact our sales department at support@quickaudits.io to set a lower amount of users.')));
        $this->get('max_users')->getValidatorChain()->addValidator($validator);
        
        // validate billing periods selections
        $validator = new \Zend\Validator\Callback(function($value){
            $organizationTable = $this->serviceManager->get('Application\Model\OrganizationTable');
            $currentSubscription = $organizationTable->fetchCurrentSubscription($this->auth->getIdentity()->id_organization);
            // some validation
            return $currentSubscription->billing_period == 'year' && $this->get('billing_period')->getValue()=='month' && !$currentSubscription->end_date ? false : true;
        });
        $validator->setMessage($this->getTranslator()->translate('If you want to downgrade your subscription to a monthly billing period, please contact our sales department at support@quickaudits.io'));
        $this->get('billing_period')->getValidatorChain()->addValidator($validator);
        
        // confirm user password belongs to the current user
        $validator = new \Zend\Validator\Callback(function($value){
            $userTable = $this->serviceManager->get('Application\Model\UserTable');
            return $userTable->confirmUserPassword($this->auth->getIdentity()->id, $this->get('password')->getValue());
        });
        $validator->setMessage($this->getTranslator()->translate('The password you entered does not match with your current password. Please verify your details and try again.'));
        $this->get('password')->getValidatorChain()->addValidator($validator);
        
        // validate the user entered different values for the plan details
        if ($currentSubscription->id_membership==$this->get('id_membership')->getValue() &&
            $currentSubscription->max_users==$this->get('max_users')->getValue() &&
            $currentSubscription->billing_period==$this->get('billing_period')->getValue() && 
            !$currentSubscription->end_date && !$currentSubscription->in_trial)
        {
            throw new \Exception($this->getTranslator()->translate('You have to change at least one value from your plan details to update it.'));
        }
        
        return parent::isValid($context);
    }
    
    public function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->serviceManager->get('translator');
        }
        return $this->translator;
    }
}   