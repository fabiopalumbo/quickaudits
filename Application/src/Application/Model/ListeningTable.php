<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Authentication\AuthenticationService;

class ListeningTable
{
    /**
     * 
     * @var Zend\Db\TableGateway\TableGateway
     */
    protected $tableGateway;

    /**
     * 
     * @param Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($paginated=false, $filter = array())
    {
        $where = array();
        
        if (isset($filter['project']))
            $where[] = '(l.id_project = \''.$filter['project'].'\')';
        
        if (isset($filter['channel']))
            $where[] = '(l.id_channel = \''.$filter['channel'].'\')';
        
        if (isset($filter['language']))
            $where[] = '(l.id_language = \''.$filter['language'].'\')';
        
        if (isset($filter['date_from']))
            $where[] = '(DATE(l.created) >= \''.date('Y-m-d',strtotime($filter['date_from'])).'\')';
        
        if (isset($filter['date_to']))
            $where[] = '(DATE(l.created) <= \''.date('Y-m-d',strtotime($filter['date_to'])).'\')';
        
        if (isset($filter['agent']))
            $where[] = '(l.id_agent = \''.$filter['agent'].'\')';
        
        if (isset($filter['qa_agent']))
            $where[] = '(l.id_qa_agent = \''.$filter['qa_agent'].'\')';
        
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
            ->join(array('p' => 'projects'), 'l.id_project=p.id', array('project'=>'name'))
            ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
            ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'))
            ->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'))
            ->join(array('la' => 'languages'), 'l.id_language=la.id', array('language'=>'name'), 'left')
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
    
    public function getById($id, $includeQuestions = true)
    {
        $id  = (int) $id;
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
            ->from(array('l' => 'listenings'))
            ->join(array('p' => 'projects'), 'l.id_project=p.id', array('project'=>'name'))
            ->join(array('c' => 'channels'), 'l.id_channel=c.id', array('channel'=>'name'))
            ->join(array('u1' => 'users'), 'l.id_qa_agent=u1.id', array('qa_agent'=>'name'))
            ->join(array('u2' => 'users'), 'l.id_agent=u2.id', array('agent'=>'name'))
            ->join(array('la' => 'languages'), 'l.id_language=la.id', array('language'=>'name'))
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
        
        return $row;
    }
    
    public function delete($id)
    {
        $entity = $this->getById($id, false);
        $data = array(
            'active' => !$entity->active,
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
                'date' => $entity->date,
                'recording_name' => $entity->recording_name,
                'time_recording_minutes' => $entity->time_recording_minutes,
                'time_recording_seconds' => $entity->time_recording_seconds,
                'comments' => $entity->comments,
                'score' => $entity->score,
                'id_organization' => $auth->getIdentity()->id_organization,
            );
    
            $id = (int) $entity->id;
    
            if ($id == 0) {
                
                $entity->setId_qa_agent($auth->getIdentity()->id);
                
                $data['id_project'] = $entity->id_project;
                $data['id_channel'] = $entity->id_channel;
                $data['id_agent'] = $entity->id_agent;
                $data['id_qa_agent'] = $entity->id_qa_agent;
                $data['id_language'] = $entity->id_language;
                $data['id_form'] = $entity->id_form;
    
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
    
            // block forms & users_projects (agent and qaagent)
            $formTable = new TableGateway('forms', $dbAdapter);
            $formTable->update(array('blocked'=>1),array('id'=>$entity->id_form));
            
            $userProjectTable = new TableGateway('users_projects', $dbAdapter);
            // update agent
            $userProjectTable->update(array('blocked'=>1),array('id_user'=>$entity->id_agent,'id_project'=>$entity->id_project));
            // update qa agent
            $userProjectTable->update(array('blocked'=>1),array('id_user'=>$entity->id_qa_agent,'id_project'=>$entity->id_project));
    
            $connection->commit();
    
        } catch (\Exception $e) {
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

        if (isset($filter['id_project']))
        {
            $where[] = 'l.id_project=\''.$filter['id_project'].'\'';
        }


        if (isset($filter['id_form']))
        {
            $where[] = 'l.id_form=\''.$filter['id_form'].'\'';
        }

        $listeningFields = [];
        $formsFields = [];
        $agentsFields = [];
        if (array_key_exists('include', $filter))
        {
            if(isset($filter['include']['listenings']))
            {
                $listeningFields = $filter['include']['listenings'];
            }
            if(isset($filter['include']['forms']))
            {
                $formsFields = $filter['include']['forms'];
            }
            if(isset($filter['include']['agents']))
            {
                $agentsFields = $filter['include']['agents'];
            }
        }


        $order = array('fq.order'=>'asc');
        if (array_key_exists('order', $filter)) {
            $order = $filter['order'];
        }
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
            ->from(array('la' => 'listenings_answers'))
            ->join(array('l' => 'listenings'), 'la.id_listening=l.id', $listeningFields)
            ->join(array('f' => 'forms'), 'l.id_form=f.id', $formsFields)
            ->join(array('fq' => 'forms_questions'), 'f.id=fq.id_form AND la.id_question=fq.id_question', array('answers','weight','weight_percentage','order'))
            ->join(array('q'=>'questions'), 'la.id_question=q.id', array('question'=>'name','id_group', 'question_type'=>'type'))
            ->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('question_group'=>'name','is_fatal', 'ml_fatal'))
            ->join(['a'=>'users'], 'l.id_agent=a.id', $agentsFields, 'left')
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order($order);
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
    
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new ListeningAnswer());
        $results = $resultSetPrototype->initialize($results);
    
        return $results;
    }
}