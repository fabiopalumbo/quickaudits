<?php
namespace Application\Form;

use Zend\Form\Form;

class ConfirmTokenForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->add(array(
            'name' => 'password',
            'type' => 'Zend\Form\Element\Password',
            'options' => array(
                'label' => 'Password',
            ),
            'attributes' => array(
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
                'placeholder' => 'Confirm Password'
            ),
        ));
    }
}