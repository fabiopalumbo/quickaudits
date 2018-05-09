<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;

class ChannelTable
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
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()->from(array('c' => 'channels'))->order('c.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Channel());
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
    
    public function fetchAllProjectChannels($idProject)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('c' => 'channels'))
            ->join(array('pc' => 'projects_channels'), 'c.id=pc.id_channel', array())
            ->join(array('f'=>'forms'), 'pc.id_form=f.id', array())
            ->where('pc.id_project=\''.$idProject.'\' AND f.active=\'1\'')
            ->group('c.id')
            ->order('c.name ASC');
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new Channel());
        $results = $resultSetPrototype->initialize($results);
        
        return $results;
    }

    public function fetchAllOrganizationChannels($idOrganization)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('c' => 'channels'))
            ->join(array('oc' => 'organizations_channels'), 'c.id=oc.id_channel', array())
            ->where('oc.id_organization=\''.$idOrganization.'\'')
            ->group('c.id')
            ->order('c.name ASC');
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new Channel());
        $results = $resultSetPrototype->initialize($results);
        
        return $results;
    }
    
}