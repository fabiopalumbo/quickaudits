<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Form\SubjectForm;
use Application\Model\Subject;
use Application\Filter\SubjectFilter;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * SubjectController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class SubjectController extends AbstractActionController
{
    protected $currentTable;
    protected $auth;
    protected $session;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\SubjectTable');
        }
        return $this->currentTable;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $keyword = $this->params()->fromQuery('keyword');
        $active = $this->params()->fromQuery('active');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        if ($keyword)
            $filter['keyword'] = $keyword;
        
        if (is_numeric($active))
            $filter['active'] = $active;
        
        if ($organization)
            $filter['organization'] = $organization;
            
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
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        
        return array(
                'id'    => $id,
                'entity' => $entity
        );
    }
    
    public function addAction()
    {
        $form = new SubjectForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            
            try {
                
                $subject = new Subject();
                $form->setInputFilter(new SubjectFilter());
                $form->setData($request->getPost());
                
                if ($form->isValid()) {
                    
                    $data = $form->getData();
                    
                    $subject->exchangeArray($data);
                    
                    $this->getCurrentTable()->save($subject);
                    
                    if (!$data['submitandadd'])
                        return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'view', 'id' => $subject->id));
                    else
                        return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action'=>'add'));
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
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'add'));
        }
        
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
            
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'view','id'=>$id));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        
        $form = new SubjectForm();
              
        $form->bind($entity);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
         try {
             $form->setInputFilter(new SubjectFilter());
             $form->setData($request->getPost());
             
             if ($form->isValid()) {
                 
                 $this->getCurrentTable()->save($entity);
             
                 return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'view', 'id' => $id));
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
    
    public function changeStatusAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject', 'action' => 'index'));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->delete($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'subject','action'=>'view','id'=>$id));
        }
        
        return array(
                'id'    => $id,
                'entity' => $entity
        );
    }
}