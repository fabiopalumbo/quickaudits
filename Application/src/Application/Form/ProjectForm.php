<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Form\FormInterface;
use Application\Model\LocaleTable;

class ProjectForm extends Form implements FormInterface
{
    protected $languages;
    protected $forms;

    public function __construct($languages, $forms, LocaleTable $localeTable)
    {
        // we want to ignore the name passed
        parent::__construct('projects');
        
        $this->languages = $languages;
        $this->forms = $forms;

        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
                'name' => 'name',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                ),
        ));
        
        $this->add(array(
            'name' => 'min_performance_required',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control positive-integer',
            ),
        ));
        
//         $this->add(array(
//             'name' => 'languages',
//             'type' => 'Zend\Form\Element\Select',
//             'attributes' => array(
//                 'class' => 'select-chosen form-control',
//                 'multiple' => 'multiple',
//             ),            
//             'options' => array(
//                 'value_options' => $this->getLanguagesOptions(),
//             ),
//         ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Collection',
            'name' => 'projects_channels',
            'options' => array(
                'count' => count($this->channels),
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => new ProjectChannelFieldset($this->getFormOptions()),
            ),
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'enable_public',
            'options' => array(
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'public_by_agents',
            'options' => array(
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'be_anonymous',
            'options' => array(
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'enable_form_selector',
            'options' => array(
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));

        $this->add(array(
            'name' => 'form_selector_question',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength'=>255,
            ),
        ));
                
        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'require_public_names',
            'options' => array(
                'use_hidden_element' => true,
                'checked_value' => '1',
                'unchecked_value' => '0'
            )
        ));
        
        $this->add(array(
            'name' => 'public_description',
            'type' => 'Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength'=>255,
            ),
        ));
        
        $this->add(array(
            'name'    => 'id_locale',
            'type'    => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $localeTable->getOptionsForSelect(),
            ),
            'attributes' => array(
                'class' => 'select-chosen form-control',
                'multiple' => false
            )
        ));
        
        $this->add(array(
                'name' => 'submit',
                'type' => 'Submit',
                'attributes' => array(
                        'value' => 'Save Changes',
                        'class' => 'btn btn-sm btn-primary',
                        'id' => 'submitbutton',
                ),
        ));
        
    }
    
    public function getLanguagesOptions()
    {
        $data = $this->languages;
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }

        return $selectData;
    }
    
    public function getFormOptions()
    {
        $data = $this->forms;
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
}