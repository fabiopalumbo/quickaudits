<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;

class BillingDetailFilter extends InputFilter
{
    public function __construct() {
        
        $this->add(array(
            'name'     => 'address',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'  => array(
                array('name' => 'NotEmpty'),
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 150,
                    ),
                ),
            ),
        ));
        
        $this->add(array(
            'name'     => 'city',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'  => array(
                array('name' => 'NotEmpty'),
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 150,
                    ),
                ),
            ),
        ));
        
        $this->add(array(
            'name'     => 'id_country',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
        
        $this->add(array(
            'name'     => 'id_state',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
        
        $this->add(array(
            'name'     => 'postcode',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'  => array(
                array('name' => 'NotEmpty'),
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 10,
                    ),
                ),
            ),
        ));
        
        $this->add(array(
            'name'     => 'cardnumber',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
        
        $this->add(array(
            'name'     => 'cvv2',
            'required' => false
        ));
        
        $this->add(array(
            'name'     => 'exp_month',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
        
        $this->add(array(
            'name'     => 'exp_year',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
        
        $this->add(array(
            'name'     => 'cardholder_name',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators'  => array(
                array('name' => 'NotEmpty'),
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 150,
                    ),
                ),
            ),
        ));
        
        $this->add(array(
            'name'     => 'cardtype',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
    }
}