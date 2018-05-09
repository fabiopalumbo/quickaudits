<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Form\UserForm;
use Application\Model\User;
use Application\Form\ChangePasswordForm;
use Application\Filter\ChangePasswordFilter;
use Zend\View\Model\JsonModel;
use Application\Form\UserProjectForm;
use Application\Model\UserProject;
use Application\Filter\UserProjectFilter;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * UserController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class UserController extends AbstractActionController
{
    protected $currentTable;
    protected $auth;
    protected $session;
    protected $translator;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\UserTable');
        }
        return $this->currentTable;
    }
    
    public function getTranslator()
    {
        if (!$this->translator) {
            $sm = $this->getServiceLocator();
            $this->translator = $sm->get('translator');
        }
        return $this->translator;
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction()
    {
        $keyword = $this->params()->fromQuery('keyword');
        $active = $this->params()->fromQuery('active') != '' && is_numeric($this->params()->fromQuery('active')) ? $this->params()->fromQuery('active') : '1';
        $organization = $this->auth->getIdentity()->id_organization;
        $m = $this->params()->fromQuery('m');
        
        $filter = array();
        
        if ($keyword)
            $filter['keyword'] = $keyword;
        
        if (is_numeric($active))
            $filter['active'] = $active;
        
        if ($organization)
            $filter['organization'] = $organization;
            
        // grab the paginator from the RoleTable
        $paginator = $this->getCurrentTable()->fetchAll(true, $filter);
        // set the current page to what has been passed in query string, or to 1 if none set
        $paginator->setCurrentPageNumber((int) $this->params()->fromQuery('page', 1));
        // set the number of items per page to 10
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);
        
        return new ViewModel(array(
            'paginator' => $paginator,
            'filter' => $filter,
            'm'=>$m,
            'subtitle'=>$this->getTranslator()->translate('Users List'),
        ));
    }
    
    public function viewAction()
    {
        $m = (int) $this->params()->fromQuery('m', 0);
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getUser($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        $userProjects = $this->getCurrentTable()->fetchAllUserProjects($id);
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'userProjects'=>$userProjects,
            'subtitle'=>$this->getTranslator()->translate('User Details'),
            'm'=>$m
        );
    }
    
    public function addAction()
    {
        // validate if membership allows to create more items
        $organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
        $currentSubscription = $organizationTable->fetchCurrentSubscription($this->auth->getIdentity()->id_organization);
        $maxAllowedUsers = $organizationTable->countMaxAllowedUsers(array('organization'=>$this->auth->getIdentity()->id_organization,'active'=>'1'));
        $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
        if (!$currentSubscription->in_trial && $items->count() >= $maxAllowedUsers)
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'), array('query' => array('m' => 10)));
        
        $roleTable = $this->getServiceLocator()->get('Application\Model\RoleTable');
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $form = new UserForm($roleTable, $languageTable, $localeTable, $projectRoleTable, $projectTable);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            try {
                
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                
                $user = new User();
                $userProject = new UserProject();
                $form->setInputFilter($user->getInputFilter($dbAdapter));
                $form->setData($request->getPost());
                
                if ($form->isValid()) {
                    
                    $user->exchangeArray($form->getData());
                    $userProject->exchangeArray($form->getData());
                    
                    $this->getCurrentTable()->saveUser($user, $userProject);
                    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'view', 'id' => $user->id), array('query' => array('m' => 1)));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
         
        return array('form' => $form, 'error' => $error, 'subtitle'=>$this->getTranslator()->translate('Add new User'),);
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'add'));
        }

         // Get the User with the specified id.  An exception is thrown
         // if it cannot be found, in which case go to the index page.
         try {
             $entity = $this->getCurrentTable()->getUser($id);
             
             if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                 return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
         }
         
         $roleTable = $this->getServiceLocator()->get('Application\Model\RoleTable');
         $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
         $localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');
         $form = new UserForm($roleTable, $languageTable, $localeTable);
                  
         $form->bind($entity);

         $request = $this->getRequest();
         if ($request->isPost()) {

             try {
                 
                 $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                 
                 $form->setInputFilter($entity->getInputFilter($dbAdapter));
                 $form->setData($request->getPost());
                 
                 if ($form->isValid()) {
                     
                     $this->getCurrentTable()->saveUser($entity);
                 
                     return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'view', 'id' => $id), array('query'=>array('m'=>20)));
                 }                 
             } catch (\Exception $e) {
                 $error = $e->getMessage();
             }
         }
            
         $userProjects = $this->getCurrentTable()->fetchAllUserProjects($id);
         
         return array(
             'id' => $id,
             'form' => $form,
             'error' => $error,
             'userProjects'=>$userProjects,
             'subtitle'=>$this->getTranslator()->translate('Edit User'),
         );
    }
    
    public function changeStatusAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getUser($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->deleteUser($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'user','action'=>'view','id'=>$id));
        }
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$this->getTranslator()->translate('Change User Status'),
        );
    }
    
    public function changePasswordAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'add'));
        }
    
        // Get the User with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $user = $this->getCurrentTable()->getUser($id);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
         
        $form = new ChangePasswordForm();
    
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
                $data = $request->getPost();
                $form->setData($data);
                $form->setInputFilter(new ChangePasswordFilter());
                 
                if ($form->isValid()) {
                    $this->getCurrentTable()->changePassword($id, $data['password']);
                     
                    return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'view', 'id' => $id));
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'subtitle'=>$this->getTranslator()->translate('Change User Password'),
        );
    }
    
    public function getProjectAgentsAction()
    {
        try {
        
            $idProject = (int) $this->params()->fromPost('id_project', 0);
        
            $projectAgents = $this->getCurrentTable()->fetchAllProjectAgents($idProject);
        
            $agents = array();
            
            foreach ($projectAgents as $agent) {
                /* @var $agent \Application\Model\User */
                $agents[] = array(
                    'id'    => $agent->id,
                    'name'  => $agent->name,
                );
            }
            
            return new JsonModel(array('success'=>true,'agents'=>$agents));
        
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
    
    public function addUserProjectAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        // Get the User with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
             $entity = $this->getCurrentTable()->getUser($id);
             
             if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                 return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
         }
         catch (\Exception $ex) {
             return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
         }
        
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectTable = $this->getServiceLocator()->get('Application\Model\ProjectTable');
        $form = new UserProjectForm($projectRoleTable->fetchAll(), $projectTable->fetchAll(false, array('organization'=>$this->auth->getIdentity()->id_organization)));
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
            try {
        
                $userProject = new UserProject();
                $form->setInputFilter(new UserProjectFilter());
                $form->setData($request->getPost());
        
                if ($form->isValid()) {
                    $userProject->exchangeArray($form->getData());
                    $userProject->id_user = $id;
                    $this->getCurrentTable()->saveUserProject($userProject);
        
                    return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'view', 'id' => $id));
                }
        
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
         
        return array('form' => $form, 'error' => $error, 'id'=>$id, 'subtitle'=>$this->getTranslator()->translate('Add new User Project'));
    }
    
    public function changeStatusUserProjectAction()
    {
        $idUser = (int) $this->params()->fromQuery('user', 0);
        $idProject = (int) $this->params()->fromQuery('project', 0);
        $idProjectRole = (int) $this->params()->fromQuery('project_role', 0);
        
        if (!$idUser || !$idProject || !$idProjectRole) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        try {
            $userProject = $this->getCurrentTable()->getUserProject($idUser, $idProject, $idProjectRole);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
    
        $request = $this->getRequest();
    
        if ($request->isPost()) {
            $this->getCurrentTable()->changeStatusUserProject($idUser, $idProject, $idProjectRole);
    
            return $this->redirect()->toRoute('application/default', array('controller'=>'user','action'=>'edit','id'=>$idUser));
        }
    
        return array(
            'id'    => $idUser,
            'entity' => $userProject,
            'subtitle'=>$this->getTranslator()->translate('Change User Project Status'),
        );
    }
    
    public function deleteUserProjectAction()
    {
        $idUser = (int) $this->params()->fromQuery('user', 0);
        $idProject = (int) $this->params()->fromQuery('project', 0);
        $idProjectRole = (int) $this->params()->fromQuery('project_role', 0);
    
        if (!$idUser || !$idProject || !$idProjectRole) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
    
        try {
            $userProject = $this->getCurrentTable()->getUserProject($idUser, $idProject, $idProjectRole);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        
        if ($userProject->blocked)
            return $this->redirect()->toRoute('application/default', array('controller'=>'user','action'=>'edit','id'=>$idUser));
        
        $request = $this->getRequest();
    
        if ($request->isPost()) {
            $this->getCurrentTable()->deleteUserProject($idUser, $idProject, $idProjectRole);
    
            return $this->redirect()->toRoute('application/default', array('controller'=>'user','action'=>'edit','id'=>$idUser));
        }
    
        return array(
            'id'    => $idUser,
            'entity' => $userProject,
            'subtitle'=>$this->getTranslator()->translate('Delete User Project'),
        );
    }
    
    public function manageProfileAction()
    {
        $id = (int) $this->auth->getIdentity()->id;
        
        $m = (int) $this->params()->fromQuery('m',0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'add'));
        }
        
        // Get the User with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getUser($id);
             
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
         
        $roleTable = $this->getServiceLocator()->get('Application\Model\RoleTable');
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');
        $form = new UserForm($roleTable, $languageTable, $localeTable);
        
        $form->bind($entity);
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
            try {
                 
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                 
                $form->setInputFilter($entity->getInputFilter($dbAdapter, false));
                $form->setData($request->getPost());
                 
                if ($form->isValid()) {

                    $this->getCurrentTable()->saveUser($entity);
                     
                    return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'manage-profile'), array('query' => array('m' => 1)));
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        $userProjects = $this->getCurrentTable()->fetchAllUserProjects($id);
         
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'userProjects'=>$userProjects,
            'entity'=>$entity,
            'm'=>$m,
            'subtitle'=>$this->getTranslator()->translate('My Profile'),
        );
    }
    
    public function changePasswordProfileAction()
    {
        $id = (int) $this->auth->getIdentity()->id;
    
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'add'));
        }
    
        // Get the User with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $user = $this->getCurrentTable()->getUser($id);
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'index'));
        }
         
        $form = new ChangePasswordForm();
    
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
                $data = $request->getPost();
                $form->setData($data);
                $form->setInputFilter(new ChangePasswordFilter());
                 
                if ($form->isValid()) {
                    $this->getCurrentTable()->changePassword($id, $data['password']);
                     
                    return $this->redirect()->toRoute('application/default', array('controller'=>'user', 'action' => 'manage-profile'), array('query' => array('m' => 1)));
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'subtitle'=>$this->getTranslator()->translate('Change Password'),
        );
    }
    
    public function dismissWizardAction()
    {
        try {
            
            $auth = new AuthenticationService();
            $session = new Container('role');
            
            $wizardTable = $this->getServiceLocator()->get('Application\Model\WizardTable');
            
            $wizard = $wizardTable->getWizard($session->role->membership->id_membership, $auth->getIdentity()->id_role, $session->role->locale->id);
            
            $wizardTable->completeWizard($auth->getIdentity()->id, $wizard);
            
            return new JsonModel(array('success'=>true));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
    
    public function resetWizardAction()
    {
        $wizardTable = $this->getServiceLocator()->get('Application\Model\WizardTable');
 
        $wizardTable->resetWizard();
        
        return $this->redirect()->toRoute('home');
    }
}