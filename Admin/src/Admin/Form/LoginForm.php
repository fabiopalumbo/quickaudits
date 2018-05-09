<?php
namespace Admin\Form;

use Zend\Form\Form;

class LoginForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->add(array(
                'name' => 'email',
                'type' => 'Text',
                'options' => array(
                        'label' => 'Email',
                ),
        ));
        
        $this->add(array(
                'name' => 'password',
                'type' => 'Zend\Form\Element\Password',
                'options' => array(
                        'label' => 'Password',
                ),
        ));
    }
}