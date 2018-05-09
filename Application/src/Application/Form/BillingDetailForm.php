<?php
namespace Application\Form;

use Zend\Form\Form;

class BillingDetailForm extends Form
{
    /**
     * 
     * @param \Application\Model\CountryTable $countryTable
     */
    public function __construct($countryTable)
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
            'name' => 'address',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'name' => 'city',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'name' => 'id_country',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $countryTable->getOptionsForSelect(),
                'disable_inarray_validator' => true
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'name' => 'id_state',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'disable_inarray_validator' => true
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'name' => 'postcode',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'name' => 'cardtype',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => array('visa'=>'Visa','mastercard'=>'MasterCard','discover'=>'Discover','amex'=>'Amex'),
                'empty_option' => '',
                'disable_inarray_validator' => true
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));
        
        $this->add(array(
            'name' => 'cardnumber',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control integer',
                'maxlength' => 20
            ),
        ));
        
        $this->add(array(
            'name' => 'cvv2',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control integer',
            ),
        ));
        
        $this->add(array(
            'name' => 'exp_month',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control integer',
                'placeholder' => 'mm',
                'maxlength' => 2
            ),
        ));
        
        $this->add(array(
            'name' => 'exp_year',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control integer',
                'placeholder' => 'yyyy',
                'maxlength' => 4
            ),
        ));
        
        $this->add(array(
            'name' => 'cardholder_name',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
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