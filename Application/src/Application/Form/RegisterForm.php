<?php
namespace Application\Form;

use Zend\Form\Form;

class RegisterForm extends Form
{
    /**
     * 
     * @param Application\Model\MembershipTable $membershipTable
     */
    public function __construct($membershipTable)
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setAttribute('class', 'form-horizontal');
        
        /*For the user*/
        $this->add(array(
                'name' => 'firstname',
                'type' => 'Text',
                'options' => array(
//                         'label' => 'First name',
                ),
                'attributes' => array(
                        'class' => 'form-control input-lg',
//                         'placeholder' => 'First name'
                ),
        ));
        $this->add(array(
                'name' => 'lastname',
                'type' => 'Text',
                'options' => array(
//                         'label' => 'Last name',
                ),
                'attributes' => array(
                        'class' => 'form-control input-lg',
//                         'placeholder' => 'Last name'
                ),
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'Zend\Form\Element\Email',
            'options' => array(
//                 'label' => 'Email'
            ),
            'attributes' => array(
                'class' => 'form-control input-lg',
//                 'placeholder' => 'Email'
            ),
        ));
        
        $this->add(array(
            'name' => 'name',
            'type' => 'Text',
            'options' => array(
//                 'label' => 'Organization name',
            ),
            'attributes' => array(
                    'class' => 'form-control input-lg',
//                     'placeholder' => 'Organization name'
            ),
        ));        
        
        $this->add(array(
                'name' => 'phone',
                'type' => 'Text',
                'options' => array(
//                         'label' => 'Organization phone',
                ),
                'attributes' => array(
                        'class' => 'form-control input-lg',
//                         'placeholder' => 'Organization phone'
                ),
        ));
        
        $this->add(array(
            'name' => 'id_membership',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $membershipTable->fetchForSelect(true),
//                 'empty_option' => 'Select a product',
                'disable_inarray_validator' => true
            ),
            'attributes' => array(
                'class' => 'form-control input-lg',
            ),
        ));
    }
}