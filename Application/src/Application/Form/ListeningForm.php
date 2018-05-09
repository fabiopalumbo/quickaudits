<?php
namespace Application\Form; 

use Zend\Form\Form;

class ListeningForm extends Form
{
    protected $projects;
    
    public function __construct($projects=array())
    {
        // we want to ignore the name passed
        parent::__construct('listening');
        
        $this->projects = $projects;
        
        $this->setAttribute('class', 'form-horizontal form-bordered testing_class');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'id_form',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'score',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'score',
            )
        ));
        
        $this->add(array(
            'name' => 'id_project',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control select-chosen',
            ),
            'options' => array(
                'label' => 'Project',
                'empty_option' => 'Choose a Project',
                'value_options' => $this->getProjectOptions(),
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name' => 'id_channel',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control select-chosen',
            ),
            'options' => array(
                'label' => 'Channel',
                'empty_option' => 'Choose a Channel',
                'disable_inarray_validator' => true, // <-- disable
            ),
        ));
        
        $this->add(array(
            'name' => 'id_agent',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control select-chosen',
            ),
            'options' => array(
                'label' => 'Agent',
                'empty_option' => 'Choose an Agent',
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name' => 'id_language',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control select-chosen',
            ),
            'options' => array(
                'label' => 'Language',
                'empty_option' => 'Choose a Language',
                'disable_inarray_validator' => true, // <-- disable
            ),
        ));
        
        $this->add(array(
            'name' => 'comments',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class' => 'form-control',
                'rows' => 5,
                'placeholder' => 'Enter your comments...'
            )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Collection',
            'name' => 'listenings_answers',
            'options' => array(
                'should_create_template' => false,
                'allow_add' => true,
                'target_element' => new ListeningAnswerFieldset(),
            ),
        ));
        
    }
    
    public function getProjectOptions()
    {
        $selectData = array();
    
        foreach ($this->projects as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
}