<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;

class ChangePasswordFilter extends InputFilter
{
    public function __construct() {        
        $this->add(array(
                'name'     => 'password',
                'required' => true,
                'filters'  => array(
                        array('name' => 'StripTags'),
                        array('name' => 'StringTrim'),
                ),
                'validators' => array(
                        array(
                                'name'    => 'StringLength',
                                'options' => array(
                                        'encoding' => 'UTF-8',
                                        'min'      => 6,
                                        'max'      => 10,
                                ),
                        ),
                ),
        ));
        
        $this->add(array(
            'name'     => 'confirm-password',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min'      => 6,
                        'max'      => 10,
                    ),
                ),
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => 'password',
                    ),
                ),
            ),
        ));
    }
}