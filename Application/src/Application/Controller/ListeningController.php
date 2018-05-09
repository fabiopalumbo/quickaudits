<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Model\Listening;
use Application\Filter\ListeningFilter;
use Application\Form\ListeningForm;
use Zend\View\Model\JsonModel;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * ListeningController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class ListeningController extends AbstractActionController
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
            $this->currentTable = $sm->get('Application\Model\ListeningTable');
        }
        return $this->currentTable;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $project = $this->params()->fromQuery('project');
        $channel = $this->params()->fromQuery('channel');
        $language = $this->params()->fromQuery('language');
        $dateFrom = $this->params()->fromQuery('date_from');
        $dateTo = $this->params()->fromQuery('date_to');
        $active = $this->params()->fromQuery('active');
        $agent = $this->params()->fromQuery('agent');
        $qaAgent = $this->params()->fromQuery('qa_agent');
        $organization = $this->auth->getIdentity()->id_organization;
        
        $filter = array();
        
        if ($project)
            $filter['project'] = $project;
        
        if ($channel)
            $filter['channel'] = $channel;
        
        if ($language)
            $filter['language'] = $language;
        
        if ($dateFrom)
            $filter['date_from'] = $dateFrom;
        
        if ($dateTo)
            $filter['date_to'] = $dateTo;
        
        if (is_numeric($active))
            $filter['active'] = $active;
        
        if ($agent)
            $filter['agent'] = $agent;
        
        if ($qaAgent)
            $filter['qa_agent'] = $qaAgent;
        
        if ($organization)
            $filter['organization'] = $organization;
        
        // grab the paginator from the RoleTable
        $paginator = $this->getCurrentTable()->fetchAll(true, $filter);
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('organization'=>$organization));
        
        $channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
        $channels = $channelTable->fetchAll(false);
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $languages = $languageTable->fetchAll(false);
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'filter' => $filter,
            'projects' => $projects,
            'channels' => $channels,
            'languages' => $languages,
        ));
    }
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        
        $entity->listenings_answers->buffer();
        
        $groupsWeights = array();
        foreach ($entity->listenings_answers as $item)
        {
            $groupsWeights[$item->id_group]+=$item->weight;
        }
        
        $entity->listenings_answers->rewind();
        
        $groupsScores = array();
        foreach ($entity->listenings_answers as $item)
        {
            if (!isset($groupsScores[$item->id_group]))
                $groupsScores[$item->id_group]=0;
            
            $maxAnswer = $item->answers - 1;
            $answer = $item->answer;
            $groupsScores[$item->id_group]+=($item->answer*$item->weight_percentage)/($item->answers-1);
            
            if ($item->is_fatal && $item->answer)
                $groupsScores[$item->id_group]=100;

            if ($item->ml_fatal && $item->answer)
                $groupsScores[$item->id_group]=40;
        }        

        return new ViewModel(array(
            'id'    => $id,
            'entity' => $entity,
            'groupsWeights' => $groupsWeights,
            'groupsScores' => $groupsScores,
        ));
    }
    
    public function addAction()
    {
        die('ok');
        $m = (int) $this->params()->fromQuery('m',0);
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $this->getServiceLocator()->get('Application\Model\ProjectTable')
            ->fetchAllAgentProjects(array('active'=>1,'id_user'=>$this->auth->getIdentity()->id,'user_project_active'=>1));
        
        $form = new ListeningForm($projects);

        return new ViewModel(array('form'=>$form,'m'=>$m));
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
    
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
    
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
    
        $entity->listenings_answers->buffer();
    
        $form = new ListeningForm();
    
        $form->bind($entity);
    
        $groupsWeights = array();
        foreach ($entity->listenings_answers as $item)
        {
            $groupsWeights[$item->id_group]+=$item->weight;
        }
    
        $entity->listenings_answers->rewind();
    
        $groupsScores = array();
        foreach ($entity->listenings_answers as $item)
        {
            if (!isset($groupsScores[$item->id_group]))
                $groupsScores[$item->id_group]=0;
    
            $maxAnswer = $item->answers - 1;
            $answer = $item->answer;
            $groupsScores[$item->id_group]+=($item->answer*$item->weight_percentage)/($item->answers-1);
    
            if ($item->is_fatal && $item->answer)
                $groupsScores[$item->id_group]=100;

            if ($item->ml_fatal && $item->answer)
                $groupsScores[$item->id_group]=40;
        }
    
        return new ViewModel(array(
            'id' => $id,
            'form' => $form,
            'entity' => $entity,
            'groupsWeights' => $groupsWeights,
            'groupsScores' => $groupsScores,
        ));
    }

    public function saveAction()
    {
        $id = (int) $this->params()->fromPost('id', 0);
        $request = $this->getRequest();
        $post = $request->getPost();
        if ($id)
        {
            try {
                
                $listening = $this->getCurrentTable()->getById($id);
                
                $listening->listenings_answers->buffer();
                
                $post->id_project = $listening->id_project;
                $post->id_channel = $listening->id_channel;
                $post->id_agent = $listening->id_agent;
                $post->id_language = $listening->id_language;
            }
            catch (\Exception $e) {
                return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
            }
        }
        
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $this->getServiceLocator()->get('Application\Model\ProjectTable')
            ->fetchAllAgentProjects(array('active'=>1,'id_user'=>$this->auth->getIdentity()->id,'user_project_active'=>1));
        
        $form = new ListeningForm($projects);
        
        if ($request->isPost()) {
        
            try {
        
                $listening = $listening?:new Listening();
                $form->setInputFilter(new ListeningFilter());
                $form->setData($post);
                
                if ($form->isValid() || !$form->getMessages()) {

                    $listening->exchangeArray($form->getData());
                    
                    $this->getCurrentTable()->save($listening);
        
                    return new JsonModel(array('success'=>true,'url'=>$this->url()->fromRoute('application/default', array('controller'=>'listening','action' => 'view','id'=>$listening->id))));
                }
                else
                {
                    return new JsonModel(array('success'=>false,'message'=>$form->getMessages(),'is_valid'=>false));
                }
                
        
            } catch (\Exception $e) {
                return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
            }
        }
         

    }
    
    public function changeStatusAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening', 'action' => 'index'));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->delete($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'listening','action'=>'view','id'=>$id));
        }
        
        $entity->listenings_answers->buffer();
        
        $groupsWeights = array();
        foreach ($entity->listenings_answers as $item)
        {
            $groupsWeights[$item->id_group]+=$item->weight;
        }
        
        $entity->listenings_answers->rewind();
        
        $groupsScores = array();
        foreach ($entity->listenings_answers as $item)
        {
            if (!isset($groupsScores[$item->id_group]))
                $groupsScores[$item->id_group]=0;
            
            $maxAnswer = $item->answers - 1;
            $answer = $item->answer;
            $groupsScores[$item->id_group]+=($item->answer*$item->weight_percentage)/($item->answers-1);
            
            if ($item->is_fatal && $item->answer)
                $groupsScores[$item->id_group]=100;

            if ($item->ml_fatal && $item->answer)
                $groupsScores[$item->id_group]=40;
        }        

        return new ViewModel(array(
            'id'    => $id,
            'entity' => $entity,
            'groupsWeights' => $groupsWeights,
            'groupsScores' => $groupsScores,
        ));
    }

    public function exportAction()
    {

        $headAnswers = [];
        $questionsAnswers = [];

        $id_project = (int) $this->params()->fromRoute('id', 0);

        $listeningsTable = $this->getServiceLocator()->get('Application\Model\ListeningTable');

        //$listenings = $listeningsTable->fetchAll(false, ['project'=>$id_project]);
        $listeningsFields = ['l_score' => 'score', 'l_created' => 'created', 'l_qa_agent' => 'qa_agent_fullname', 'l_is_public' => 'is_public', 'pr_id' => 'id_project', 'l_comments' => 'comments' ];

        if($id_project == 676)
        {
            $listeningsFields['l_pnorte_room'] = 'pnorte_room';
            $listeningsFields['l_pnorte_name'] = 'pnorte_name';
            $listeningsFields['l_pnorte_arrival'] = 'pnorte_arrival';
            $listeningsFields['l_pnorte_departure'] = 'pnorte_departure';
            $listeningsFields['l_pnorte_company'] = 'pnorte_company';
            $listeningsFields['l_pnorte_city'] = 'pnorte_city';
            $listeningsFields['l_pnorte_country'] = 'pnorte_country';
            $listeningsFields['l_pnorte_email'] = 'pnorte_email';
            $listeningsFields['l_pnorte_radio1'] = 'pnorte_radio1';
            $listeningsFields['l_pnorte_radio2'] = 'pnorte_radio2';
            $listeningsFields['l_pnorte_recommend'] = 'pnorte_recommend';
        }

        $listening_answers = $listeningsTable->fetchAllListeningAnswers(['id_project'=>$id_project, 'order'=>['l.id_form' => 'asc', 'l.id' => 'desc', 'fq.order'=>'asc'],
            'include'=>[
                'listenings'=>$listeningsFields,
                'forms'=>['f_id' => 'id', 'f_name' => 'name'],
                'agents'=>['l_agent' => 'name']
            ]
        ]);

        $viewModel = new ViewModel(compact('listening_answers')); 

        $viewModel->setTerminal(true);
        $viewModel->setTemplate('application/listening/export');

        return $viewModel;
    }
}