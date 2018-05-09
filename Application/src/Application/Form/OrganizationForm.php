<?php
namespace Application\Form;

use Zend\Form\Form;

class OrganizationForm extends Form
{
    public function __construct()
    {
        // we want to ignore the name passed
        parent::__construct();

        $this->setAttribute('class', 'form-horizontal form-bordered');
        
//         $this->add(array(
//                 'name' => 'id',
//                 'type' => 'Hidden',
//         ));
        
        $this->add(array(
            'name' => 'name',
            'type' => 'Text',
            'attributes' => array(
                    'class' => 'form-control',
//                     'placeholder' => 'Organization\'s name'
            ),
        ));
        
        $this->add(array(
            'name' => 'firstname',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
//                 'placeholder' => 'Organization\'s contact first name'
            ),
        ));
        
        $this->add(array(
            'name' => 'lastname',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
//                 'placeholder' => 'Organization\'s contact last name'
            ),
        ));
        
        $this->add(array(
            'name' => 'email',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
//                 'placeholder' => 'Organization\'s email'
            ),
        ));
        
        $this->add(array(
            'name' => 'phone',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
//                 'placeholder' => 'Organization\'s phone'
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