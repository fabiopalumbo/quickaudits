<?php
namespace Admin\Form;

use Zend\Form\Form;
use Admin\Model\RoleTable;

class UserForm extends Form
{
    /**
     * 
     * @var RoleTable
     */
    protected $roleTable;
    
    /**
     * 
     * @param RoleTable $roleTable
     */
    public function __construct(RoleTable $roleTable)
    {
        // we want to ignore the name passed
        parent::__construct('user');
        
        $this->roleTable = $roleTable;
        

        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
                'name' => 'firstname',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'First name'
                ),
        ));
        
        $this->add(array(
                'name' => 'lastname',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'Last name'
                ),
        ));
        
        $this->add(array(
                'name' => 'email',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
                        'placeholder' => 'Email'
                ),
        ));
        
        $this->add(array(
                'name'    => 'id_role',
                'type'    => 'Zend\Form\Element\Select',
                'options' => array(
                        'value_options' => $this->getOptionsForRoleSelect(),
                        'empty_option' => 'Elija una opción'
                ),
                'attributes' => array(
                        'class' => 'form-control'
                )
        ));
                
        $this->add(array(
                'name' => 'submit',
                'type' => 'Submit',
                'attributes' => array(
                        'value' => 'Go',
                        'class' => 'btn btn-primary',
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
    
}