<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Model\QuestionGroup;
use Application\Form\QuestionGroupForm;
use Application\Filter\QuestionGroupFilter;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * QuestionGroupController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class QuestionGroupController extends AbstractActionController
{
    protected $currentTable;
    protected $auth;
    protected $session;
    protected $translator;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\QuestionGroupTable');
        }
        return $this->currentTable;
    }
    
    public function getTranslator()
    {
        if (!$this->translator) {
            $sm = $this->getServiceLocator();
            $this->translator = $sm->get('translator');
        }
        return $this->translator;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $subtitle = $this->getTranslator()->translate('Question Groups List');
        
        $keyword = $this->params()->fromQuery('keyword');
        $active = $this->params()->fromQuery('active') != '' && is_numeric($this->params()->fromQuery('active')) ? $this->params()->fromQuery('active') : '1';
        $organization = $this->auth->getIdentity()->id_organization;
        $m = $this->params()->fromQuery('m');
                
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
            'm'=>$m,
            'subtitle'=>$subtitle,
        ));
    }
    
    public function viewAction()
    {
        $subtitle = $this->getTranslator()->translate('Question Groups Details');
        
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        
        $questionTable = $this->getServiceLocator()->get('Application\Model\QuestionTable');
        $questions = $questionTable->fetchAll(false, array('question_group'=>$id));
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'questions' => $questions,
            'subtitle'=>$subtitle,
        );
    }
    
    public function addAction()
    {
        $subtitle = $this->getTranslator()->translate('Add new Question Group');
        
        // validate if membership allows to create more items
        $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
        if ($this->session->role->membership->package=='basic' && $items->count()>=1)
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'), array('query' => array('m' => 10)));
        
        $form = new QuestionGroupForm();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            try {
                
                $questionGroup = new QuestionGroup();
                $form->setInputFilter(new QuestionGroupFilter());
                $form->setData($request->getPost());
                
                if ($form->isValid()) {
                    $questionGroup->exchangeArray($form->getData());
                    
                    $this->getCurrentTable()->save($questionGroup);
                    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'view', 'id' => $questionGroup->id));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
         
        return new ViewModel(array('form' => $form, 'error' => $error, 'subtitle'=>$subtitle,));
    }
    
    public function editAction()
    {
        $subtitle = $this->getTranslator()->translate('Edit Question Group');
        
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'add'));
        }
        
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        
        $form = new QuestionGroupForm();
              
        $form->bind($entity);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
         try {
             $form->setInputFilter(new QuestionGroupFilter());
             $form->setData($request->getPost());
             
             if ($form->isValid()) {

                 $this->getCurrentTable()->save($entity);
             
                 return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'view', 'id' => $id));
             }                 
         } catch (\Exception $e) {
             $error = $e->getMessage();
         }
        }
        
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'subtitle'=>$subtitle,
        );
    }
    
    public function changeStatusAction()
    {
        $subtitle = $this->getTranslator()->translate('Change Question Group Status');
        
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
            
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        
        if (!$entity->active)
        {
            // validate if membership allows to create more items
            $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
            if ($this->session->role->membership->package=='basic' && $items->count()>=1)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'), array('query' => array('m' => 10)));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->changeStatus($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group','action'=>'view','id'=>$id));
        }
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$subtitle,
        );
    }
    
    public function deleteAction()
    {
        $subtitle = $this->getTranslator()->translate('Delete Question Group');
    
        $id = (int) $this->params()->fromRoute('id', 0);
    
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
    
        try {
            $entity = $this->getCurrentTable()->getById($id);
    
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
    
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group', 'action' => 'index'));
        }
    
        $request = $this->getRequest();
    
        if ($request->isPost()) {
            $this->getCurrentTable()->delete($id);
    
            return $this->redirect()->toRoute('application/default', array('controller'=>'question-group','action'=>'index'), array('query' => array('m' => 20)));
        }
        
        // get question group questions
        $questionTable = $this->getServiceLocator()->get('Application\Model\QuestionTable');
        $questions = $questionTable->fetchAll(false, array('question_group'=>$id));
    
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$subtitle,
            'totalQuestions'=>$questions->count(),
        );
    }
}