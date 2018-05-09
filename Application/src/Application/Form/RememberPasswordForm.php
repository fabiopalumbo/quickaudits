<?php
namespace Application\Form;

use Zend\Form\Form;

class RememberPasswordForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct();
        
//         $this->setAttribute('class', 'form-horizontal form-bordered form-control-borderless');
        $this->setAttribute('class', 'form-horizontal display-none');
        $this->setAttribute('id', 'form-reminder');

        $this->add(array(
                'name' => 'email',
                'type' => 'Zend\Form\Element\Email',
                'options' => array(
                    'label' => 'Email',
                ),
                'attributes' => array(
                    'class' => 'form-control input-lg',
//                     'placeholder' => 'Enter your email'
                ),
        ));
        
        $this->add(array(
            'name' => 'remember',
            'type' => 'Submit',
            'attributes' => array(
                'value' => 'Reset Password',
                'class' => 'btn btn-sm btn-primary',
            ),
        ));
    }
}