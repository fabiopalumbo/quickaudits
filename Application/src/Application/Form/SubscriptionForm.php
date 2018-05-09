<?php
namespace Application\Form;

use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\ServiceLocatorInterface;
// use Zend\Form\Form;

class SubscriptionForm extends BillingDetailForm
{
    /**
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;
    
    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
        return $this;
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    public function getTranslator()
    {
        return $this->getServiceLocator()->get('translator');
    }
    
    /**
     * 
     * @param \Application\Model\CountryTable $countryTable
     * @param \Application\Model\MembershipTable $membershipTable
     */
    public function __construct($countryTable, $membershipTable, $sl)
    {
        // we want to ignore the name passed
        parent::__construct($countryTable);
        
        $this->setServiceLocator($sl);
        
        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $auth = new AuthenticationService();
        $membership = $membershipTable->fetchOrganizationMembership($auth->getIdentity()->id_organization);
        
        if ($membership->upgrade)
        {
            $membershipUpgrade = $membershipTable->getById($membership->upgrade);
            
            $minUsers = $membershipUpgrade->min_users;
            
            $this->add(array(
                'name' => 'id_membership',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => array($membershipUpgrade->id=>$membershipUpgrade->name),
//                     'disable_inarray_validator' => true
                ),
                'attributes' => array(
                    'class' => 'form-control',
                ),
            ));
        }
        else
        {
            $minUsers = $membership->min_users;
            
            $this->add(array(
                'name' => 'id_membership',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => array($membership->id_membership=>$membership->membership),
//                     'disable_inarray_validator' => true
                ),
                'attributes' => array(
                    'class' => 'form-control',
                ),
            ));
        }
        
        $this->add(array(
            'name' => 'max_users',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control positive-integer',
                'data-min' => $minUsers,
            ),
        ));
        
        $this->add(array(
            'name' => 'billing_period',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => array('month'=>$this->getTranslator()->translate('Monthly'),'year'=>$this->getTranslator()->translate('Yearly')),
                'disable_inarray_validator' => true
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'update_cc',
            'options' => array(
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));
        
        $this->add(array(
            'name' => 'password',
            'type' => 'Zend\Form\Element\Password',
            'options' => array(
                'label' => 'Password',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'placeholder' => 'Password'
            ),
        ));
    }
}