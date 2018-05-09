<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\ServiceManager\ServiceLocatorInterface;

class MembershipTable
{
    /**
     * 
     * @var \Zend\Db\TableGateway\TableGateway
     */
    protected $tableGateway;
    
    /**
     *
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;
    
    /**
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sl
     * @return \Application\Model\OrganizationTable
     */
    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
        return $this;
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * 
     * @param \Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($paginated=false)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()->from(array('m' => 'memberships'))->order('m.name ASC');
        
        if ($paginated) {
            // create a new result set based on the Album entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Membership());
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
    
    public function fetchForSelect($include_trial=false)
    {
        $results = $this->fetchAll();
        
        $select = array();
        foreach ($results as $item)
        {
            /* @var $item \Application\Model\Membership */
            $select[$item->id]=$item->name.($include_trial && $item->trial_days ? ' ('.$item->trial_days.' day trial)' : '');
        }
        
        return $select;
    }
    
    public function fetchOrganizationMembership($idOrganization)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from(array('om' => 'organizations_memberships'))
        ->columns(array('id_organization','id_membership'))
        ->join(array('m'=>'memberships'), 'om.id_membership=m.id', array('membership'=>'name','package','module','upgrade','min_users','max_dashboard_reports'))
        ->where('om.active=\'1\' AND om.id_organization=\''.$idOrganization.'\'');
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $results->current();
        
        if (!$row) {
            throw new \Exception("Could not find row for $idOrganization");
        }
        
        $entity = new OrganizationMembership();
        $entity->exchangeArray($row);
        
        return $entity;
    }
    
    public function updateOrganizationMembership(OrganizationMembership $entity)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            // get current membership, if it has changed create new record of membership and disable current one
            $currentMembership = $this->fetchOrganizationMembership($entity->id_organization);
            
            if ($currentMembership->id_membership == $entity->id_membership)
                return;
            
            // validate amount of users & validate amount of other items
            // get total users 
            $userTable = new UserTable($this->tableGateway);
            $users = $userTable->fetchAll(false, array('active'=>'1','organization'=>$entity->id_organization));
            
            // TODO validate you can't create more items            
            $organizationMembershipTable = new TableGateway('organizations_memberships', $dbAdapter);
            
            $organizationMembershipTable->update(
                array(
                    'active'=>'0',
                    'modified'=>date("Y-m-d H:i:s"),
                    'modified_by'=>$auth->getIdentity()->id), 
                array(
                    'id_organization'=>$entity->id_organization,
                    'active'=>'1'
                ));
            
            $organizationMembershipTable->insert(array(
                'id_organization'=>$entity->id_organization,
                'id_membership'=>$entity->id_membership,
                'id_plan'=>$entity->id_plan,
                'created'=>date("Y-m-d H:i:s"),
                'modified'=>date("Y-m-d H:i:s"),
                'created_by'=>$entity->id_organization,
                'modified_by'=>$entity->id_organization,
            ));
            
            $connection->commit();
    
        } catch (\Exception $e) {
            $connection->rollback();
    
            throw $e;
        }
    }
    
    public function getById($id)
    {
        $id  = (int) $id;
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('m' => 'memberships'))->where(array('m.id'=>$id));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
    
        $entity = new Membership();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    public function calculatePrice_old($id, $maxUsers, $billingPeriod)
    {
        $membership = $this->getById($id);
        
        $total = number_format($maxUsers * ($billingPeriod=='month' ? $membership->price_month : $membership->price_year), 2);
        
        return $total;
    }
    
    public function calculatePrice($id, $maxUsers, $billingPeriod)
    {
        //$membership = $this->getById($id);

        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $where = $sql->select()
                    ->where
                        ->equalTo('mp.id_membership', $id)
                        ->and
                        ->greaterThanOrEqualTo('mp.max_users', $maxUsers)
                        ->and
                        ->lessThanOrEqualTo('mp.min_users', $maxUsers);

        $select = $sql->select()
            ->from(array('mp' => 'memberships_prices'))
            ->where($where);        
    
        $selectString = $sql->getSqlStringForSqlObject($select);

        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        $row = $rowset->current();
 
        if (!$row) {
            //throw new \Exception("Could not find price for $maxUsers and $billingPeriod");
            return $this->calculatePrice_old($id, $maxUsers, $billingPeriod);
        }

        $entity = new MembershipPrice();

        $entity->exchangeArray($row);



        $total = number_format($maxUsers * ($billingPeriod=='month' ? $entity->price_month : $entity->price_year), 2);

        return $total;
    }

    public function getPrices($id, $maxUsers)
    {
        //$membership = $this->getById($id);

        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $where = $sql->select()
                    ->where
                        ->equalTo('mp.id_membership', $id)
                        ->and
                        ->greaterThanOrEqualTo('mp.max_users', $maxUsers)
                        ->and
                        ->lessThanOrEqualTo('mp.min_users', $maxUsers);

        $select = $sql->select()
            ->from(array('mp' => 'memberships_prices'))
            ->where($where);        
    
        $selectString = $sql->getSqlStringForSqlObject($select);

        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        $row = $rowset->current();
 
        if (!$row) {
            //throw new \Exception("Could not find price for $maxUsers and $billingPeriod");
            return $this->calculatePrice_old($id, $maxUsers, $billingPeriod);
        }

        $entity = new MembershipPrice();

        $entity->exchangeArray($row);
        
        return $entity;
    }
    
    
    /**
     * Check if current membership allows agents
     * @param int $id
     * @return boolean
     */
    public function hasAgents($id) {
        
        $membership = $this->getById($id);
        
        return $membership->package == 'contact_center';
        
    }
}