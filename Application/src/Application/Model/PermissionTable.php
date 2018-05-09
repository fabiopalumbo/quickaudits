<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Application\Model\Permission;
use Zend\Db\Sql\Expression;

class PermissionTable
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

    public function fetchAll($paginated=false, $idRole=NULL)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()->from(array('p' => 'permissions'))->order(array('p.category ASC, p.name'));
        
        if (!is_null($idRole))
            $select->columns(array('*','checked'=>new Expression('IF (rp.id_role IS NOT NULL, 1, 0)')))
                    ->join(array('rp'=>'roles_permissions'), new Expression('p.id=rp.id_permission AND (rp.id_role IS NULL OR rp.id_role = '.$idRole.')'), array(), 'left');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Permission());
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

    public function getPermission($id)
    {
        $id  = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }

    public function savePermission(Permission $entity)
    {
        $data = array(
                'name' => $entity->name,
                'category' => $entity->category,
                'key' => $entity->key,
                'controller' => $entity->controller,
                'action' => $entity->action,
        );

        $id = (int) $entity->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getPermission($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('Permission id does not exist');
            }
        }
    }
    
    public function fetchByRole ($idRol){
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
            ->from(array('p' => 'permissions'))
            ->join(array('rp' => 'roles_permissions'),'p.id = rp.id_permission')
            ->where(array('rp.id_role'=>$idRol));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        
//         foreach($results as $result){
//             $permission = new Permission();
//             $permission->exchangeArray($result);
//             $permissions[] = $permission;
//         }
    
        return $results;
    
    }
    
    
}