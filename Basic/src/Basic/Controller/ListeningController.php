<?php
namespace Basic\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Basic\Model\Listening;
use Basic\Filter\ListeningFilter;
use Basic\Form\ListeningForm;
use Zend\View\Model\JsonModel;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Application\Helper\ExcelReports;
use Locale as Zend_Locale;
use Zend\Validator\AbstractValidator;

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
	protected $translator;
	
	public function __construct(){
		$this->auth = new AuthenticationService();
		$this->session = new Container('role');
	}
	
	public function getCurrentTable()
	{
		if (!$this->currentTable) {
			$sm = $this->getServiceLocator();
			$this->currentTable = $sm->get('Basic\Model\ListeningTable');
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
	
	public function excelAction()
	{
		$project = $this->params()->fromQuery('project');
		
		$answers = $this->getCurrentTable()->fetchAllListeningAnswers(['id_project'=>$project]);

		$excelReport = new ExcelReports($this->getServiceLocator());
		$excelReport->exportListeningAnswers($answers);

	}


	/**
	 * The default action - show the home page
	 */
	public function indexAction()
	{
		$project = $this->params()->fromQuery('project');
		$channel = $this->params()->fromQuery('channel');
		$dateFrom = $this->params()->fromQuery('date_from');
		$dateTo = $this->params()->fromQuery('date_to');
		$week = $this->params()->fromQuery('week');
		$created = $this->params()->fromQuery('created');
		$active = $this->params()->fromQuery('active') != '' && is_numeric($this->params()->fromQuery('active')) ? $this->params()->fromQuery('active') : '1';
		$agent = $this->params()->fromQuery('agent');
		$qaAgent = $this->params()->fromQuery('qa_agent');

		$organization = $this->auth->getIdentity()->id_organization;
		$excel = $this->params()->fromQuery('excel');
		$m = $this->params()->fromQuery('m');
		
		$config = $this->getServiceLocator()->get('config');
		if ($this->auth->getIdentity()->id_role == $config['roles']['agent']) {
			$agent = $this->auth->getIdentity()->id;
		}            
		
		$filter = array();
		
		if ($channel)
			$filter['channel'] = $channel;
		
		if ($dateFrom)
			$filter['date_from'] = $dateFrom;
		
		if ($dateTo)
			$filter['date_to'] = $dateTo;
		
		if ($week)
			$filter['week'] = $week;
		
		if ($created)
			$filter['created'] = $created;
		
		if (is_numeric($active))
			$filter['active'] = $active;
		
		if ($agent)
			$filter['agent'] = $agent;
		
		if ($qaAgent)
			$filter['qa_agent'] = $qaAgent;
		
		if ($organization)
			$filter['organization'] = $organization;
		
		if ($project)
			$filter['project'] = $project;
		
		if ($excel)
		{
			$excelReport = new ExcelReports($this->getServiceLocator());
			$excelReport->exportListenings($this->getCurrentTable()->fetchAll(false, $filter));
		}
		
		// grab the paginator from the RoleTable
		$paginator = $this->getCurrentTable()->fetchAll(true, $filter);
		// set the current page to what has been passed in query string, or to 1 if none set
		$paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
		// set the number of items per page to 10
		$paginator->setItemCountPerPage(30);
		$paginator->setPageRange(5);
		
		$membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
		$membership = $membershipTable->getById($this->session->role->membership->id_membership);
		$hasAgents = $membership->hasAgents() && $this->auth->getIdentity()->id_role != $config['roles']['agent'];
	$hasTeamLead = $membership->hasAgents() && $this->auth->getIdentity()->id_role != $config['roles']['agent'];
	$hasCase = $membership->hasAgents() && $this->auth->getIdentity()->id_role != $config['roles']['agent'];	
	$hasIncident = $membership->hasAgents() && $this->auth->getIdentity()->id_role != $config['roles']['agent'];	
		
		if ($hasAgents) {
			$userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
			$agents = $userTable->fetchAllProjectAgents();
		}

		
		$channelTable = $this->getServiceLocator()->get('Application\Model\ChannelTable');
		$channels = $channelTable->fetchAll(false);
		
		$projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
		$projects = $projectTable->fetchAll(false, array('organization'=>$organization));
		
		$userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
		$auditors = $userTable->fetchAllProjectAuditors(null, $organization);
		
		$returnFilter = $filter;
		$returnFilter['page'] = $paginator->getCurrentPageNumber();
		unset($returnFilter['organization']);
		$rurl = $this->url()->fromRoute('basic/default', array('controller'=>'listening','action' => 'index'), array('query'=>$returnFilter));
		
		$vmData = array(
			'paginator' => $paginator,
			'filter' => $filter,
			'channels' => $channels,
			'subtitle'=>$this->getTranslator()->translate('Evaluations List'),
			'projects'=>$projects,
			'auditors'=>$auditors,
			'm'=>$m,
			'rurl'=>$rurl,
			'hasAgents'=>$hasAgents,
		'hasTeamLead'=>$hasTeamLead,
		'hasCase'=>$hasCase,
		'hasIncident'=>$hasIncident,

		);
		
		if ($hasAgents) {
			$vmData['agents'] = $agents;
		}
		if ($hasTeamLead) {
			$vmData['teamlead'] = $agents;
		}
		if ($hasCase) {
			$vmData['case'] = $agents;
		}
		if ($hasIncident) {
			$vmData['incident'] = $agents;
		}

		
		return new ViewModel($vmData);
	}
	
	public function viewAction()
	{
		$id = (int) $this->params()->fromRoute('id', 0);
		$rurl = $this->params()->fromQuery('rurl', '') ? base64_decode($this->params()->fromQuery('rurl', '')): null;

	//echo("<script>console.log('ID: ".$id."');</script>");
	//echo("<script>console.log('rurl: ".$rurl."');</script>");        

		if (!$id) {
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
		}
		
		try {
			$entity = $this->getCurrentTable()->getById($id);
			
			if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
			{
				return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
			}

			if($entity->id_agent)
			{
                $agentsTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                $entity->agent = $agentsTable->getUser($entity->id_agent);
			}
		}
		catch (\Exception $ex) {

		//echo $ex;
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
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
			$groupsScores[$item->id_group]+= $item->answers == 2 ? ($item->answer*$item->weight_percentage)/($item->answers-1) : ($item->answer*$item->weight_percentage)/($item->answers);
			
			//if ($item->is_fatal && $item->answer)
				//$groupsScores[$item->id_group]=100;

			//if ($item->ml_fatal && $item->answer)
				//$groupsScores[$item->id_group]=40;
		}        

		return new ViewModel(array(
			'id'    => $id,
			'entity' => $entity,
			'groupsWeights' => $groupsWeights,
			'groupsScores' => $groupsScores,
			'subtitle'=>$this->getTranslator()->translate('Evaluation Details'),
			'rurl'=>$rurl
		));
	}
	
	public function addAction()
	{
		$m = (int) $this->params()->fromQuery('m',0);

		$form = $this->getServiceLocator()->get('Basic\Form\ListeningForm');

		return new ViewModel(array('form'=>$form,'m'=>$m, 'subtitle'=>$this->getTranslator()->translate('Perform QA'),));
	}
	
	public function editAction()
	{
		$id = (int) $this->params()->fromRoute('id', 0);
		
		$rurl = $this->params()->fromQuery('rurl', '') ? base64_decode($this->params()->fromQuery('rurl', '')): null;
		
		if (!$id) {
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
		}
		try {
			
			$entity = $this->getCurrentTable()->getById($id);
			
			if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
				return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
			
			if ($entity->is_public)
				return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'), array('query'=>array('m'=>10)));
			
			if($entity->id_agent)
			{
                $agentsTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                $entity->agent = $agentsTable->getUser($entity->id_agent);
			}

		}
		catch (\Exception $ex) {
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
		}
	
		$entity->listenings_answers->buffer();
		$entity->listenings_group_scores->buffer();

//         $form = new ListeningForm();
		$form = $this->getServiceLocator()->get('Basic\Form\ListeningForm');
	
		$form->bind($entity);
	
		$groupsWeights = array();

		foreach ($entity->listenings_answers as $item)
		{
			$groupsWeights[$item->id_group]+=$item->weight;
		}
	
		$entity->listenings_answers->rewind();
	
		$groupsScores = array();

		foreach($entity->listenings_group_scores as $item ){
			$groupsScores[$item->id_question_group] =  $item->score;
		};

/*
		foreach ($entity->listenings_answers as $item)
		{
			if (!isset($groupsScores[$item->id_group]))
				$groupsScores[$item->id_group]=0;
	
			$maxAnswer = $item->answers - 1;
			$answer = $item->answer;
//             $groupsScores[$item->id_group]+=($item->answer*$item->weight_percentage)/($item->answers-1);            
			$groupsScores[$item->id_group]+= $item->answers == 2 ? ($item->answer*$item->weight_percentage)/($item->answers-1) : ($item->answer*$item->weight_percentage)/($item->answers);
	
			if ($item->is_fatal && $item->answer)
				$groupsScores[$item->id_group]=100;

			if ($item->ml_fatal && $item->answer)
				$groupsScores[$item->id_group]=40;
		}
*/
		return new ViewModel(array(
			'id' => $id,
			'form' => $form,
			'entity' => $entity,
			'groupsWeights' => $groupsWeights,
			'groupsScores' => $groupsScores,
			'subtitle'=>$this->getTranslator()->translate('Edit Evaluation'),
			'rurl'=>$rurl,
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
				$post->is_public_evaluation = 0;
   
				$agentFilter = false;
					$membershipTable = $this->getServiceLocator()->get('\Application\Model\MembershipTable');
				if ($membershipTable->hasAgents($this->session->role->membership->id_membership)) {
					$post->id_agent = $listening->id_agent;
					$agentFilter = true;
				}
				
//                 $post->id_subject = $listening->id_subject;
//                 $post->id_language = $listening->id_language;
			}
			catch (\Exception $e) {
				return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
			}
		}

		$form = $this->getServiceLocator()->get('Basic\Form\ListeningForm');
		
		if ($request->isPost()) {
		
			try {
		
				$listening = $listening?:new Listening();
				
				$filter = new ListeningFilter();
				
				$form->setInputFilter($filter);

				$form->setData($post);

				$freeQuestionsOk = true;

				//Validación de preguntas libres
				foreach ($post['listenings_answers'] as $listeningAnswer) {

					if (array_key_exists('free_answer', $listeningAnswer) ) {
						if(empty(trim($listeningAnswer['free_answer']))){
							$freeQuestionsOk = false;
						};
					};
				};

				if (($form->isValid() || !$form->getMessages()) && $freeQuestionsOk) {

					$listening->exchangeArray($form->getData());

					$this->getCurrentTable()->save($listening);
		
					return new JsonModel(array('success'=>true,'url'=>$this->url()->fromRoute('basic/default', array('controller'=>'listening','action' => 'view','id'=>$listening->id))));
				}
				else
				{
					$message = $form->getMessages();

					if(!$freeQuestionsOk) {
						$message['free_questions'] = true;
					}

					return new JsonModel(array('success'=>false,'message'=>$message,'is_valid'=>false));
				}
		
			} catch (\Exception $e) {
				return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
			}
		}
		 

	}
	
	public function changeStatusAction()
	{
		$id = (int) $this->params()->fromRoute('id', 0);
		$rurl = $this->params()->fromQuery('rurl', '') ? base64_decode($this->params()->fromQuery('rurl', '')): null;
		
		if (!$id) {
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
		}
		
		try {
			$entity = $this->getCurrentTable()->getById($id);
			
			if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
				return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));

			if($entity->id_agent)
			{
                $agentsTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                $entity->agent = $agentsTable->getUser($entity->id_agent);
			}
		}
		catch (\Exception $ex) {
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening', 'action' => 'index'));
		}
		
		$request = $this->getRequest();
		
		if ($request->isPost()) {
			$this->getCurrentTable()->delete($id);
			
			return $this->redirect()->toRoute('basic/default', array('controller'=>'listening','action'=>'view','id'=>$id));
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
			$groupsScores[$item->id_group]+= $item->answers == 2 ? ($item->answer*$item->weight_percentage)/($item->answers-1) : ($item->answer*$item->weight_percentage)/($item->answers);
			
			if ($item->is_fatal && $item->answer)
				$groupsScores[$item->id_group]=100;

			if ($item->ml_fatal && $item->answer)
				$groupsScores[$item->id_group]=100;
		}        

		return new ViewModel(array(
			'id'    => $id,
			'entity' => $entity,
			'groupsWeights' => $groupsWeights,
			'groupsScores' => $groupsScores,
			'subtitle'=>$this->getTranslator()->translate('Change Evaluation Status'),
			'rurl'=>$rurl
		));
	}


	public function addPublicAction()
	{

		$tok = $this->params()->fromRoute('token', 0);

		//Antes de cargar las preguntas, se debe verificar si es una evaluación con selección de formulario en vivo.
		//Para eso detecto el tipo de token que recibo, si tiene guiones, busco el projecto y paso al formulario que presenta la pregunta selectora, caso contrario sigo como hasta ahora
		if(preg_match('/([a-f\d]{8}-[a-f\d]{4}-[a-f\d]{4}-[a-f\d]{4}-[a-f\d]{12})(\d*)/', $tok, $partes))
		{
			$token = $partes[1];
			$id_agent = count($partes)>2?$partes[2]:false;

			$projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
			$project = $projectTable->getByToken($token);

			$tokens = [];
			foreach ($project->projects_channels as $channel) {
				$tokens[$channel->id_channel] = $project->getPublicToken($channel->id_channel, $channel->id_form, $id_agent);
			}

			$agent = false;
			if($id_agent)
			{
				$userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
				$agent = $userTable->getUser($id_agent);				
			}

			$viewModel = new ViewModel(
				array(
					'project' => $project,
					'tokens' => $tokens,
					'agent' => $agent
				)
			);

			$viewModel->setTemplate('basic/listening/public-selector');

			$this->layout('layout/layout-public-listening');
			return $viewModel; 

		} 

		preg_match('/([a-f\d]{32})(\d*)/', $tok, $partes);

		$token = $partes[1];
		$id_agent = count($partes)>2?$partes[2]:false;

		// fetch current form
		$formTable = $this->getServiceLocator()->get('Application\Model\FormTable');
		$f = $formTable->fetchProjectChannelFormByToken($token);
	
		// IF "FORM" IS EMPTY REDIRECT TO HOMEPAGE
		if (!$f)
			return $this->redirect()->toUrl('http://www.quickaudits.io');
		
		$projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
		$projectChannels = $projectTable->fetchProjectChannelsFormsByToken($token);
			
		// VALIDATE ENABLE PUBLIC EVALUATIONS THIS PROJECT CHANNEL FORM
		if (!$projectChannels->enable_public)
			return $this->redirect()->toUrl('http://www.quickaudits.io');
		
		$organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
		$organization = $organizationTable->getById($projectChannels->id_organization);
		
		if ($projectChannels->culture_name)
		{
			// set locale for current user
			$config = $this->getServiceLocator()->get('config');
			$translator = $this->getServiceLocator()->get('translator');
			//$translator->setLocale(str_replace('-', '_', $projectChannels->culture_name))->setFallbackLocale('en_US');
			$locale = explode('_', $translator->getLocale());
			Zend_Locale::setDefault($translator->getLocale());
			$translator->addTranslationFile(
				'phpArray',
				$config['paths']['translation_file_path'].$locale[0].'/Zend_Validate.php'
			);
			AbstractValidator::setDefaultTranslator($translator);
			// end locale
		}
		
	   
//         $form = new ListeningForm();
		$form = $this->getServiceLocator()->get('Basic\Form\ListeningForm');
		
		$request = $this->getRequest();

		if ($request->isPost()) {

			try {
				$post = $request->getPost();

				$post->id_project = $projectChannels->id_project;
				$post->id_channel = $projectChannels->id_channel;
				$post->id_form = $projectChannels->id_form;
				$post->is_public_evaluation = true;

				if($id_agent) {
					$post->id_agent = $id_agent;

				}

				$listening = new Listening();

				$listeningFilter = new ListeningFilter();

				// add filter only for this listening
				if ($projectChannels->public_by_agents)
				{

			       	$post->public_by_agents = true;

				}

				// add filter only for this listening
				if ($projectChannels->require_public_names && !$projectChannels->public_by_agents)
				{
					$listeningFilter->add(array(
						'name' => 'id_agent',
						'type' => 'Zend\Form\Element\Select',
						'attributes' => array(
							'class' => 'form-control select-chosen',
						),
						'options' => array(
							'label' => $sl->get('translator')->translate('Agent'),
							'empty_option' => $sl->get('translator')->translate('Choose an Agent'),
							'disable_inarray_validator' => true,
						),
					));
				}

				if ($projectChannels->require_public_names )
				{
						$listeningFilter->add(array(
						'name'     => 'qa_agent_fullname',
						'required' => true,
						'filters'  => array(
							array('name' => 'StripTags'),
							array('name' => 'StringTrim'),
						),
						'validators' => array(
							array(
								'name'    => 'StringLength',
								'options' => array(
									'encoding' => 'UTF-8',
									'min'      => 1,
									'max'      => 100,
								),
							),
						),
					));
				}

				$form->setInputFilter($listeningFilter);

				$form->setData($post);
/*
echo '<pre>';
print_r($form->isValid()?'Valid':'Invalid');
print_r($form->getErrors());	
echo '</pre>';die;	
*/
				if ($form->isValid() || !$form->getMessages()) {
		
					$listening->exchangeArray($form->getData());
	
					$listening->id_organization = $projectChannels->id_organization;
	
					$this->getCurrentTable()->savePublic($listening);
		
					return $this->redirect()->toRoute('public_listening_success', array(), array('query'=>array('p'=>base64_encode($projectChannels->id_project))));
				}

echo '<pre>';
echo 'Fallo validacion';
print_r($form->getMessages());
print_r($form->getErrors());
echo '</pre>';die;
		
								
			} catch (\Exception $e) {

				$error = $e->getMessage();
echo '<pre>';
echo 'Fallo try';
print_r($error);
echo '</pre>';

			}
		}
		
		$formQuestions = $formTable->fetchAllFormsQuestions(array('id_form'=>$f->id,'active'=>1));


		$groupsWeights = array();
		
		$questions = array();
		foreach ($formQuestions as $item)
		{
			array_push($questions, $item);
		}

		$agent = false;
		if($id_agent)
		{
			$userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
			$agent = $userTable->getUser($id_agent);				
		}
		
		$viewModel = new ViewModel(
			array(
				'form' => $form,
				'questions' => $questions,
				'organization' => $organization,
				'projectChannels' => $projectChannels,
				'id_agent' => $id_agent,
				'locale'=>$locale,
				'agent' => $agent
			)
		);

		$this->layout('layout/layout-public-listening');
		return $viewModel;            
	}
	
	public function successPublicAction()
	{
		$idProject = (int) base64_decode($this->params()->fromQuery('p', 0));
		
		if (!$idProject)
			return $this->redirect()->toUrl('http://www.quickaudits.io');
		
		$projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
		$project = $projectTable->getById($idProject);
		$localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');
		$locale = $localeTable->getById($project->id_locale);
		
		if ($project->id_locale)
		{
			// set locale for current user
			$config = $this->getServiceLocator()->get('config');
			$translator = $this->getServiceLocator()->get('translator');
			$translator->setLocale(str_replace('-', '_', $locale->culture_name))->setFallbackLocale('en_US');
			$locale = explode('_', $translator->getLocale());
			Zend_Locale::setDefault($translator->getLocale());
			$translator->addTranslationFile(
				'phpArray',
				$config['paths']['translation_file_path'].$locale[0].'/Zend_Validate.php'
			);
			AbstractValidator::setDefaultTranslator($translator);
			// end locale
		}
		
		$viewModel = new ViewModel(
//             array(
//                 'locale'=>$locale
//             )
		);
		
		$this->layout('layout/layout-public-listening');
		return $viewModel;
	}
}
