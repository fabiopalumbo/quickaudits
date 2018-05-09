<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\ResultSet;

class QuestionGroupTable
{
    /**
     * 
     * @var \Zend\Db\TableGateway\TableGateway
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

    public function fetchAll($paginated=false, $filter = array(), $order = null)
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
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
                        ->from(array('qg' => 'questions_groups'))
                        ->join(array('o'=>'organizations'), 'qg.id_organization=o.id', array('organization'=>'name'))
                        ->where(!empty($where) ? implode(' AND ', $where) : 1)
                        ->order(!is_null($order) ? $order : 'qg.name ASC');
        
        if ($paginated) {
            // create a new result set based on the entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new QuestionGroup());
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

    public function getById($id)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
                ->from(array('qg' => 'questions_groups'))
                ->join(array('o'=>'organizations'), 'qg.id_organization=o.id', array('organization'=>'name'))
                ->where(array('qg.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $QuestionGroup = new QuestionGroup();
        $QuestionGroup->exchangeArray($row);
        
        return $QuestionGroup;
    }
    
    public function save(QuestionGroup $entity, $existingConnection=NULL)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        if (is_null($existingConnection))
            $connection = $dbAdapter->getDriver()->getConnection();
        
        try {

            if (is_null($existingConnection))
                $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            $data = array(
                'name' => $entity->name,
                'is_fatal' => !is_null($entity->is_fatal) ? $entity->is_fatal : '0',
                'ml_fatal' => !is_null($entity->ml_fatal) ? $entity->ml_fatal : '0',
                'id_organization' => $auth->getIdentity()->id_organization,
                'order'=>intval($entity->order)
            );
            
            if ($entity->order)
                $data['order'] = $entity->order;
            
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
            $wizardTable->completeWizardStep('manage_question-group');
            
            if (is_null($existingConnection))
                $connection->commit();
            
        } catch (\Exception $e) {
            if (is_null($existingConnection))
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
    
    /**
     * 
     * @param array $filter
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAllProjectsGroups($filter = array())
    {
        $where = array();
    
        if (isset($filter['organization']))
            $where[] = '(p.id_organization = \''.$filter['organization'].'\')';
        
        if (isset($filter['project']) && !empty($filter['project']))
        {
            if (is_array($filter['project']))
            {
                $where[] = '(p.id IN ('.implode(',', $filter['project']).'))';
            }
            else
            {
                $where[] = '(p.id = \''.$filter['project'].'\')';
            }
        }
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('p' => 'projects'))
        ->columns(array())
        ->join(array('pc'=>'projects_channels'), 'p.id=pc.id_project', array())
        ->join(array('fq'=>'forms_questions'), 'pc.id_form=fq.id_form', array())
        ->join(array('q'=>'questions'), 'fq.id_question=q.id', array())
        ->join(array('qg'=>'questions_groups'), 'q.id_group=qg.id', array('id','name'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->group('qg.id')
        ->order('qg.name ASC');
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results;
    }
    
}