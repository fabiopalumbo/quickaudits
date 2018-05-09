<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
// use Application\Model\Organization;
use Application\Form\OrganizationForm;
use Application\Filter\OrganizationFilter;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Application\Form\BillingDetailForm;
use Application\Model\OrganizationBillingDetail;
use Application\Filter\BillingDetailFilter;
use Application\Form\SubscriptionForm;
// use Application\Model\OrganizationSubscription;
use Application\Filter\SubscriptionFilter;
// use Application\Form\ManageMembershipForm;
// use Application\Filter\ManageMembershipFilter;

/**
 * OrganizationController
 *
 * @author Ariel Lipschutz <alipschutz@clickwayit.com>
 *
 * @version
 *
 */
class OrganizationController extends AbstractActionController
{
    protected $currentTable;
    protected $auth;
    protected $session;
    protected $translator;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }
    
    public function getTranslator()
    {
        if (!$this->translator) {
            $sm = $this->getServiceLocator();
            $this->translator = $sm->get('translator');
        }
        return $this->translator;
    }
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\OrganizationTable');
        }
        return $this->currentTable;
    }
    
    public function manageProfileAction()
    {
        $id = (int) $this->auth->getIdentity()->id_organization;
        
        if (!$id) {
            return $this->redirect()->toRoute('home');
        }
        
        $m = $this->params()->fromQuery('m',0);
    
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('home');
        }
    
        $form = new OrganizationForm();
    
        $form->bind($entity);
    
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
                $form->setInputFilter(new OrganizationFilter());
                $form->setData($request->getPost());
                 
                if ($form->isValid()) {
                    
                    $entity->id = $id;
                    
                    $this->getCurrentTable()->save($entity);
                     
                    return $this->redirect()->toRoute('application/default', array('controller'=>'organization', 'action' => 'manage-profile'), array('query' => array('m' => 1)));
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $membership = $membershipTable->fetchOrganizationMembership($id);
    
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'm'=>$m,
            'membership'=>$membership,
            'subtitle'=>$this->getTranslator()->translate('Manage Profile')
        );
    }
    
    public function manageDashboardAction()
    {
        $id = (int) $this->auth->getIdentity()->id_organization;
        
        if (!$id) {
            return $this->redirect()->toRoute('home');
        }
        
        $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
        
        $dashboardReports = $dashboardReportTable->fetchAllOrganizationDashboardReport($id, null, true);

        
        return new ViewModel(array('dashboardReports'=>$dashboardReports,'subtitle'=>_('Manage Dashboard')));
    }
    
    public function updateDashboardAction()
    {
        $idOrganization = (int) $this->auth->getIdentity()->id_organization;
        $idRole = (int) $this->params()->fromPost('role', 0);
        $idDashboardReport = (int) $this->params()->fromPost('dashboard_report', 0);
        $action = (int) $this->params()->fromPost('action');
        
        try {
            
            $roleTable = $this->getServiceLocator()->get('Application\Model\RoleTable');
            $role = $roleTable->getById($idRole);
            
            $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
            $dashboardReport = $dashboardReportTable->getById($idDashboardReport);

            if ($action===1)
            {
                // insert
                $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $idDashboardReport);
                
                return new JsonModel(array('success'=>true,'message'=>sprintf($this->getTranslator()->translate('<strong>%s</strong> was added to <strong>%s</strong> successfully!'), $this->getTranslator()->translate($dashboardReport->name), $role->name)));
            }
            else 
            {
                // delete
                $dashboardReportTable->deleteOrganizationRoleDashboard($idOrganization, $idRole, $idDashboardReport);
                
                return new JsonModel(array('success'=>true,'message'=>sprintf($this->getTranslator()->translate('<strong>%s</strong> was removed from <strong>%s</strong> successfully!'),$this->getTranslator()->translate($dashboardReport->name),$role->name)));
            }
            
            
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
    
    public function sortDashboardAction()
    {
        $idOrganization = (int) $this->auth->getIdentity()->id_organization;
        $idRole = (int) $this->params()->fromPost('role', 0);
        $dashboardReportsIds = $this->params()->fromPost('dashboard_reports');
    
        try {
    
            $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
    
            $dashboardReportTable->sortOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReportsIds);

            return new JsonModel(array('success'=>true,'message'=>$this->getTranslator()->translate('Dashboard was sorted successfully!')));
            
    
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
    
    public function manageBillingDetailsAction()
    {
        $m = $this->params()->fromQuery('m');
        $id = (int) $this->auth->getIdentity()->id_organization;
    
        if (!$id) {
            return $this->redirect()->toRoute('home');
        }
    
        $m = $this->params()->fromQuery('m',0);
    
        $entity = $this->getCurrentTable()->fetchCurrentBillingDetails($id);
    
        return new ViewModel(
            array( 
                'entity'=>$entity,
                'subtitle'=>$this->getTranslator()->translate('Billing Details'),
                'm'=>$m
            )
        );
    }
    
    public function addBillingDetailsAction()
    {
        $countryTable = $this->getServiceLocator()->get('Application\Model\CountryTable');
        $form = new BillingDetailForm($countryTable);

        $request = $this->getRequest();
        if ($request->isPost()) {

            try {

                $entity = new OrganizationBillingDetail();
                $form->setInputFilter(new BillingDetailFilter());
                $form->setData($request->getPost());

                if ($form->isValid()) {
                    $entity->exchangeArray($form->getData());
                    
                    $this->getCurrentTable()->saveBillingDetails($entity);
                    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'organization', 'action' => 'manage-billing-details'), array('query'=>array('m'=>1)));
                }

            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
 
        return new ViewModel(array('form' => $form, 'error' => $error, 'subtitle'=>$this->getTranslator()->translate('Add New Billing Details')));
    }
    
    public function manageSubscriptionAction()
    {
        $m = $this->params()->fromQuery('m');
        $id = (int) $this->auth->getIdentity()->id_organization;
        
        if (!$id) {
            return $this->redirect()->toRoute('home');
        }
    
        $m = $this->params()->fromQuery('m',0);
        
        try {
            $entity = $this->getCurrentTable()->fetchCurrentSubscription($id);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('home');
        }
        
        // get other possible active subscriptions
        $oldSubscriptions = $this->getCurrentTable()->fetchAllSubscriptions(array('organization'=>$this->auth->getIdentity()->id_organization,'active'=>'1','not_end_date'=>true,'not_id'=>$entity->id));

        return new ViewModel(
            array(
                'entity'=>$entity,
                'subtitle'=>$this->getTranslator()->translate('Plan Details'),
                'm'=>$m,
                'oldSubscriptions'=>$oldSubscriptions,
            )
        );
    }
    
    public function editSubscriptionAction()
    {
        $id = (int) $this->auth->getIdentity()->id_organization;
        
        if (!$id) {
            return $this->redirect()->toRoute('home');
        }
        
        $m = $this->params()->fromQuery('m',0);
        
        try {
            $entity = $this->getCurrentTable()->fetchCurrentSubscription($id);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('home');
        }
        
        $countryTable = $this->getServiceLocator()->get('Application\Model\CountryTable');
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        $form = new SubscriptionForm($countryTable, $membershipTable, $this->getServiceLocator());

        $form->bind($entity);
    
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
                $data=$request->getPost();
                $form->setInputFilter(new SubscriptionFilter($this->getServiceLocator()));
                $form->setData($data);
                
                if ($form->isValid()) {
                    
                    $entity->getBillingDetails()->exchangeArray($data);
                    
                    $this->getCurrentTable()->changeSubscriptionPlan($entity, $data->update_cc);
                    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'organization', 'action' => 'manage-subscription'), array('query'=>array('m'=>1)));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        // fetch current billing details
        $billing = $this->getCurrentTable()->fetchCurrentBillingDetails($id);
    
        return new ViewModel(
            array(
                'form'=>$form, 
                'error'=>$error, 
                'subtitle'=>$this->getTranslator()->translate('Change Plan'),
                'billing'=>$billing,
            )
        );
    }
    
    public function calculateTotalPriceAction()
    {
        try {
            
            $id_membership = (int) $this->params()->fromPost('membership',0);
            $maxUsers = (int) $this->params()->fromPost('users',0);
            $billingPeriod = $this->params()->fromPost('period');
            
            $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
            $total = $membershipTable->calculatePrice($id_membership, $maxUsers, $billingPeriod); 

            $membership = $membershipTable->getById($id_membership);

            $memberShipPrice = $membershipTable->getPrices($id_membership, $maxUsers); 
            
            $unitPrice = $billingPeriod=='month'?$memberShipPrice->price_month:$memberShipPrice->price_year;
            
            $auth = new AuthenticationService();
            $currentSubscriptionDetails = $this->getCurrentTable()->fetchCurrentSubscription($auth->getIdentity()->id_organization);
            
            // set price per user from membership
            $pricePerUser = $unitPrice; //$billingPeriod=='month'?$membership->price_month:$membership->price_year;
            
            // set total days in current subscription period
            $totalDaysInPeriod = $currentSubscriptionDetails->billing_period=='month'?30:365;
            
            
            $dateInterval = date_diff(date_create(date('Y-m-d')), date_create($currentSubscriptionDetails->next_billing_date));
            $remainingPercentageInPeriod = $dateInterval->days / $totalDaysInPeriod;
            
            $paymentTotal = 0;

            if ($id_membership != $currentSubscriptionDetails->id_membership || $currentSubscriptionDetails->end_date)
            {
                $paymentTotal = $maxUsers * $pricePerUser;
            }
            elseif ($billingPeriod == $currentSubscriptionDetails->billing_period && $maxUsers > $currentSubscriptionDetails->max_users)
            {
                $aditionalUsers = $maxUsers - $currentSubscriptionDetails->max_users;
                $paymentTotal = ($aditionalUsers * $pricePerUser) * $remainingPercentageInPeriod;
            }
            elseif ($billingPeriod != $currentSubscriptionDetails->billing_period && 
                    $maxUsers >= $currentSubscriptionDetails->max_users)
            {
                $currentUsersDiscount = ($currentSubscriptionDetails->max_users * $currentSubscriptionDetails->unit_price) * $remainingPercentageInPeriod;
                $paymentTotal = ($maxUsers * $pricePerUser) - $currentUsersDiscount;
            }
            
            $paymentTotal=$paymentTotal<0?0:$paymentTotal;
            
            return new JsonModel(array(
                'success'=>true,
                'total'=>$total,
                'unit_price'=>number_format($unitPrice,2),
                'remaining_days'=>$dateInterval->days,
                'payment_total'=>number_format($paymentTotal,2)
            ));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
    
    public function cancelSubscriptionAction()
    {
        $session = new Container('role');
        
        if ($session->role->membership->package=='basic')
            return $this->redirect()->toRoute('home');
        
        $id = (int) $this->auth->getIdentity()->id_organization;
        
        if (!$id) {
            return $this->redirect()->toRoute('home');
        }
        
        try {
            $entity = $this->getCurrentTable()->fetchCurrentSubscription($id);
            
            if ($entity->end_date || !$entity->active)
                return $this->redirect()->toRoute('application/default', array('controller'=>'organization','action'=>'manage-subscription'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'organization','action'=>'manage-subscription'));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            
            $this->getCurrentTable()->cancelSubscription();
        
            return $this->redirect()->toRoute('application/default', array('controller'=>'organization','action'=>'manage-subscription'), array('query'=>array('m'=>'20')));
        }
        
        return new ViewModel(
            array(
                'entity'=>$entity,
                'subtitle'=>$this->getTranslator()->translate('Cancel Subscription'),
            )
        );
    }
}