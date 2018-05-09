<?php
namespace Basic\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\AuthenticationService;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $auth = new AuthenticationService();
        $userId = $auth->getIdentity()->id;
        $organizationId = $auth->getIdentity()->id_organization;
        $roleId = $auth->getIdentity()->id_role;
        $projectId = $this->params()->fromPost('project',0);

        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $projects = $projectTable->fetchAll(false, array('active'=>'1','organization'=>$organizationId));
        
        if ($projectId)
            $project = $projectTable->getById($projectId);
        else 
            $project = $projects->current();
                    
        $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
        $dashboardReports = $dashboardReportTable->fetchAllOrganizationDashboardReport($organizationId, $roleId);        
        
        $viewModel = new ViewModel(array(
            'dashboardReports'=>$dashboardReports,
            'projects'=>$projects,
            'selectedProject'=>$project,
            'roleId'=>$roleId
        ));
        
        
        return $viewModel;
    }
}