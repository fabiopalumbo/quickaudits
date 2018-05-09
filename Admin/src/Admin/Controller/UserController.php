<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use Admin\Form\UserForm;
/**
 * UserController
 *
 * @author
 *
 * @version
 *
 */
class UserController extends AbstractActionController
{
    protected $userTable;
    protected $roleTable;
    
    public function getUserTable()
    {
        if (!$this->userTable) {
            $sm = $this->getServiceLocator();
            $this->userTable = $sm->get('Admin\Model\UserTable');
        }
        return $this->userTable;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction ()
    {
        // grab the paginator from the RoleTable
         $paginator = $this->getUserTable()->fetchAll(true);
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
         
         $roleTable = $this->getServiceLocator()->get('Admin\Model\RoleTable');
         $form = new UserForm($roleTable);
         $form->get('submit')->setValue('Add');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $user = new User();
             $form->setInputFilter($user->getInputFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $user->exchangeArray($form->getData());
                 $this->getUserTable()->saveUser($user);

                 // Redirect to list of users
                 return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'index'));
             }
         }
         
         return array('form' => $form);
     }
    
    
     public function editAction()
     {
         $id = (int) $this->params()->fromRoute('id', 0);
         if (!$id) {
             return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'add'));
         }

         // Get the User with the specified id.  An exception is thrown
         // if it cannot be found, in which case go to the index page.
         try {
             $user = $this->getUserTable()->getUser($id);
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'index'));
         }
         
         $roleTable = $this->getServiceLocator()->get('Admin\Model\RoleTable');
         $form = new UserForm($roleTable);         
         $form->bind($user);
         $form->get('submit')->setAttribute('value', 'Edit');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setInputFilter($user->getInputFilter());
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 $this->getUserTable()->saveUser($user);

                 // Redirect to list of users
                 return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'index'));
             }
         }

         return array(
             'id' => $id,
             'form' => $form,
         );
     }
    
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = (int) $request->getPost('id');
            $this->getUserTable()->deleteUser($id);
        
            // Redirect to list of users
            return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        return array(
                'id'    => $id,
                'user' => $this->getUserTable()->getUser($id)
        );
    }
    
    public function viewAction(){
    
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('admin/default', array('controller'=>'user', 'action' => 'index'));
        }
    
        return array(
                'id'    => $id,
                'user' => $this->getUserTable()->getUser($id)
        );
    }
    
}