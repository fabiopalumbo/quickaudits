<?php
namespace Admin\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class UserTable
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

        $select = $sql->select()
        ->from(array('u' => 'users'))
        ->columns(array('*'))
        ->join(array('r' => 'roles'), 'r.id = u.id_role', array('role' => 'name'));
        
        if ($paginated) {
            // create a new Select object for the table album
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

    public function getUser($id)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
                ->from(array('u' => 'users'))
                ->join(array('r' => 'roles'),
                        'u.id_role = r.id',
                array('user_name' => 'name', 'user_active' => 'active'))
                ->where(array('u.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        $role = new Role();
        $role->name = $row->user_name;
        $role->active = $row->user_active;
        $row->role = $role;
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $user = new User();
        $user->exchangeArray($row);

        return $user;
    }

    public function saveUser(User $entity)
    {
        $data = array(
                'firstname' => $entity->firstname,
                'lastname' => $entity->lastname,
                'email' => $entity->email,
                'id_role' => $entity->id_role,
        );

        $id = (int) $entity->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getUser($id)) {
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('User id does not exist');
            }
        }
    }

    public function deleteUser($id)
    {
        $user = new UserTable($this->tableGateway);
        $user = $user->getUser($id);
        $data = array(
        	'active' => !$user->active,
        );
        $this->tableGateway->update($data, array('id' => $id));
    }
}