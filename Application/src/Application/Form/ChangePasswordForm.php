<?php
namespace Application\Form;

use Zend\Form\Form;

class ChangePasswordForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct();
        
//         $this->setAttribute('class', 'form-horizontal form-bordered');
        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
            'name' => 'id',
            'type' => 'Hidden',
        ));

        $this->add(array(
                'name' => 'password',
                'type' => 'Zend\Form\Element\Password',
                'options' => array(
//                         'label' => 'Password',
                ),
                'attributes' => array(
                    'class' => 'form-control',
//                     'placeholder' => 'Enter new password..'
                ),
        ));
        
        $this->add(array(
                'name' => 'confirm-password',
                'type' => 'Zend\Form\Element\Password',
                'options' => array(
//                         'label' => 'Confirm Password',
                ),
                'attributes' => array(
                    'class' => 'form-control',
//                     'placeholder' => 'Confirm new password..'
                ),
        ));
        
        $this->add(array(
            'name' => 'submit',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Change Password',
                'class' => 'btn btn-sm btn-primary',
                'id' => 'submitbutton',
            ),
        ));
    }
}