<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\ResultSet;

class SubjectTable
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

    public function fetchAll($paginated=false, $filter = array(), $order = null)
    {
        $where = array();
        
        if (isset($filter['keyword']))
            $where[] = '(s.name LIKE \'%'.$filter['keyword'].'%\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(s.active = \''.$filter['active'].'\' OR s.active = \'\')';
            else
                $where[] = 's.active = \''.$filter['active'].'\'';
        }
        
        if (isset($filter['organization']))
            $where[] = '(s.id_organization = \''.$filter['organization'].'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
                        ->from(array('s' => 'subjects'))
                        ->join(array('o'=>'organizations'), 's.id_organization=o.id', array('organization'=>'name'))
                        ->where(!empty($where) ? implode(' AND ', $where) : 1)
                        ->order(!is_null($order) ? $order : 's.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Subject());
            
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
                ->from(array('s' => 'subjects'))
                ->join(array('o'=>'organizations'), 's.id_organization=o.id', array('organization'=>'name'))
                ->where(array('s.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $subject = new Subject();
        $subject->exchangeArray($row);
        
        return $subject;
    }
    
    public function save(Subject $entity)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            $data = array(
                'name' => $entity->name,
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
            
            $connection->commit();
            
        } catch (\Exception $e) {
            $connection->rollback();
            
            throw $e;
        }
    }

    public function delete($id)
    {
        $entity = $this->getById($id);
        $data = array(
        	'active' => !$entity->active,
        );
        $this->tableGateway->update($data, array('id' => $id));
    }
    
    public function fetchAllForSelect($filter = array())
    {
        $where = array();
        
        $where[] = '(s.active=\'1\')';
    
        if (isset($filter['organization']))
            $where[] = '(s.id_organization = \''.$filter['organization'].'\')';
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('s' => 'subjects'))
        ->join(array('o'=>'organizations'), 's.id_organization=o.id', array('organization'=>'name'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order('s.name ASC');
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $select = array();
        foreach ($results as $item)
        {
            $select[$item->id]=$item->name;
        }
    
        return $select;
    }
    
}