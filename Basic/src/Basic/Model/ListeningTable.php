<?php
namespace Basic\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Authentication\AuthenticationService;
use Application\Model\WizardTable;
use Application\Model\UserTable;

class ListeningTable
{
	/**
	 * 
	 * @var \Zend\Db\TableGateway\TableGateway
	 */
	protected $tableGateway;

	/**
	 * 
	 * @param \Zend\Db\TableGateway\TableGateway $tableGateway
	 */
	public function __construct(TableGateway $tableGateway)
	{
		$this->tableGateway = $tableGateway;
	}
	
	/**
	 *
	 * @return \Zend\Db\Sql\Select
	 */
	public function getCurrentUserProjectsSelect() {
	
		$auth = new AuthenticationService();
	
		$sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
	
		return $sql->select()
		->from(array('p'=>'projects'))
		->join(array('up'=>'users_projects'), 'p.id=up.id_project', array())
		->where(array('up.id_user'=>$auth->getIdentity()->id,'up.active'=>1))
		->group('p.id');
	}

	public function fetchAll($paginated=false, $filter = array())
	{
		$where = array();
		
		if (isset($filter['project'])) {
			$where[] = "(l.id_project IN (" . (is_array($filter['project']) ? implode(',', $filter['project']) : $filter['project']) . "))";
		}
		
		if (isset($filter['channel'])) {
			$where[] = "(l.id_channel IN (" . (is_array($filter['channel']) ? implode(',', $filter['channel']) : $filter['channel']) . "))";
		}            
		
		if (isset($filter['date_from']))
			$where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
		
		if (isset($filter['date_to']))
			$where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
		
		if (isset($filter['week']))
			$where[] = '(WEEKOFYEAR(l.created) = \''.$filter['week'].'\')';
		
		if (isset($filter['created']))
			$where[] = '(DATE(l.created) = \''.$filter['created'].'\')';
		
		if (isset($filter['subject']))
			$where[] = '(l.id_subject = \''.$filter['subject'].'\')';
		
		if (isset($filter['qa_agent'])) {
			$where[] = "(l.id_qa_agent IN (" . (is_array($filter['qa_agent']) ? implode(',', $filter['qa_agent']) : $filter['qa_agent']) . "))";
		}
		
		if (isset($filter['agent'])) {
			$where[] = "(l.id_agent IN (" . (is_array($filter['agent']) ? implode(',', $filter['agent']) : $filter['agent']) . "))";
		}
			
		
		if (isset($filter['active']))
		{
			if (!$filter['active'])
				$where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
			else
				$where[] = 'l.active = \''.$filter['active'].'\'';
		}
		
		if (isset($filter['organization']))
			$where[] = '(l.id_organization = \''.$filter['organization'].'\')';

		$sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
		
		$select = $sql->select()
			->from(array('l' => 'listenings'))
			->join(array('p' => $this->getCurrentUserProjectsSelect()), 'l.id_project=p.id', array('project'=>'name','min_performance_required'))
			->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
			->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'), 'left')
			->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'), 'left')
//             ->join(array('s' => 'subjects'), 'l.id_subject=s.id', array('subject'=>'name'), 'left')
//             ->join(array('la' => 'languages'), 'l.id_language=la.id', array('language'=>'name'))
			->join(array('o'=>'organizations'), 'l.id_organization=o.id', array('organization'=>'name'))
			->where(!empty($where) ? implode(' AND ', $where) : 1)
			->order('l.id DESC');
		
		if ($paginated) {
			// create a new result set based on the entity
			$resultSetPrototype = new ResultSet();
			$resultSetPrototype->setArrayObjectPrototype(new Listening());
			// create a new pagination adapter object
			$paginatorAdapter = new DbSelect(
					// our configured select object
					$select,
					// the adapter to run it against
					$this->tableGateway->getAdapter(),
					// the result set to hydrate
					$resultSetPrototype
			);
			$paginator = new Paginator($paginatorAdapter);
			return $paginator;
		}
		
		$selectString = $sql->getSqlStringForSqlObject($select);
		$results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
		
		return $results;
	}
	
	/**
	 * 
	 * @param int $id
	 * @param string $includeQuestions
	 * @throws \Exception
	 * @return \Application\Model\Listening
	 */
	public function getById($id, $includeQuestions = true)
	{
		$id  = (int) $id;
	
		$sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
	
		$select = $sql->select()
			->from(array('l' => 'listenings'))
			->join(array('p' => 'projects'), 'l.id_project=p.id', array('project'=>'name'))
			->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
			->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'),'left')
//             ->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'))
			->join(array('s' => 'subjects'), 'l.id_subject=s.id', array('subject'=>'name'), 'left')
//             ->join(array('la' => 'languages'), 'l.id_language=la.id', array('language'=>'name'))
			->join(array('o'=>'organizations'), 'l.id_organization=o.id', array('organization'=>'name'))
			->where('l.id='.$id);

		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		
		$resultSetPrototype = new HydratingResultSet();
		$resultSetPrototype->setHydrator(new ObjectProperty());
		$resultSetPrototype->setObjectPrototype(new Listening());
		$results = $resultSetPrototype->initialize($results);
		
		$row = $results->current();
	
		if (!$row) {
			throw new \Exception("Could not find row $id");
		}

		$row->setListenings_answers($this->fetchAllListeningAnswers(array('id_listening'=>$id)));

		$listenings_group_scores = $this->fetchAllListeningsGroupScores(['id_listening'=>$id]);

		$row->setListenings_group_scores($listenings_group_scores);

		return $row;
	}
	
	public function delete($id)
	{
		$entity = $this->getById($id, false);
		$data = array(
			'active' => $entity->active?'0':'1',
		);
		$this->tableGateway->update($data, array('id' => $id));
	}
	
	public function save(Listening $entity)
	{
		$dbAdapter = $this->tableGateway->getAdapter();
	
		$connection = $dbAdapter->getDriver()->getConnection();

		try {
	
			$connection->beginTransaction();
			
			$auth = new AuthenticationService();
	
			$data = array(
				'date'                      => $entity->date,
				'recording_name'            => $entity->recording_name,
				'time_recording_minutes'    => $entity->time_recording_minutes,
				'time_recording_seconds'    => $entity->time_recording_seconds,
				'comments'                  => $entity->comments,
				'teamlead'                  => $entity->teamlead,
				'case'			=> $entity->case,
				'incident'		=> $entity->incident,
				//'notes'			=> $entity->notes,
				'score'                     => $entity->score,
				'id_organization'           => $auth->getIdentity()->id_organization,
			);
	
			$id = (int) $entity->id;
	
			if ($id == 0) {
				
				$entity->setId_qa_agent($auth->getIdentity()->id);
				
				$userTable = new UserTable($this->tableGateway);
				$qaAgent = $userTable->getUser($entity->id_qa_agent);
				
				$data['id_project'] = $entity->id_project;
				$data['id_channel'] = $entity->id_channel;
				$data['id_qa_agent'] = $entity->id_qa_agent;
				$data['id_form'] = $entity->id_form;
				$data['qa_agent_fullname'] = $qaAgent->name;
				$data['qa_agent_email'] = $qaAgent->email;
				
				if ($entity->id_agent)
					$data['id_agent'] = $entity->id_agent;
				
				$data['created'] = date("Y-m-d H:i:s");
				$data['created_by'] = $auth->getIdentity()->id;
				$data['modified'] = date("Y-m-d H:i:s");
				$data['modified_by'] = $auth->getIdentity()->id;
	
				$this->tableGateway->insert($data);
				$entity->id = $this->tableGateway->lastInsertValue;
	
			} else {
				if ($this->getById($id)) {
					$data['modified'] = date("Y-m-d H:i:s");
					$data['modified_by'] = $auth->getIdentity()->id;
	
					$this->tableGateway->update($data, array('id' => $id));
				} else {
					throw new \Exception('Entity id does not exist');
				}
			}
	
			// save listenings answers & block questions
			$listeningsAnswersTable = new TableGateway('listenings_answers', $dbAdapter);
			$questionTable = new TableGateway('questions', $dbAdapter);
			
			$listeningsAnswersTable->delete(array('id_listening'=>$entity->id));
			foreach($entity->listenings_answers as $listeningAnswer){

//                 $listeningsAnswersTable->insert(array('id_listening'=>$entity->id,'id_question'=>$listeningAnswer['id_question'],'answer'=>$listeningAnswer['answer']));
				$listeningsAnswersTable->insert(array(
					'id_listening'=>$entity->id,
					'id_question'=>$listeningAnswer->id_question,
					'answer'=>$listeningAnswer->answer,
					'free_answer'=>$listeningAnswer->free_answer
				));
				
				// block question & questions_groups
				$statement = $dbAdapter->createStatement('UPDATE questions INNER JOIN questions_groups ON questions.id_group = questions_groups.id SET questions.blocked = \'1\', questions_groups.blocked = \'1\' WHERE questions.id = ?');
//                 $statement->execute(array($listeningAnswer['id_question']));
				$statement->execute(array($listeningAnswer->id_question));
			}

			// save listening group scores
			$listeningsGroupScoresTable = new TableGateway('listenings_group_scores', $dbAdapter);
			$listeningsGroupScoresTable->delete(array('id_listening'=>$entity->id));
			foreach($entity->listenings_group_scores as $listeningGroupScore)
			{
				$listeningsGroupScoresTable->insert(array(
					'id_listening'=>$entity->id,
					'id_question_group'=>$listeningGroupScore->id_question_group,
					'score'=>$listeningGroupScore->score,
					'weight'=>$listeningGroupScore->weight
				));
			}
			
			// block forms & users_projects (agent and qaagent)
			$formTable = new TableGateway('forms', $dbAdapter);
			$formTable->update(array('blocked'=>1),array('id'=>$entity->id_form));
			
			$userProjectTable = new TableGateway('users_projects', $dbAdapter);
			// update agent
			$userProjectTable->update(array('blocked'=>1),array('id_user'=>$entity->id_agent,'id_project'=>$entity->id_project));
			// update qa agent
			$userProjectTable->update(array('blocked'=>1),array('id_user'=>$entity->id_qa_agent,'id_project'=>$entity->id_project));
			
//             $subjectTable = new TableGateway('subjects', $dbAdapter);
//             $subjectTable->update(array('blocked'=>1),array('id'=>$entity->id_subject));

			// complete wizard step
			$wizardTable = new WizardTable($this->tableGateway);
			$wizardTable->completeWizardStep('perform-qa');
	
			$connection->commit();
	
		} catch (\Exception $e) {

print_r($e);


			$connection->rollback();
	
			throw $e;
		}
	}
	
	public function fetchAllListeningAnswers($filter=array())
	{
		$where = array();
		
		if (isset($filter['id_listening']))
		{
			$where[] = 'la.id_listening=\''.$filter['id_listening'].'\'';
		}
	
		$sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
	
		$select = $sql->select()
			->from(array('la' => 'listenings_answers'))
			->join(array('l' => 'listenings'), 'la.id_listening=l.id', array())
			->join(array('f' => 'forms'), 'l.id_form=f.id', array())
			->join(array('fq' => 'forms_questions'), 'f.id=fq.id_form AND la.id_question=fq.id_question',
				// KHB - Agregado allow_na en tarea NA
				array('answers','weight','weight_percentage','order', 'allow_na'))
			->join(array('q'=>'questions'), 'la.id_question=q.id', array('question'=>'name','id_group', 'question_type' => 'type', 'question_options' => 'options'))
			->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('question_group'=>'name','is_fatal'))
			//->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('question_group'=>'name','ml_fatal'))
			->where(!empty($where) ? implode(' AND ', $where) : 1)
			->order(array('fq.order'=>'asc'));
	
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
	
		$resultSetPrototype = new HydratingResultSet();
		$resultSetPrototype->setHydrator(new ObjectProperty());
		$resultSetPrototype->setObjectPrototype(new ListeningAnswer());
		$results = $resultSetPrototype->initialize($results);
	
		return $results;
	}

	public function fetchAllListeningsGroupScores($filter=array())
	{
		$where = array();
		
		if (isset($filter['id_listening']))
		{
			$where[] = 'lgs.id_listening=\''.$filter['id_listening'].'\'';
		}
	
		$sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
	
		$select = $sql->select()
			->from(array('lgs' => 'listenings_group_scores'))
			->where(!empty($where) ? implode(' AND ', $where) : 1);
	
		$statement = $sql->prepareStatementForSqlObject($select);
		$results = $statement->execute();
		$resultSetPrototype = new HydratingResultSet();
		$resultSetPrototype->setHydrator(new ObjectProperty());
		$resultSetPrototype->setObjectPrototype(new ListeningGroupScore());
		$results = $resultSetPrototype->initialize($results);

		return $results;
	}
		
	public function savePublic(Listening $entity)
	{
		$dbAdapter = $this->tableGateway->getAdapter();
	
		$connection = $dbAdapter->getDriver()->getConnection();
	
		try {
	
			$connection->beginTransaction();
	
			$auth = new AuthenticationService();

			//KHB NA - Se elimina el calculo del score, se graba el que viene del formulario
			$data = array(
				'comments'          => $entity->comments,
				//'notes'          	=> $entity->pnorte_room,
				'teamlead'          => $entity->teamlead,
				'case'              => $entity->case,
				'score'             => $entity->score, //$entity->calculateScore(),
				'pnorte_room' 	=> $entity->pnorte_room,
				'pnorte_arrival'	=> $entity->pnorte_arrival,
				'pnorte_departure' 	=> $entity->pnorte_departure,
				'pnorte_company' 	=> $entity->pnorte_company,
				'pnorte_city' 	=> $entity->pnorte_city,
				'pnorte_recommend' 	=> $entity->pnorte_recommend,
				'pnorte_country' 	=> $entity->pnorte_country,
				'pnorte_departure' 	=> $entity->pnorte_departure,
				'pnorte_email' 	=> $entity->pnorte_email,
				'id_organization'   => $entity->id_organization,
				'id_project'        => $entity->id_project,
				'id_channel'        => $entity->id_channel,
				'id_form'           => $entity->id_form,
				'created'           => date("Y-m-d H:i:s"),
				'is_public'         => '1',
				'qa_agent_fullname' => $entity->qa_agent_fullname,

				'id_agent'			=> $entity->id_agent,
			);

			$this->tableGateway->insert($data);
			$entity->id = $this->tableGateway->lastInsertValue;
	
			// save listenings answers & block questions
			$listeningsAnswersTable = new TableGateway('listenings_answers', $dbAdapter);
			$questionTable = new TableGateway('questions', $dbAdapter);
	
			$listeningsAnswersTable->delete(array('id_listening'=>$entity->id));
	
			foreach($entity->listenings_answers as $listeningAnswer){
	
				$listeningsAnswersTable->insert(array(
					'id_listening'=>$entity->id,
					'id_question'=>$listeningAnswer->id_question,
					'answer'=>$listeningAnswer->answer,
					'free_answer'=>$listeningAnswer->free_answer
				));
	
				// block question & questions_groups
				$statement = $dbAdapter->createStatement('UPDATE questions INNER JOIN questions_groups ON questions.id_group = questions_groups.id SET questions.blocked = \'1\', questions_groups.blocked = \'1\' WHERE questions.id = ?');

				$statement->execute(array($listeningAnswer->id_question));
			}

			// save listening group scores
			$listeningsGroupScoresTable = new TableGateway('listenings_group_scores', $dbAdapter);
			$listeningsGroupScoresTable->delete(array('id_listening'=>$entity->id));
			foreach($entity->listenings_group_scores as $listeningGroupScore)
			{
				$listeningsGroupScoresTable->insert(array(
					'id_listening'=>$entity->id,
					'id_question_group'=>$listeningGroupScore->id_question_group,
					'score'=>$listeningGroupScore->score,
					'weight'=>$listeningGroupScore->weight
				));
			}		
		
			// block forms & users_projects (agent and qaagent)
			$formTable = new TableGateway('forms', $dbAdapter);
			$formTable->update(array('blocked'=>1),array('id'=>$entity->id_form));
	
			$connection->commit();
	
		} catch (\Exception $e) {

			print_r($e->getMessage());

			$connection->rollback();
	
			throw $e;
		}

	}
}