<?php
namespace Application\Form;

use Zend\Form\Fieldset;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Application\Model\ProjectChannel;
// use Application\Model\FormTable;

class ProjectChannelFieldset extends Fieldset 
{
    
    public function __construct($forms)
    {
        parent::__construct('project_channel');
        
        $this->setHydrator(new ClassMethodsHydrator(false))->setObject(new ProjectChannel());

        $this->add(array(
            'name' => 'id_channel',
            'type' => 'Zend\Form\Element\Hidden',
        ));
        
        $this->add(array(
            'name' => 'channel',
            'type' => 'Zend\Form\Element\Hidden',
        ));
        
        $this->add(array(
            'name' => 'id_form',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'select-chosen form-control',
            ),
            'options' => array(
                'empty_option' => '',
                'value_options' => $forms,
            ),
        ));
    }
}