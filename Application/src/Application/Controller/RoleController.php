<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Model\Role;
use Application\Filter\RoleFilter;
use Application\Form\RoleForm;

/**
 * RoleController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class RoleController extends AbstractActionController
{
    protected $currentTable;
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\RoleTable');
        }
        return $this->currentTable;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $keyword = $this->params()->fromQuery('keyword');
        
        $filter = array();
        
        if ($keyword)
            $filter['keyword'] = $keyword;
        
        // grab the paginator from the RoleTable
        $paginator = $this->getCurrentTable()->fetchAll(true, $filter);
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'filter' => $filter,
        ));
    }
    
    public function addAction()
    {
        
        $permissionTable = $this->getServiceLocator()->get('Application\Model\PermissionTable');
        $permissions = $permissionTable->fetchAll();
        
        $form = new RoleForm();
        $form->populateValues(array('permissions'=>$permissions));
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            try {
                
                $role = new Role();
                $form->setInputFilter(new RoleFilter());
                $form->setData($request->getPost());
                
                if ($form->isValid()) {
                    $role->exchangeArray($form->getData());
                    $this->getCurrentTable()->save($role);
                    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'role', 'action' => 'index'));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
         
        return array('form' => $form, 'error' => $error);
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'role', 'action' => 'add'));
        }
        
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'role', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'role', 'action' => 'index'));
        }
        
        $permissionTable = $this->getServiceLocator()->get('Application\Model\PermissionTable');
        $permissions = $permissionTable->fetchAll(false, $id);
        
        $form = new RoleForm();
        $form->populateValues(array('permissions'=>$permissions));
              
        $form->bind($entity);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
         try {
             $form->setInputFilter(new RoleFilter());
             $form->setData($request->getPost());
             
             if ($form->isValid()) {
                 $this->getCurrentTable()->save($entity);
             
                 return $this->redirect()->toRoute('application/default', array('controller'=>'role', 'action' => 'index'));
             }                 
         } catch (\Exception $e) {
             $error = $e->getMessage();
         }
        }
        
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error
        );
    }
}