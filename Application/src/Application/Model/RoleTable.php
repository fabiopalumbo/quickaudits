<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

class RoleTable
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
        $session = new Container('role');
        
        $where=array();
        
        $where[] = '(r.is_admin!=\'1\')';
        $where[] = '(mr.id_membership=\''.$session->role->membership->id_membership.'\')';
        
        if (isset($filter['keyword']))
            $where[] = '(r.name LIKE \'%'.$filter['keyword'].'%\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('r' => 'roles'))
            ->join(array('mr'=>'memberships_roles'), 'r.id=mr.id_role', array())
            ->where(!empty($where)?implode(' AND ', $where):1)
            ->order('r.name ASC');
        
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

    public function getById($id)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()->from(array('r' => 'roles'))->where(array('r.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $role = new Role();
        $role->exchangeArray($row);
        
        return $role;
    }

    public function save(Role $entity)
    {
        
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {

            $connection->beginTransaction();
            
            $data = array(
                'name' => $entity->name,
            );
            
            $auth = new AuthenticationService();
            
            $id = (int) $entity->id;
            if ($id == 0) {
                
                $data['created'] = date("Y-m-d H:i:s");
                $data['created_by'] = $auth->getIdentity()->id;
                $data['modified'] = date("Y-m-d H:i:s");
                $data['modified_by'] = $auth->getIdentity()->id;
                
                $this->tableGateway->insert($data);
                $id = $this->tableGateway->lastInsertValue;
            } else {
                if ($this->getById($id)) {
                    
                    $data['modified'] = date("Y-m-d H:i:s");
                    $data['modified_by'] = $auth->getIdentity()->id;
                    
                    $this->tableGateway->update($data, array('id' => $id));
                } else {
                    throw new \Exception('Role id does not exist');
                }
            }

            $rolesPermissionsTable = new TableGateway('roles_permissions', $dbAdapter);            
            
            $rolesPermissionsTable->delete(array('id_role'=>$id));
                        
            foreach($entity->permissions as $item){                
                
                if (is_array($item) && $item['checked'])
                {
                    $permission = new Permission();
                    $permission->exchangeArray($item);

                    $rolesPermissionsTable->insert(array('id_role'=>$id,'id_permission'=>$permission->id));
                }
                elseif (!is_array($item) && $item->checked)
                {
                    $rolesPermissionsTable->insert(array('id_role'=>$id,'id_permission'=>$item->id));
                }
            }
        
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();        
        }        
    }
    
    public function getDefault()
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('r' => 'roles'))->where(array('r.is_default'=>'1'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find default row");
        }
    
        $role = new Role();
        $role->exchangeArray($row);    
        return $role;
    }
}