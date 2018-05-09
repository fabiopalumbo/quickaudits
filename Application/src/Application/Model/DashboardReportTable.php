<?php
namespace Application\Model;

// use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
// use Zend\Paginator\Adapter\DbSelect;
// use Zend\Paginator\Paginator;
// use Zend\Db\ResultSet\HydratingResultSet;
// use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Db\Sql\Expression;
use Zend\ServiceManager\ServiceLocatorInterface;

class DashboardReportTable
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
    
    public function getTranslator()
    {
        return $this->getServiceLocator()->get('translator');
    }

    /**
     * 
     * @param \Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * 
     * @param int $id
     * @throws \Exception
     * @return \Application\Model\DashboardReport
     */
    public function getById($id)
    {
        $id  = (int) $id;
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('dr'=>'dashboard_reports'))->where(array('dr.id'=>$id));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
    
        $entity = new DashboardReport();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    /**
     *
     * @param string $action
     * @throws \Exception
     * @return \Application\Model\DashboardReport
     */
    public function getByAction($action)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('dr'=>'dashboard_reports'))->where(array('dr.action'=>$action));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row $action");
        }
    
        $entity = new DashboardReport();
        $entity->exchangeArray($row);
    
        return $entity;
    }
    
    /**
     * 
     * @param unknown $idOrganization
     * @param string $idRole
     * @param string $form
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAllOrganizationDashboardReport($idOrganization, $idRole=NULL, $form=false)
    {
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $organizationMembership = $membershipTable->fetchOrganizationMembership($idOrganization);
        
        $where=array();
        
        $where[]='(r.is_admin<>\'1\')';
        $where[]='(mdr.id_membership=\''.$organizationMembership->id_membership.'\')';
        $where[]='(mr.id_membership=\''.$organizationMembership->id_membership.'\')';
        
        if (!$form)
            $where[]='(odr.id_organization = \''.$idOrganization.'\')';
        
        if (!is_null($idRole))
            $where[]='(r.id = \''.$idRole.'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
            ->from(array('r' => 'roles'))
            ->columns(array('id_role'=>'id','role'=>'name','selected'=>new Expression('IF(odr.id_organization IS NOT NULL, 1, 0)')))
            ->join(array('mr'=>'memberships_roles'), 'r.id=mr.id_role', array())
            ->join(array('dr'=>'dashboard_reports'), new Expression('1'), array('id_dashboard_report'=>'id','dashboard_report'=>'name','dashboard_report_description'=>'description','action','widget_size'))
            ->join(array('mdr'=>'memberships_dashboard_reports'), 'dr.id=mdr.id_dashboard_report',array('id_membership'))
            ->join(array('odr'=>'organizations_dashboard_reports'), new Expression('r.id=odr.id_role AND dr.id=odr.id_dashboard_report AND odr.id_organization = \''.$idOrganization.'\''), array('display_order' ), 'left')
            ->where(implode(' AND ', $where))
            ->order(array('r.name ASC', /*new Expression('odr.display_order IS NOT NULL DESC'),*/'odr.display_order ASC','dr.name ASC'));
        
        
        $selectString = $sql->getSqlStringForSqlObject($select);
//print_r($selectString);die; 
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $results->buffer();
        
        return $results;
    }
    
    public function countAllOrganizationRoleDashboardReport($idOrganization, $idRole)
    {
        $where=array();
        
        $where[]='(odr.id_organization=\''.$idOrganization.'\')';
        $where[]='(odr.id_role=\''.$idRole.'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()->from(array('odr'=>'organizations_dashboard_reports'))->columns(array('total'=>new Expression('COUNT(*)')))->where(implode(' AND ', $where));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        return $results->current()->total;
    }
    
    public function addOrganizationRoleDashboard($idOrganization, $idRole, $idDashboard, $existsTransaction=false)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        if (!$existsTransaction)
            $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
        
            if (!$existsTransaction)
                $connection->beginTransaction();
            
            // insert
            // count how many reports are assigned for the selected role
            $total = $this->countAllOrganizationRoleDashboardReport($idOrganization, $idRole);
            
            $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
            $organizationMembership = $membershipTable->fetchOrganizationMembership($idOrganization);

            if ($total >= $organizationMembership->max_dashboard_reports)
                throw new \Exception(sprintf($this->getTranslator()->translate('Your organization is allowed to display up to %s reports for each role.'),$organizationMembership->max_dashboard_reports));
        
            $data = array(
                'id_organization' => $idOrganization,
                'id_dashboard_report' => $idDashboard,
                'id_role' => $idRole,
            );
            
            $tableGateway = new TableGateway('organizations_dashboard_reports', $dbAdapter);
        
            $tableGateway->insert($data);
        
            if (!$existsTransaction)
                $connection->commit();
        
        } catch (\Exception $e) {
            
            if (!$existsTransaction)
                $connection->rollback();
        
            throw $e;
        }       
    }
    
    public function deleteOrganizationRoleDashboard($idOrganization, $idRole, $idDashboard)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        $connection = $dbAdapter->getDriver()->getConnection();
    
        try {
    
            $connection->beginTransaction();
    
            $tableGateway = new TableGateway('organizations_dashboard_reports', $dbAdapter);
            
            $tableGateway->delete(array('id_organization'=>$idOrganization,'id_role'=>$idRole,'id_dashboard_report'=>$idDashboard));
    
            $connection->commit();
    
        } catch (\Exception $e) {
            $connection->rollback();
    
            throw $e;
        }
    }
    
    public function sortOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReportsIds)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        $connection = $dbAdapter->getDriver()->getConnection();
    
        try {
    
            $connection->beginTransaction();
    
            // insert
            // count how many reports are assigned for the selected role
//             $total = $this->countAllOrganizationRoleDashboardReport($idOrganization, $idRole);
    
//             if ($total >= 5)
//                 throw new \Exception('Your organization is allowed to display up to 5 reports for each role.');
    
//             $data = array(
//                 'id_organization' => $idOrganization,
//                 'id_dashboard_report' => $idDashboard,
//                 'id_role' => $idRole,
//             );
    
            $tableGateway = new TableGateway('organizations_dashboard_reports', $dbAdapter);

            if(is_string($dashboardReportsIds))
            {
                $dashboardReportsIds = explode(',', $dashboardReportsIds);
            }

            foreach ($dashboardReportsIds as $displayOrder=>$idDashboardReport)
            {
                $where = array(
                    'id_organization' => $idOrganization,
                    'id_dashboard_report' => $idDashboardReport,
                    'id_role' => $idRole,
                );
                
                $tableGateway->update(array('display_order'=>$displayOrder), $where);
            }            
    
            $connection->commit();
    
        } catch (\Exception $e) {
            $connection->rollback();
    
            throw $e;
        }
    }
    
}