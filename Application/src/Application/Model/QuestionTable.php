<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\ResultSet;

class QuestionTable
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

    public function fetchAll($paginated=false, $filter = array(), $order = null)
    {
        $where = array();
        
        if (isset($filter['keyword']))
            $where[] = '(q.name LIKE \'%'.$filter['keyword'].'%\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(q.active = \''.$filter['active'].'\' OR q.active = \'\')';
            else
                $where[] = 'q.active = \''.$filter['active'].'\'';
        }
        
        if (isset($filter['question_group']))
            $where[] = '(q.id_group = \''.$filter['question_group'].'\')';
        
        if (isset($filter['organization']))
            $where[] = '(q.id_organization = \''.$filter['organization'].'\')';
        
        if (isset($filter['type']))
            $where[] = '(q.type = \''.$filter['type'].'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
                        ->from(array('q' => 'questions'))
                        ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('question_group'=>'name','is_fatal'))
                        ->join(array('o'=>'organizations'), 'q.id_organization=o.id', array('organization'=>'name'))
                        ->where(!empty($where) ? implode(' AND ', $where) : 1)
                        ->order(!is_null($order) ? $order : 'q.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Question());
            
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

    public function getById($id)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
                ->from(array('q' => 'questions'))
                ->join(array('qg' => 'questions_groups'), 'q.id_group=qg.id', array('question_group'=>'name'))
                ->join(array('o'=>'organizations'), 'q.id_organization=o.id', array('organization'=>'name'))
                ->where(array('q.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $question = new Question();
        $question->exchangeArray($row);
        
        return $question;
    }
    
    public function save(Question $entity)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            $data = array(
                'name' => $entity->name,
                'type' => $entity->type,
                'options' => $entity->options,
                'id_group' => $entity->id_group,
                
                'id_organization' => $auth->getIdentity()->id_organization,
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
            
            // complete wizard step
            $wizardTable = new WizardTable($this->tableGateway);
            $wizardTable->completeWizardStep('manage_question');
            
            $connection->commit();
            
        } catch (\Exception $e) {
            $connection->rollback();
            
            throw $e;
        }
    }

    public function changeStatus($id)
    {
        $entity = $this->getById($id);
        $data = array(
            'active' => !$entity->active,
        );
        $this->tableGateway->update($data, array('id' => $id));
    }
    
    public function delete($id)
    {
        $this->tableGateway->delete(array('id' => $id));
    }
    
}