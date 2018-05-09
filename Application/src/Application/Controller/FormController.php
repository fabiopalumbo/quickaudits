<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Model\Form;
use Application\Form\FormForm;
use Application\Filter\FormFilter;
use Zend\View\Model\JsonModel;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * FormController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class FormController extends AbstractActionController
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
            $this->currentTable = $sm->get('Application\Model\FormTable');
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
            'subtitle'=>$this->getTranslator()->translate('Forms List'),
        ));
    }
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $m = $this->params()->fromQuery('m');
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        
        $groupsWeights = array();
        foreach ($entity->forms_questions as $item)
        {
            $groupsWeights[$item->id_group]+=$item->weight;
        }
        
        return new ViewModel(array(
            'id'    => $id,
            'entity' => $entity,
            'groupsWeights' => $groupsWeights,
            'subtitle'=>$this->getTranslator()->translate('Form Details'),
            'm'=>$m,
        ));
    }
    
    public function addAction()
    {
        // validate if membership allows to create more items
        $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
        if ($this->session->role->membership->package=='basic' && $items->count()>=1)
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'), array('query' => array('m' => 10)));
        
        $formEntity = new Form();
        
        $questionsGroups = $this->getServiceLocator()->get('Application\Model\QuestionGroupTable')->fetchAll(false,array('active'=>1,'organization'=>$this->auth->getIdentity()->id_organization),'qg.order asc');
        $formsQuestions = $this->getCurrentTable()->fetchAllFormsQuestions(array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization),array('id_form'=>'IS NULL'));

        $form = new FormForm($questionsGroups, $formsQuestions);
        $request = $this->getRequest();
        if ($request->isPost()) {

            try {
                
                $form->setInputFilter(new FormFilter());

                // set new order of questions & calculate weight for each question
                $post = $request->getPost();
                                
                $groupsWeight = array();
                foreach ($post['questions_groups'] as $questionGroup)
                {
                    $groupsWeight[$questionGroup['id']] = $questionGroup['group_weight'];
                }

                $formsQuestions = array();
                foreach ($post['forms_questions'] as $formQuestion)
                {
                    if ($formQuestion['question_checked'])
                    {
                        $formQuestion['weight'] = ($formQuestion['weight_percentage'] / 100) * $groupsWeight[$formQuestion['id_group']];
                    }
                    array_push($formsQuestions, $formQuestion);                                        
                }
                $post['forms_questions'] = $formsQuestions; 

                $form->setData($post);
                
                if ($form->isValid()) {

                    $formEntity->exchangeArray($form->getData());
                    
                    $this->getCurrentTable()->save($formEntity);
                    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'view', 'id' => $formEntity->id));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
echo '<pre>';
//print_r($post);
print_r($e);die;
echo '</pre>';
            }
        }
                
        return new ViewModel(
            array(
                'form'=>$form, 
                'error'=>$error,
                'subtitle'=>$this->getTranslator()->translate('Add new Form'),
            )
        );
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'add'));
        }
        
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id, false);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }        
        
        $questionsGroups = $this->getCurrentTable()->fetchAllFormsQuestionsGroups(array('active'=>1,'organization'=>$this->auth->getIdentity()->id_organization),array('id_form'=>$id),'qg.order asc');
        $formsQuestions = $this->getCurrentTable()->fetchAllFormsQuestions(array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization),array('id_form'=>array('IS NULL',$id)));
        $form = new FormForm($questionsGroups, $formsQuestions);
        
        $form->bind($entity);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
            try {
                $form->setInputFilter(new FormFilter());

                // set new order of questions & calculate weight for each question
                $post = $request->getPost();
                
                $groupsWeight = array();
                foreach ($post['questions_groups'] as $questionGroup)
                {
                    $groupsWeight[$questionGroup['id']] = $questionGroup['group_weight'];
                }
                
                $formsQuestions = array();
                foreach ($post['forms_questions'] as $formQuestion)
                {
                    if ($formQuestion['question_checked'])
                    {
                        $formQuestion['weight'] = ($formQuestion['weight_percentage'] / 100) * $groupsWeight[$formQuestion['id_group']];
                    }
                    array_push($formsQuestions, $formQuestion);
                }
                
                $post['forms_questions'] = $formsQuestions;

                $form->setData($post);

                if ($form->isValid()) {
                    
                    $this->getCurrentTable()->save($entity);
                     
                    return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'view', 'id' => $id));
                }

//                 print"<pre>";print_r($form->getMessages());print"</pre>";
//                 die;
            } catch (\Exception $e) {

                $error = $e->getMessage();
            }
        }
                
        $viewModel = new ViewModel(
            array(
                'id'=>$id,
                'form'=>$form,
                'error'=>$error,
                'subtitle'=>$this->getTranslator()->translate('Edit Form'),
            )
        );

        return $viewModel;
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
                return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
            
//             if ($entity->blocked)
//                 return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        
        if (!$entity->active)
        {
            // validate if membership allows to create more items
            $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
            
            if ($this->session->role->membership->package=='basic' && $items->count()>=1)
                return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'), array('query' => array('m' => 10)));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->delete($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'form','action'=>'view','id'=>$id));
        }
        
        $groupsWeights = array();
        foreach ($entity->forms_questions as $item)
        {
            $groupsWeights[$item->id_group]+=$item->weight;
        }
        
        return new ViewModel(array(
            'id'    => $id,
            'entity' => $entity,
            'groupsWeights' => $groupsWeights,
            'subtitle'=>$this->getTranslator()->translate('Change Form Status'),
        ));
    }
    
    public function validateAction()
    {
        try {
            
            $request = $this->getRequest();

            if ($request->isPost())
            {
                $data = $request->getPost();

                // validate groups
                $this->validateGroups($data['questions_groups']);
                
                // validate questions
                $this->validateQuestionsAnswers($data['questions_groups'], $data['forms_questions']);
                
                // validate weight
                $this->validateQuestionsWeight($data['questions_groups'], $data['forms_questions']);

                return new JsonModel(array('success'=>true));
            }
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage(),'tab'=>$e->getCode()));
        }
        
        return new JsonModel(
            array(
                'success'=>false,
                'message'=>$this->getTranslator()->translate('An unknown error happened. Please verify your data and try again.')));
    }
    
    public function validateGroups($questionsGroups)
    {
        try {
            
            $totalWeight = 0;

            foreach ($questionsGroups as $questionGroup)
            {
                // validate and sum only for selected groups
                if ($questionGroup['group'])
                {
                    if (!$questionGroup['group_weight'] && !$questionGroup['is_fatal'])
                        throw new \Exception($this->getTranslator()->translate('Each selected group must have a score bigger than 0.'), 1);
                    
                    $totalWeight+=$questionGroup['group_weight'];
                }
            }
            
            if ($totalWeight != 100)
                throw new \Exception($this->getTranslator()->translate('Question groups must sum a total of 100%'), 1);
            
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function validateQuestionsAnswers($questionsGroups, $formsQuestions)
    {
        try {
            
            $totalChecked = 0;
            
            //Create an array for each selected group and will sum the amount of selected questions for each group
            //If the amount of selected questions is equal to 0 then I throw error
            $groups = array();
            
            foreach ($questionsGroups as $questionGroup) {
                if ($questionGroup['group']) {
                    $groups[$questionGroup['id']] = 0;
                }
            }
          
            foreach ($formsQuestions as $formQuestion)
            {
                if ($formQuestion['question_checked'])
                {
                    $totalChecked++;
                    
                    if (!$formQuestion['answers'])
                    {
                        throw new \Exception($this->getTranslator()->translate('All checked questions must have answers.'), 2);
                    }
                    else 
                    {
                        if (in_array($formQuestion['question_type'], ['closed', 'inverted']) && $formQuestion['answers'] < 2 )
                            throw new \Exception($this->getTranslator()->translate('Answers must be higher than 1.'), 2);
                    }
                    
                    $groups[$formQuestion['id_group']]++;
                }
            }
            
            if ($totalChecked === 0)
            {
                throw new \Exception($this->getTranslator()->translate('You have to check at least one question and select its amount of answers.'), 2);
            }
            
            foreach ($groups as $group){
                if (!$group)
                    throw new \Exception($this->getTranslator()->translate('You have to select at least one question for each group.'), 2);
            }
            
        } catch (\Exception $e) {
            throw $e;
        }        
    }
    
    public function validateQuestionsWeight($questionsGroups, $formsQuestions)
    {
        try {

            $groups = array();
            
            // array to validate the sum of all questions weight is equal to the group total
            foreach ($questionsGroups as $key => $questionGroup) {
                if (!$questionGroup['is_fatal'] && $questionGroup['group']) {
                    $groups[$questionGroup['id']] = array('question_group'=>$questionGroup['name'],'total'=>0);
                }
            }
            
            foreach ($formsQuestions as $formQuestion)
            {
                if ($formQuestion['question_checked'])
                {
                    if (!$formQuestion['is_fatal']
                        && in_array($formQuestion['question_type'], ['closed', 'binary', 'inverted']) 
                        && (!is_numeric($formQuestion['weight_percentage']) 
                            || !$formQuestion['weight_percentage']
                        )
                    ) {
                        throw new \Exception($this->getTranslator()->translate('All closed and binary questions except fatals groups must be higher than 0%'), 3);
                    };

                    if (!$formQuestion['is_fatal']) {
                        $groups[$formQuestion['id_group']]['total'] += $formQuestion['weight_percentage'];
                    }
                }
            }

            // validate the sum of all questions weight is equal to the group total
            foreach ($groups as $group){
                if (intval($group['total']) !== 100 && intval($group['total']) > 0) {
                    throw new \Exception(sprintf($this->getTranslator()->translate('The group %s has a weight percentage of %s%%'), $group['question_group'], $group['total']), 3);
                }
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    public function getProjectChannelFormAction()
    {
        $error = '';
        
        try {
            $idProject = (int) $this->params()->fromPost('id_project', 0);
            $idChannel = (int) $this->params()->fromPost('id_channel', 0);

            $idAgent = (int) $this->params()->fromPost('id_agent', 0);
            
            if ($idProject && $idChannel)
            {
                // get form for current convination
                $form = $this->getCurrentTable()->fetchProjectChannelForm($idProject, $idChannel);
    
                $results = $this->getCurrentTable()->fetchAllFormsQuestions(array('id_form'=>$form->id,'active'=>1));
                
                $groupsWeights = array();
                
                $questions = array();
                foreach ($results as $item)
                {
                    array_push($questions, $item);
                    
                    $groupsWeights[$item->id_group]+=$item->weight;
                }
            }

            $agent = ['name' => 'Anonimous'];
            if ($idAgent)
            {
                $agentsTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                $agentObj = $agentsTable->getUser($idAgent);
                $agent['name'] = $agentObj->name;
            }
            
        } catch (\Exception $e) {
            $error = $e->getMessage();

        }
        
        $viewModel = new ViewModel(array(
                'form' => $form,
                'agent' => $agent,
                'forms_questions' => $questions,
                'groupsWeights' => $groupsWeights,
                'error' => $error,
        ));
        
        $viewModel->setTerminal(true);
        $viewModel->setTemplate('application/listening/questions');
        return $viewModel;
    }
    
    public function copyAction()
    {
        // validate if membership allows to create more items
        $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
        if ($this->session->role->membership->package=='basic' && $items->count()>=1)
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'), array('query' => array('m' => 10)));
    
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'question', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'form', 'action' => 'index'));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {

            // copy and returns the new form id
            $id = $this->getCurrentTable()->copy($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'form','action'=>'view','id'=>$id), array('query' => array('m' => 1)));
        }
        
        $groupsWeights = array();
        foreach ($entity->forms_questions as $item)
        {
            $groupsWeights[$item->id_group]+=$item->weight;
        }
        
        return new ViewModel(array(
            'id'    => $id,
            'entity' => $entity,
            'groupsWeights' => $groupsWeights,
            'subtitle'=>$this->getTranslator()->translate('Copy Form'),
            
        ));
    }
}