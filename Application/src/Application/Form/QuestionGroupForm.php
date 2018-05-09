<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Session\Container;

class QuestionGroupForm extends Form
{
    public function __construct()
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
                'name' => 'name',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                ),
        ));
        
        $session = new Container('role');
        if ($session->role->membership->package != 'basic')
        {
            $this->add(array(
                'type' => 'Zend\Form\Element\Checkbox',
                'name' => 'is_fatal',
                'options' => array(
                    'use_hidden_element' => true,
                    'checked_value' => '1',
                    'unchecked_value' => '0'
                )
            ));
            $this->add(array(
                'type' => 'Zend\Form\Element\Checkbox',
                'name' => 'ml_fatal',
                'options' => array(
                    'use_hidden_element' => true,
                    'checked_value' => '1',
                    'unchecked_value' => '0'
                )
            ));
        }

        $this->add(array(
            'name' => 'order',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control integer',
                'maxlength' => 2
            ),
        ));
        
        $this->add(array(
                'name' => 'submit',
                'type' => 'Submit',
                'attributes' => array(
                        'value' => 'Save Changes',
                        'class' => 'btn btn-sm btn-primary',
                        'id' => 'submitbutton',
                ),
        ));
    }
}