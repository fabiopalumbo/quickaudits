<?php
namespace Application\Form;

use Zend\Form\Form;
// use Application\Model\PermissionTable;

class RoleForm extends Form
{
//     /**
//      * 
//      * @var PermissionTable
//      */
//     protected $permissionTable;
    
//     /**
//      * 
//      * @param PermissionTable $permissionTable
//      */
//     public function __construct(PermissionTable $permissionTable)
    public function __construct()
    {
        // we want to ignore the name passed
        parent::__construct('role');
        
//         $this->permissionTable = $permissionTable;

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
                        'placeholder' => 'Name'
                ),
        ));
        
        $this->add(array(
            'name' => 'permissions',
            'type' => 'Zend\Form\Element\Collection',
            'options' => array(
                'should_create_template' => false,
                'allow_add' => true,
                'allow_remove' => true,
                'target_element' => new PermissionFieldset(),
            ),
        ));

//         $this->add(array(
//         	'name' => 'permissions',
//             'type' => 'Zend\Form\Element\MultiCheckbox',
//             'options' => array(
//                 'value_options' => $this->getOptionsForPermissionCheckbox()
//             ),
//         ));
        
        $this->add(array(
                'name' => 'submit',
                'type' => 'Submit',
                'attributes' => array(
                        'value' => 'Save Changes',
                        'class' => 'btn btn-primary btn-sm',
                        'id' => 'submitbutton',
                ),
        ));
    }
    
//     public function getOptionsForPermissionCheckbox()
//     {
//         $table = $this->permissionTable;
//         $data  = $table->fetchAll();
    
//         $selectData = array();
    
//         foreach ($data as $selectOption) {
//             $selectData[$selectOption->id] = $selectOption->name;
//         }
    
//         return $selectData;
//     }
}