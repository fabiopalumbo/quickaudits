<?php
namespace Application\Form;

use Zend\Form\Form;

class UserProjectForm extends Form
{
    protected $projectsRoles;
    protected $projects;
    
    public function __construct($projectsRoles, $projects)
    {
        // we want to ignore the name passed
        parent::__construct('user_project');
        
        $this->projectsRoles = $projectsRoles;
        $this->projects = $projects;
        
        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
                'name'    => 'id_project_role',
                'type'    => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => $this->getOptionsForProjectRoleSelect(),
//                     'empty_option' => 'Select a Role'
                ),
                'attributes' => array(
                        'class' => 'form-control select-chosen'
                )
        ));
        
        $this->add(array(
                'name'    => 'id_project',
                'type'    => 'Zend\Form\Element\Select',
                'options' => array(
                        'value_options' => $this->getOptionsForProjectSelect(),
//                         'empty_option' => 'Select a Project'
                ),
                'attributes' => array(
                        'class' => 'form-control select-chosen'
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
    
    public function getOptionsForProjectRoleSelect()
    {
        $data = $this->projectsRoles;
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
    
    public function getOptionsForProjectSelect()
    {
        $data = $this->projects;
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
    
}