<?php
namespace Application\Form;

use Zend\Form\Form;

class LanguageForm extends Form
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
                        'placeholder' => 'Enter Question..'
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