<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class StateTable
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

    public function fetchAll($paginated=false, $filter=array())
    {
        $where=array();
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(s.active = \''.$filter['active'].'\' OR s.active = \'\')';
            else
                $where[] = '(s.active = \''.$filter['active'].'\')';
        }
        
        if (isset($filter['id_country']))
        {
            $where[] = '(s.id_country = \''.$filter['id_country'].'\')';
        }
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('s' => 'states'))
            ->join(array('c'=>'countries'), 's.id_country=c.id', array('country'=>'name'))
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order('s.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Locale());
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
        ->from(array('c' => 'countries'))
        ->join(array('c'=>'countries'), 's.id_country=c.id', array('country'=>'name'))
        ->where(array('c.id'=>$id));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
    
        $entity = new Country();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    public function getOptionsForSelect()
    {
        $result=$this->fetchAll(false, array('active'=>'1'));
        $options=array();
        foreach ($result as $item)
        {
            $options[$item->id]=$item->name;
        }
        return $options;
    }
}