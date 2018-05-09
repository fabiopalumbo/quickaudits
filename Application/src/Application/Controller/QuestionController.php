<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Form\QuestionForm;
use Application\Model\Question;
use Application\Filter\QuestionFilter;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * QuestionController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class QuestionController extends AbstractActionController
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
            $this->currentTable = $sm->get('Application\Model\QuestionTable');
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
        $keyword = $this->params()->fromQuery('keyword');
        $active = $this->params()->fromQuery('active') != '' && is_numeric($this->params()->fromQuery('active')) ? $this->params()->fromQuery('active') : '1';
        $questionGroup = $this->params()->fromQuery('question_group');
        $organization = $this->auth->getIdentity()->id_organization;
        $m = $this->params()->fromQuery('m');
        
        $filter = array();
        
        if ($keyword)
            $filter['keyword'] = $keyword;
        
        if (is_numeric($active))
            $filter['active'] = $active;
        
        if ($questionGroup)
            $filter['question_group'] = $questionGroup;
        
        if ($organization)
            $filter['organization'] = $organization;
            
        // grab the paginator from the RoleTable
        $paginator = $this->getCurrentTable()->fetchAll(true, $filter);
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $questionsGroups = $questionGroupTable->fetchAll(false, array('organization'=>$organization));
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'filter' => $filter,
            'questionsGroups' => $questionsGroups,
            'm'=>$m,
            'subtitle'=>$this->getTranslator()->translate('Questions List'),
        ));
    }
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$this->getTranslator()->translate('Question Details'),
        );
    }
    
    public function addAction()
    {
        $m = $this->params()->fromQuery('m');
        
        // validate if membership allows to create more items
        $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
        if ($this->session->role->membership->package=='basic' && $items->count()>=10)
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'), array('query' => array('m' => 10)));
        
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $form = new QuestionForm($this->getServiceLocator(), $questionGroupTable->fetchAll(false, array('organization'=>$this->auth->getIdentity()->id_organization)));

        // if someone press to create a question from a specific question group set the default question group
        $questionGroup = (int) $this->params()->fromQuery('question_group', 0);
        $form->populateValues(array('id_group'=>$questionGroup));
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            try {
                
                $question = new Question();
                $form->setInputFilter(new QuestionFilter());
                $form->setData($request->getPost());
                
                if ($form->isValid()) {
                    
                    $data = $form->getData();
                    
                    $question->exchangeArray($data);
                    
                    $this->getCurrentTable()->save($question);
                    
                    if (!$data['submitandadd'])
                        return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'view', 'id' => $question->id));
                    else
                        return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action'=>'add'), array('query' => array('question_group' => $data['id_group'], 'm'=>'1')));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
         
        return new ViewModel(
            array(
                'form' => $form, 
                'error' => $error, 
                'questionGroup'=>$questionGroup,
                'm'=>$m,
                'subtitle'=>$this->getTranslator()->translate('Add new Question'),
            )
        );
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'add'));
        }
        
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
            
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'view','id'=>$id));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        
        $questionGroupTable = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable');
        $form = new QuestionForm($this->getServiceLocator(), $questionGroupTable->fetchAll(false, array('organization'=>$this->auth->getIdentity()->id_organization)));
        
        $form->bind($entity);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
         try {
             $form->setInputFilter(new QuestionFilter());
             $form->setData($request->getPost());
             
             if ($form->isValid()) {
                 
                 $data = $request->getPost();
                 
                 $this->getCurrentTable()->save($entity);
             
                 if (!$data['submitandadd'])
                     return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'view', 'id' => $id));
                 else
                     return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action'=>'add'), array('query' => array('question_group' => $data['id_group'], 'm'=>'1')));
             }                 
         } catch (\Exception $e) {
             $error = $e->getMessage();
         }
        }
        
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'subtitle'=>$this->getTranslator()->translate('Edit Question'),
        );
    }
    
    public function changeStatusAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
            
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        
        if (!$entity->active)
        {
            // validate if membership allows to create more items
            $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
            if ($this->session->role->membership->package=='basic' && $items->count()>=10)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'), array('query' => array('m' => 10)));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->changeStatus($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'question','action'=>'view','id'=>$id));
        }
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$this->getTranslator()->translate('Change Question Status'),
        );
    }
    
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
    
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
    
        try {
            $entity = $this->getCurrentTable()->getById($id);
    
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
    
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
    
        $request = $this->getRequest();
    
        if ($request->isPost()) {
            $this->getCurrentTable()->delete($id);
    
            return $this->redirect()->toRoute('application/default', array('controller'=>'question','action'=>'index'), array('query'=>array('m'=>'20')));
        }
    
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$this->getTranslator()->translate('Change Question Status'),
        );
    }
}