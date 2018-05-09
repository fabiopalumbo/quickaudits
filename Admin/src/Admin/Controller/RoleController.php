<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Role;
use Admin\Form\RoleForm;

use Admin\Filter\RoleFilter;

/**
 * RoleController
 *
 * @author
 *
 * @version
 *
 */
class RoleController extends AbstractActionController
{
    protected $roleTable;
    
    public function getRoleTable()
    {
        if (!$this->roleTable) {
            $sm = $this->getServiceLocator();
            $this->roleTable = $sm->get('Admin\Model\RoleTable');
        }
        return $this->roleTable;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction ()
    {
        // grab the paginator from the RoleTable
         $paginator = $this->getRoleTable()->fetchAll(true);
         // set the current page to what has been passed in query string, or to 1 if none set
         $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
         // set the number of items per page to 10
         $paginator->setItemCountPerPage(10);
    
         return new ViewModel(array(
             'paginator' => $paginator
         ));
    }
    
     public function addAction()
     {
         $permissionTable = $this->getServiceLocator()->get('Admin\Model\PermissionTable');
         
         $form = new RoleForm($permissionTable);
         $form->get('submit')->setValue('Add');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $role = new Role();
             $form->setInputFilter(new RoleFilter());
             $form->setData($request->getPost());
             if ($form->isValid()) {
                 $role->exchangeArray($form->getData());
                 $this->getRoleTable()->saveRole($role);

                 // Redirect to list of roles
                 return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'index'));
             }
         }
         
        $permissions = $permissionTable->fetchAll();
         
         return array('form' => $form, 'permissions' => $permissions);
     }
    
    
     public function editAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'add'));
         }

         // Get the Role with the specified id.  An exception is thrown
         // if it cannot be found, in which case go to the index page.
         try {
             $role = $this->getRoleTable()->getRole($id);
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'index'));
         }
         
         $permissionTable = $this->getServiceLocator()->get('admin\Model\PermissionTable');

         $form  = new RoleForm($permissionTable);
         $form->bind($role);
         $form->get('submit')->setAttribute('value', 'Edit');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setInputFilter(new RoleFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $this->getRoleTable()->saveRole($role);

                 // Redirect to list of roles
                 return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'index'));
             }
         }
                  
         $permissions = $permissionTable->fetchAll();

         return array(
             'id' => $id,
             'form' => $form,
             'permissions' => $permissions,    
         );
     }
    
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'index'));
        }
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $request->getPost('id');
            $this->getRoleTable()->deleteRole($id);
                    
            // Redirect to list of roles
            return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'index'));
        }
        
        return array(
                'id'    => $id,
                'role' => $this->getRoleTable()->getRole($id)
        );
    }
    
    public function viewAction(){
        
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('admin/default', array('controller'=>'role', 'action' => 'index'));
        }
        
        return array(
                'id'    => $id,
                'role' => $this->getRoleTable()->getRole($id)
        );
    }
    
    
}