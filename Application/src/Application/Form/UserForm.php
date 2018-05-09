<?php
namespace Application\Form;

use Zend\Form\Form;
use Application\Model\RoleTable;
use Application\Model\LanguageTable;
use Application\Model\LocaleTable;
use Application\Model\ProjectRoleTable;
use Application\Model\ProjectTable;

class UserForm extends Form
{
    /**
     * 
     * @var \Application\Model\RoleTable
     */
    protected $roleTable;
    
    /**
     * 
     * @var \Application\Model\LanguageTable
     */
    protected $languageTable;
    
    /**
     * 
     * @var \Application\Model\LocaleTable
     */
    protected $localeTable;
    
    /**
     * 
     * @param \Application\Model\RoleTable $roleTable
     * @param \Application\Model\LanguageTable $languageTable
     * @param \Application\Model\LocaleTable $localeTable
     * @param \Application\Model\ProjectRoleTable $projectRoleTable
     * @param \Application\Model\ProjectTable $projectTable
     */
    public function __construct(RoleTable $roleTable, LanguageTable $languageTable, LocaleTable $localeTable, ProjectRoleTable $projectRoleTable=NULL, ProjectTable $projectTable=NULL)
    {
        // we want to ignore the name passed
        parent::__construct('user');
        
        $this->roleTable = $roleTable;
        $this->languageTable = $languageTable;
        
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
//                         'placeholder' => 'Enter user full name..'
                ),
        ));
        
        $this->add(array(
                'name' => 'email',
                'type' => 'Email',
                'attributes' => array(
                        'class' => 'form-control',
//                         'placeholder' => 'Email'
                ),
        ));
        
        $this->add(array(
                'name'    => 'id_role',
                'type'    => 'Zend\Form\Element\Select',
                'options' => array(
                        'value_options' => $this->getOptionsForRoleSelect(),
                        'empty_option' => 'Select an option',
                        'disable_inarray_validator'=>false
                ),
                'attributes' => array(
                        'class' => 'form-control'
                ),
                
        ));
        
        $this->add(array(
            'name'    => 'id_language',
            'type'    => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->getOptionsForLanguageSelect(),
            ),
            'attributes' => array(
                'class' => 'select-chosen form-control',
                'multiple' => true
            )
        ));
        
        $this->add(array(
            'name'    => 'id_locale',
            'type'    => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $localeTable->getOptionsForSelect(),
                'disable_inarray_validator'=>false
            ),
            'attributes' => array(
                'class' => 'form-control'
            ),
        
        ));
        
        if ($projectRoleTable && $projectTable)
        {
            $this->add(array(
                'name'    => 'id_project_role',
                'type'    => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => $projectRoleTable->getOptionsForSelect(),
                    'empty_option' => 'Select a Role'
                ),
                'attributes' => array(
                    'class' => 'form-control select-chosen'
                )
            ));
            
            $this->add(array(
                'name'    => 'id_project',
                'type'    => 'Zend\Form\Element\Select',
                'options' => array(
                    'value_options' => $projectTable->getOptionsForSelect(),
                    'empty_option' => 'Select a Project'
                ),
                'attributes' => array(
                    'class' => 'form-control select-chosen'
                )
            ));
        }
                
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
    
    public function getOptionsForRoleSelect()
    {
        $table = $this->roleTable;
        $data  = $table->fetchAll();
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
    
    public function getOptionsForLanguageSelect()
    {
        $table = $this->languageTable;
        $data  = $table->fetchAll();
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
    
}