<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;

class RememberPasswordFilter extends InputFilter
{
    /**
     * 
     * @param Zend\Db\Adapter\Adapter $dbAdapter
     */    
    public function __construct($dbAdapter) {
        
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
                        'name'=>'Db\RecordExists',
                        'options'=>array(
                            'table'=>'users',
                            'field'=>'email',
                            'adapter'=>$dbAdapter
                        )
                    ),
                ),
        ));
    }
}