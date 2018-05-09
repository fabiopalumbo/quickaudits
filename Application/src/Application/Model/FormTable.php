<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Expression;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Authentication\AuthenticationService;

class FormTable
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
        
        if (isset($filter['keyword']))
            $where[] = '(f.name LIKE \'%'.$filter['keyword'].'%\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(f.active = \''.$filter['active'].'\' OR f.active = \'\')';
            else
                $where[] = 'f.active = \''.$filter['active'].'\'';
        }
        
        if (isset($filter['organization']))
            $where[] = '(f.id_organization = \''.$filter['organization'].'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('f' => 'forms'))
            ->join(array('o'=>'organizations'), 'f.id_organization=o.id', array('organization'=>'name'))
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order('f.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Form());
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
            ->from(array('f' => 'forms'))
            ->join(array('o'=>'organizations'), 'f.id_organization=o.id', array('organization'=>'name'))
            ->where(array('f.id'=>$id));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
    
        if ($includeQuestions)
            $row['forms_questions'] = $this->fetchAllFormsQuestions(array('id_form'=>$id,'active'=>1));
        
        $form = new Form();
        $form->exchangeArray($row);
    
        return $form;
    }
    
    public function fetchAllFormsQuestions($filter = array(),$join = array())
    {
        $where = array();
                
        if (isset($filter['id_form']))
        {
            $where[] = '(fq.id_form=\''.$filter['id_form'].'\')';
        }
        
        if (isset($filter['active']))
        {
            $where[] = '(q.active = 1)';
        }
        
        if (isset($filter['organization']))
            $where[] = '(q.id_organization = \''.$filter['organization'].'\')';
        
        $joinFormsQuestions = array();
        $joinFormsQuestions[] = '(q.id=fq.id_question)';
        
        if (isset($join['id_form']))
        {
            if (!is_array($join['id_form']))
            {
                $joinFormsQuestions[] = is_numeric($join['id_form']) ? '(fq.id_form=\''.$join['id_form'].'\')' : '(fq.id_form '.$join['id_form'].')';
            }
            else
            {
                $arrTemp = array();
                foreach ($join['id_form'] as $item)
                {
                    $arrTemp[] = is_numeric($item) ? '(fq.id_form=\''.$item.'\')' : '(fq.id_form '.$item.')';
                }
            
                $joinFormsQuestions[] = '(' . implode(' OR ', $arrTemp) . ')';
            }
        }
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('q' => 'questions'))
            ->columns(array('id_question'=>'id','question'=>'name','id_group','question_checked'=>new Expression('IF (f.id IS NULL, 0, 1)'), 'question_type' => 'type', 'question_options' => 'options'))
            ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('question_group'=>'name','is_fatal', 'is_fatal2' ))

            ->join(array('fq' => 'forms_questions'), new Expression(implode(' AND ', $joinFormsQuestions)),
                // KHB - Agregado en tarea NA. se agrega campo allow_na
                array('id_form','answers','weight','weight_percentage', 'allow_na'), 'left')

            ->join(array('f' => 'forms'), 'fq.id_form=f.id', array('form'=>'name'), 'left')
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order(array('qg.order ASC','qg.id ASC', new Expression('IF (`fq`.`order` IS NULL, 999, `fq`.`order`) ASC'),'q.name ASC'));
        
        $statement = $sql->prepareStatementForSqlObject($select);

        $results = $statement->execute();
        
        $resultSetPrototype = new ResultSet();
        $results = $resultSetPrototype->initialize($results);

        return $results;
    }
    
    public function fetchAllFormsQuestionsGroups($filter = array(),$join = array(), $order = null)
    {
        $where = array();
    
        if (isset($filter['keyword']))
            $where[] = '(qg.name LIKE \'%'.$filter['keyword'].'%\')';
    
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(qg.active = \''.$filter['active'].'\' OR qg.active = \'\')';
            else
                $where[] = 'qg.active = \''.$filter['active'].'\'';
        }
        
        if (isset($filter['organization']))
            $where[] = '(qg.id_organization = \''.$filter['organization'].'\')';
    
        $joinFormsQuestions = array();
        $joinFormsQuestions[] = '(q.id=fq.id_question)';
    
        if (isset($join['id_form']))
        {
            if (!is_array($join['id_form']))
            {
                $joinFormsQuestions[] = is_numeric($join['id_form']) ? '(fq.id_form=\''.$join['id_form'].'\')' : '(fq.id_form '.$join['id_form'].')';
            }
        }
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('qg' => 'questions_groups'))
        ->join(array('q' => 'questions'), 'qg.id=q.id_group', array())
        ->join(array('fq' => 'forms_questions'), new Expression(implode(' AND ', $joinFormsQuestions)), array('group_weight'=>new Expression('IF (SUM(weight) IS NULL, 0, ROUND(SUM(weight), 2))'),'group'=>new Expression('IF(MAX(fq.id_form) IS NULL,0,1)')), 'left')
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->group('qg.id')
        ->order(!is_null($order) ? $order : 'qg.name ASC');
    
        $statement = $sql->prepareStatementForSqlObject($select);
    
        $results = $statement->execute();
    
        $resultSetPrototype = new ResultSet();
        $results = $resultSetPrototype->initialize($results);
        
        return $results;
    }
    
    public function save(Form $entity)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        $connection = $dbAdapter->getDriver()->getConnection();
    
        try {
    
            $connection->beginTransaction();
    
            $auth = new AuthenticationService();
            
            $data = array(
                'name' => $entity->name,
                'id_organization'=>$auth->getIdentity()->id_organization
            );
    
            $id = (int) $entity->id;
    
            if ($id == 0) {
    
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
            
            $formsQuestionsTable = new TableGateway('forms_questions', $this->tableGateway->getAdapter());
            
            $formsQuestionsTable->delete(array('id_form'=>$entity->id));
            
            $order = 0;

            foreach($entity->forms_questions as $formQuestion){
                
                $formQuestion = $formQuestion->getArrayCopy();
                if ($formQuestion['question_checked'])
                {
                    $insertData =  array(
                            'id_form'=>$entity->id,
                            'id_question'=>$formQuestion['id_question'],
                            'answers'=>$formQuestion['answers'],
                            'weight'=>$formQuestion['weight'],
                            'weight_percentage'=>$formQuestion['weight_percentage'],
                            'order'=>$order++,
                            // KHB - Agregado en tarea NA.
                            'allow_na'=>$formQuestion['allow_na'],
                            'type'=>$formQuestion['question_type'],
                            //'options'=>$formQuestion['question_options']
                        );

                    $formsQuestionsTable->insert($insertData);
                }
                
            }
            
            // complete wizard step
            $wizardTable = new WizardTable($this->tableGateway);
            $wizardTable->completeWizardStep('manage_form');
    
            $connection->commit();
    
        } catch (\Exception $e) {

print_r($e->getMessage());

            $connection->rollback();
    
            throw $e;
        }
    }
    
    public function delete($id)
    {
        $entity = $this->getById($id, false);
        
        $data = array(
            'active' => $entity->active?'0':'1',
        );
        
        $this->tableGateway->update($data, array('id' => $id));
    }
    
    public function fetchProjectChannelForm($idProject, $idChannel)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
            ->from(array('f' => 'forms'))
            ->join(array('pc' => 'projects_channels'), 'f.id=pc.id_form', array())
            ->where('pc.id_project=\''.$idProject.'\' AND pc.id_channel=\''.$idChannel.'\'');
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new Form());
        $results = $resultSetPrototype->initialize($results);
        
        $current = $results->current();
        
        return $current;
    }
    
    public function fetchProjectChannelFormByToken($token)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('f' => 'forms'))
        ->join(array('pc' => 'projects_channels'), 'f.id=pc.id_form', array())
        ->where('pc.public_token=\''.$token.'\'');
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
    
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new Form());
        $results = $resultSetPrototype->initialize($results);
    
        $current = $results->current();
    
        return $current;
    }
    
    public function copy($id)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
        
            $connection->beginTransaction();
        
            $auth = new AuthenticationService();
            
            $form = $this->getById($id);
        
            $data = array(
                'name' => $form->name.' - DUP',
                'id_organization'=>$form->id_organization,
                'created'=>date("Y-m-d H:i:s"),
                'created_by'=>$auth->getIdentity()->id,
            );

            $this->tableGateway->insert($data);
            $newFormId = $this->tableGateway->lastInsertValue;
            
            $sql = new \Zend\Db\Sql\Sql($dbAdapter);
            $select = $sql->select()->from('forms_questions')->columns(array('id_form'=>new Expression($newFormId), 'id_question', 'answers', 'weight', 'weight_percentage', 'allow_na', 'type', 'order'))->where('id_form='.$id);
            
            $insert = $sql->insert('forms_questions')->select($select);

//             $selectString = $sql->getSqlStringForSqlObject($select);
//             $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
            
            $formsQuestionsTable = new TableGateway('forms_questions', $dbAdapter);
            $formsQuestionsTable->insertWith($insert);            
            
            $connection->commit();
            
            return $newFormId;
        
        } catch (\Exception $e) {

            $connection->rollback();
        
            throw $e;
        }       
    }
}