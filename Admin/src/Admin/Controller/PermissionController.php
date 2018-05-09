<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Permission;
use Admin\Form\PermissionForm;

use Admin\Filter\PermissionFilter;
/**
 * PermissionController
 *
 * @author
 *
 * @version
 *
 */
class PermissionController extends AbstractActionController
{
    protected $permissionTable;
    
    public function getPermissionTable()
    {
        if (!$this->permissionTable) {
            $sm = $this->getServiceLocator();
            $this->permissionTable = $sm->get('Admin\Model\PermissionTable');
        }
        return $this->permissionTable;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction ()
    {
        // grab the paginator from the RoleTable
         $paginator = $this->getPermissionTable()->fetchAll(true);
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
         $form = new PermissionForm();
         $form->get('submit')->setValue('Add');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $permission = new Permission();
             $form->setInputFilter(new PermissionFilter());
             $form->setData($request->getPost());
             if ($form->isValid()) {
                 $permission->exchangeArray($form->getData());
                 $this->getPermissionTable()->savePermission($permission);

                 // Redirect to list of permissions
                 return $this->redirect()->toRoute('admin/default', array('controller'=>'permission', 'action' => 'index'));
             }
         }
         return array('form' => $form);
     }
    
    
     public function editAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('admin/default', array('controller'=>'permission', 'action' => 'add'));
         }

         // Get the Permission with the specified id.  An exception is thrown
         // if it cannot be found, in which case go to the index page.
         try {
             $permission = $this->getPermissionTable()->getPermission($id);
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('admin/default', array('controller'=>'permission', 'action' => 'index'));
         }

         $form  = new PermissionForm();
         $form->bind($permission);
         $form->get('submit')->setAttribute('value', 'Edit');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setInputFilter(new PermissionFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $this->getPermissionTable()->savePermission($permission);

                 // Redirect to list of permissions
                 return $this->redirect()->toRoute('admin/default', array('controller'=>'permission', 'action' => 'index'));
             }
         }

         return array(
             'id' => $id,
             'form' => $form,
         );
     }
    

    public function viewAction(){
    
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('admin/default', array('controller'=>'permission', 'action' => 'index'));
        }
    
        return array(
                'id'    => $id,
                'permission' => $this->getPermissionTable()->getPermission($id)
        );
    }
}