<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;

class UserProjectFilter extends InputFilter
{
    public function __construct() {
        
        $this->add(array(
                    'name'     => 'id_project_role',
                    'required' => true,
                    'filters'  => array(
                            array('name' => 'Int'),
                    ),
            ));
        
        $this->add(array(
            'name'     => 'id_project',
            'required' => true,
            'filters'  => array(
                array('name' => 'Int'),
            ),
        ));
    }
}