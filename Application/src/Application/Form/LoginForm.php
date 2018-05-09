<?php
namespace Application\Form;

use Zend\Form\Form;

class LoginForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct();
        
//         $this->setAttribute('class', 'form-horizontal form-bordered form-control-borderless');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'form-login');
//        $this->setAttribute('action', '/auth');

        $this->add(array(
                'name' => 'email',
                'type' => 'Zend\Form\Element\Email',
                'options' => array(
                        'label' => 'Email',
                ),
                'attributes' => array(
                    'class' => 'form-control input-lg',
                    'placeholder' => 'Email'
                ),
        ));
        
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
            'name' => 'login',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Login to Dashboard',
                'class' => 'btn btn-sm btn-primary',
            ),
        ));
    }
}