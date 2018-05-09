<?php
namespace Application\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Application\Model\Permission;

class PermissionFieldset extends Fieldset implements InputFilterProviderInterface 
{
    public function __construct()
    {
        parent::__construct('permissions');

        $this->setHydrator(new ClassMethodsHydrator(false))->setObject(new Permission());

        $this->add(array(
            'name' => 'id',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'name',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'category',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'checked',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'use_hidden_element' => true,
            ),
        ));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
        
        );
    }
}