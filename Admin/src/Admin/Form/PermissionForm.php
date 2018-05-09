<?php
namespace Admin\Form;

use Zend\Form\Form;

class PermissionForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('permission');

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
                        'placeholder' => 'Name'
                ),
        ));
        
        
        $this->add(array(
                'name' => 'category',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'Category'
                ),
        ));
        
        $this->add(array(
                'name' => 'key',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'Key'
                ),
        ));
        
        $this->add(array(
                'name' => 'controller',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'Controller'
                ),
        ));
        
        $this->add(array(
                'name' => 'action',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'Action'
                ),
        ));
           
        $this->add(array(
                'name' => 'submit',
                'type' => 'Submit',
                'attributes' => array(
                        'value' => 'Go',
                        'class' => 'btn btn-primary',
                        'id' => 'submitbutton',
                ),
        ));
    }
}