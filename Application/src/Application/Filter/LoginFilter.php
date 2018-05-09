<?php
namespace Application\Filter;

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
//                             'messages' => array(
//                                 'stringLengthTooShort' => 'Please enter a value between 1 to 100 character!', 
//                                 'stringLengthTooLong' => 'Please enter a value between 1 to 100 character!' 
//                             ),
                        ),
                    ),
                    array(
                        'name'=>'EmailAddress',
                    ),
//                     array(
//                         'name' =>'NotEmpty', 
//                         'options' => array(
//                             'messages' => array(
//                                 \Zend\Validator\NotEmpty::IS_EMPTY => 'Please enter User Name!' 
//                             ),
//                         ),
//                     ),
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
                                'min'      => 6,
                                'max'      => 10,
                        ),
                    ),
            ),
        ));
    }
}