<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;

class RegisterFilter extends InputFilter
{
    /**
     * 
     * @param Zend\Db\Adapter\Adapter $dbAdapter
     */    
    public function __construct($dbAdapter) {
        
         $this->add(array(
                'name'     => 'firstname',
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
            'name'     => 'lastname',
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
                array(
                    'name'=>'EmailAddress'
                ),
                array(
                    'name'=>'Db\NoRecordExists',
                    'options'=>array(
                        'table'=>'users',
                        'field'=>'email',
                        'adapter'=>$dbAdapter
                    )  
                ),
            ),
        ));
        
//         $this->add(array(
//             'name'     => 'password',
//             'required' => true,
//             'filters'  => array(
//                 array('name' => 'StripTags'),
//                 array('name' => 'StringTrim'),
//             ),
//             'validators' => array(
//                 array(
//                     'name'    => 'StringLength',
//                     'options' => array(
//                         'encoding' => 'UTF-8',
//                         'min'      => 6,
//                         'max'      => 100,
//                     ),
//                 ),
//             ),
//         ));
        
//         $this->add(array(
//             'name'     => 'verifypassword',
//             'required' => true,
//             'filters'  => array(
//                 array('name' => 'StripTags'),
//                 array('name' => 'StringTrim'),
//             ),
//             'validators' => array(
//                 array(
//                     'name'    => 'Identical',
//                     'options' => array(
//                         'token' => 'password',
//                     ),
//                 ),
//             ),
//         ));
        
        $this->add(array(
            'name'     => 'name',
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
            'name'     => 'phone',
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
            'name'     => 'id_membership',
            'required' => true,
            'validators'  => array(
                array('name' => 'NotEmpty'),
            ),
        ));
    }
}