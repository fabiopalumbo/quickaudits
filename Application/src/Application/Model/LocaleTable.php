<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class LocaleTable
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
                $where[] = '(l.active = \''.$filter['active'].'\' OR l.active = \'\')';
            else
                $where[] = '(l.active = \''.$filter['active'].'\')';
        }
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('l' => 'locales'))
            ->where(!empty($where) ? implode(' AND ', $where) : 1)
            ->order('l.display_name ASC');
        
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
        ->from(array('l' => 'locales'))
        ->where(array('l.id'=>$id));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
    
        $entity = new Locale();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    public function getDefault()
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('l' => 'locales'))->where(array('l.default'=>'1'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find default row");
        }
    
        $entity = new Locale();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    public function getByCultureName($cultureName)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('l' => 'locales'))->where(array('l.culture_name'=>$cultureName));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find default row");
        }
    
        $entity = new Locale();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    public function getOptionsForSelect()
    {
        $result=$this->fetchAll(false, array('active'=>'1'));
        $options=array();
        foreach ($result as $item)
        {
            $options[$item->id]=$item->display_name;
        }
        return $options;
    }
}