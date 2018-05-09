<?php
namespace Admin\Filter;

use Zend\InputFilter\InputFilter;

class LoginFilter extends InputFilter
{
    public function __construct() {
        $this->add(array(
                'name'     => 'email',
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
                                        'min'      => 1,
                                        'max'      => 100,
                                ),
                        ),
                ),
        ));
        
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
                                        'min'      => 1,
                                        'max'      => 100,
                                ),
                        ),
                ),
        ));
    }
}