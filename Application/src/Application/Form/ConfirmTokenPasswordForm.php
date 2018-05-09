<?php
namespace Application\Form;

use Zend\Form\Form;

class ConfirmTokenPasswordForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct();
        
//         $this->setAttribute('class', 'form-horizontal form-bordered form-control-borderless');
        $this->setAttribute('class', 'form-horizontal');

        $this->add(array(
                'name' => 'password',
                'type' => 'Zend\Form\Element\Password',
                'options' => array(
                        'label' => 'Password',
                ),
                'attributes' => array(
                    'class' => 'form-control input-lg',
                    'placeholder' => 'Password'
                ),
        ));
        
        $this->add(array(
                'name' => 'confirm-password',
                'type' => 'Zend\Form\Element\Password',
                'options' => array(
                        'label' => 'Confirm Password',
                ),
                'attributes' => array(
                    'class' => 'form-control input-lg',
                    'placeholder' => 'Confirm Password'
                ),
        ));
        
        $this->add(array(
            'name' => 'reset',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Reset Password',
                'class' => 'btn btn-sm btn-primary',
            ),
        ));
    }
}