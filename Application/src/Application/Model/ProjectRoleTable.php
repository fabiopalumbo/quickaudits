<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Session\Container;

class ProjectRoleTable
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

    public function fetchAll($paginated=false)
    {
        $session = new Container('role');

        $where=array();
        $where[] = '(mpr.id_membership=\''.$session->role->membership->id_membership.'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from(array('pr' => 'projects_roles'))
        ->join(array('mpr'=>'memberships_projects_roles'), 'pr.id=mpr.id_project_role', array())
        ->where(!empty($where)?implode(' AND ', $where):1)
        ->order('pr.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Role());
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
    
    public function getByKey($key)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('pr' => 'projects_roles'))->where(array('pr.key'=>$key));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $key");
        }
    
        $entity = new ProjectRole();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    public function getOptionsForSelect()
    {
        $data  = $this->fetchAll();
        
        $selectData = array();
        
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
        
        return $selectData;
    }
    
}