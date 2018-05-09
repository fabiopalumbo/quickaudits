<?php
namespace Admin\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

use Zend\Db\Sql\Expression;

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

    public function fetchAll($paginated=false)
    {
        
        if ($paginated) {
            // create a new Select object for the table album
            $select = new Select('roles');
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
        
        $resultSet = $this->tableGateway->select();
        return $resultSet;
    }

    public function getRole($id)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
                    ->from(array('r' => 'roles'))
                    ->join(array(
                            'rp' => 'roles_permissions'), 
                            'r.id = rp.id_role', 
                            array('permissions' => new Expression('GROUP_CONCAT(id_permission SEPARATOR \', \')')), 'left')
                    ->where(array('r.id'=>$id))
                    ->group(array('r.id'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $row->permissions = array_map('trim', explode(',', $row->permissions));
        
        $role = new Role();
        $role->exchangeArray($row);
        
        return $role;
    }

    public function saveRole(Role $entity)
    {
        
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {

            $connection->beginTransaction();
            
            $data = array(
                    'name' => $entity->name,
            );
            
            $id = (int) $entity->id;
            if ($id == 0) {
                $this->tableGateway->insert($data);
                $id = $this->tableGateway->lastInsertValue;
            } else {
                if ($this->getRole($id)) {
                    $this->tableGateway->update($data, array('id' => $id));
                } else {
                    throw new \Exception('Role id does not exist');
                }
            }

            $rolesPermissionsTable = new TableGateway('roles_permissions', $dbAdapter);            
            
            $rolesPermissionsTable->delete(array('id_role'=>$id));
                        
            foreach($entity->permissions as $permission){                
                $rolesPermissionsTable->insert(array('id_role'=>$id,'id_permission'=>$permission));
            }
        
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();

            throw $e;
        }
    }

    public function deleteRole($id)
    {
        $role = new RoleTable($this->tableGateway);
        $role = $role->getRole($id);
        $data = array(
        	'active' => !$role->active,
        );
        $this->tableGateway->update($data, array('id' => $id));
    }
}