<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/Admin for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\Session\Container;
use Application\Model\User;
use Application\Model\UserTable;
use Application\Model\QuestionGroup;
use Application\Model\QuestionGroupTable;
use Application\Model\Role;
use Application\Model\RoleTable;
use Application\Model\Permission;
use Application\Model\PermissionTable;
use Application\Model\LanguageTable;
use Application\Model\QuestionTable;
use Application\Model\Question;
use Application\Model\Project;
use Application\Model\ProjectTable;
use Application\Model\Language;
use Application\Model\ChannelTable;
use Application\Model\Channel;
use Application\Model\FormTable;
use Application\Model\Form;
use Application\Model\Listening;
use Application\Model\ListeningTable;
use Application\Model\ReportTable;
use Application\Model\Organization;
use Application\Model\OrganizationTable;
use Application\Model\ProjectRoleTable;
use Application\Model\ProjectRole;
use Application\Model\SubjectTable;
use Application\Model\DashboardReport;
use Application\Model\DashboardReportTable;
use Application\Model\MembershipTable;
use Application\Model\Membership;
use Application\Model\LocaleTable;
use Application\Model\Locale;
use Application\Model\WizardTable;
use Application\Model\Wizard;
use Application\Model\CountryTable;
use Application\Model\StateTable;
use Application\Model\State;
use Application\Model\Country;
use Zend\Validator\AbstractValidator;
use Locale as Zend_Locale;
use Basic\Form\ListeningForm;
use Zend\Console\Request as ConsoleRequest;

class Module/* implements FormElementProviderInterface*/
{
    protected $whitelist = array(
        'login',
        'confirm-token',
        'register',
        'register-success',
        'reset-password',
        'reset-success',
        'public_listening',
        'public_listening_success',
    );
    protected $ajaxRequests = array(
        'get-project-channels',
        'get-project-agents',
        'get-project-languages',
        'get-project-channel-form',
        'total-fatals-per-agent',
        'update-dashboard',
        'sort-dashboard',

        'get-organization-channels',
        'get-channel-projects',
    );
    protected $ajaxControllerRequests = array(
        'listening'=>array('save','add-public','success-public'),
        'user'=>array('dismiss-wizard','reset-wizard'),
        'country'=>array('get-country-states'),
        'organization'=>array('calculate-total-price'),
        'qrcode'=>array('generate'),
        'project'=>array('render-qr-code')
    );

    /**
     * 
     * @param \Zend\Mvc\MvcEvent $e
     * @return void|unknown
     */
    public function onBootstrap(MvcEvent $e)
    {
        $auth = new AuthenticationService();
        $session = new Container('role');
        
        // You may not need to do this if you're doing it elsewhere in your
        // application
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $app = $e->getApplication();
        $sm  = $app->getServiceManager();
        
        $list = $this->whitelist;

        /* @var $request \Zend\Http\PhpEnvironment\Request */
        $request = $sm->get('Request');
        
        if (!$request instanceof ConsoleRequest){
            
        
        
        $ajaxRequests = $this->ajaxRequests;
        $ajaxControllerRequests = $this->ajaxControllerRequests;
        
        $cultureName = $request->getQuery('locale');
        
        // set locale for current user
        if (!$session->role->locale && !$cultureName)
        {
            $localeTable = $sm->get('Application\Model\LocaleTable');
            $locale = $localeTable->getDefault();
        }
        elseif ($cultureName)
        {
            $localeTable = $sm->get('Application\Model\LocaleTable');
            $locale = $localeTable->getByCultureName($cultureName);
        }
        else 
        {
            $locale = $session->role->locale;
        }

        if ($session->role)
            $session->role->locale = $locale;
        else
        {
            $role  = new Role();
            $session->role = $role;
            $session->role->locale = $locale;
        }

        $config = $e->getApplication()->getServiceManager()->get('config');
        // set locale for current user
        $translator = $e->getApplication()->getServiceManager()->get('translator');
        $translator->setLocale(str_replace('-', '_', $locale->culture_name))->setFallbackLocale('en_US');
        $cultureName = explode('_', $translator->getLocale());
        Zend_Locale::setDefault($translator->getLocale());
        $translator->addTranslationFile(
            'phpArray',
            $config['paths']['translation_file_path'].$cultureName[0].'/Zend_Validate.php'
        );
        AbstractValidator::setDefaultTranslator($translator);
        
        if ($session->role) {
            // set translation file for current membership package
            if ($session->role->membership->package && is_dir(__DIR__ . '/language/package/' . $session->role->membership->package))
                $translator->addTranslationFilePattern('gettext', __DIR__ . '/language/package/' . $session->role->membership->package, '%s.mo');            
        }
        
        // end locale
        
        $eventManager->attach(MvcEvent::EVENT_ROUTE, function($e) use ($list, $auth, $ajaxRequests, $ajaxControllerRequests, $session, $sm, $locale) {
            $match = $e->getRouteMatch();
        
            // No route match, this is a 404
            if (!$match instanceof RouteMatch) {
                return;
            }
        
            // Route is whitelisted
            $name = $match->getMatchedRouteName();
        
            if (in_array($name, $list)) {
                return;
            }
            
            $userTable = $sm->get('Application\Model\UserTable');
            $sessionId = $userTable->getSessionId($auth->getIdentity()->id);

            // User is authenticated
            if ($auth->hasIdentity()) {
                
                // Validate if user has permission to access or not
                $controllerParamName = \Zend\Mvc\ModuleRouteListener::ORIGINAL_CONTROLLER;
                $controller = $match->getParam($controllerParamName) ?: strtolower(end(explode('\\',$match->getParam('controller'))));
                $action     = $match->getParam('action');
                
                if ($auth->getIdentity() 
                    && $auth->getIdentity()->session_id != $sessionId
                    && !isset($ajaxControllerRequests[$controller])
                    && !in_array($action, $ajaxControllerRequests[$controller]))
                {
                    // Redirect to the user login page
                    $router   = $e->getRouter();
                    $url      = $router->assemble(array(), array(
                        'name' => 'login'
                    ));
                
                    $response = $e->getResponse();
                    $response->getHeaders()->addHeaderLine('Location', $url);
                    $response->setStatusCode(302);
                
                    return $response;
                }
                
                if ($session->role && $session->role->hasPermission($controller.'_'.$action)
                    || (($controller == 'auth' && $action=='logout')
                    || ($controller == 'index') 
                    || in_array($action, $ajaxRequests)
                    || (isset($ajaxControllerRequests[$controller]) && in_array($action, $ajaxControllerRequests[$controller]))
                    || ($controller == 'form' && $action=='validate')
                    || ($controller == 'user' && $action=='manage-profile')
                    || ($controller == 'user' && $action=='change-password-profile')
                    || ($controller == 'dashboard'))
                ){
        
                    $viewModel = $e->getApplication()->getMvcEvent()->getViewModel();
        
                    //pass global vars to view
                    $viewModel->role = $session->role;

                    $viewModel->controller = $controller;
                    $viewModel->action = $action;

                    // START WIZARD
                    // check for user wizzard
                    // get user wizard to check if it's finished or not
                    $wizardTable = $sm->get('Application\Model\WizardTable');
                    $wizard = $wizardTable->getWizard($session->role->membership->id_membership, $session->role->role->id, $locale->id);
                    $isComplete = $wizardTable->isWizardCompleted($auth->getIdentity()->id, $wizard->id);
                    $viewModel->wizard = $wizard ?: null;
                    $viewModel->wizard_complete = $isComplete;
                    // END WIZARD
                    
                    // START SUBSCRIPTIONS AND USERS VALIDATIONS  
                    // get current subscription
                    $organizationTable = $sm->get('Application\Model\OrganizationTable');
                    $currentSubscription = $organizationTable->fetchCurrentSubscription($auth->getIdentity()->id_organization);
                    
                    $viewModel->subscription = $currentSubscription;
                    $viewModel->trial_expired = $currentSubscription->isTrialExpired();
                    
                    if ($viewModel->trial_expired && 
                        (!in_array($controller, array('organization','auth','index'))) &&
                        (!isset($ajaxControllerRequests[$controller]) || !in_array($action, $ajaxControllerRequests[$controller])))
                    {
                        // Redirect to the user login page
                        $router   = $e->getRouter();
                        $url      = $router->assemble(array(), array(
                            'name' => $session->role->hasPermission('organization_manage-subsctiption') ? 'plan_details' : 'home',
                        ));
                        
                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine('Location', $url);
                        $response->setStatusCode(302);
                        
                        return $response;                        
                    }
                    
                    // fetch subscription with last end date to know the remaining days for the longest subscription
                    $filter = array('organization'=>$auth->getIdentity()->id_organization,'active'=>'1');
                    $order = array('last_end_date'=>'DESC');
                    $viewModel->subscription_last_end_date = $organizationTable->fetchSubscription($filter, $order);
                    $viewModel->subscription_expired = !$currentSubscription->active && (strtotime(date('Y-m-d')) >= strtotime($currentSubscription->end_date));
                    
                    if (!$viewModel->trial_expired &&
                        $viewModel->subscription_expired &&
                        (!in_array($controller, array('organization','auth','index'))) &&
                        (!isset($ajaxControllerRequests[$controller]) || !in_array($action, $ajaxControllerRequests[$controller])))
                    {
                        // Redirect to the user login page
                        $router   = $e->getRouter();
                        $url      = $router->assemble(array(), array(
                            'name' => $session->role->hasPermission('organization_manage-subsctiption') ? 'plan_details' : 'home',
                        ));
                    
                        $response = $e->getResponse();
                        $response->getHeaders()->addHeaderLine('Location', $url);
                        $response->setStatusCode(302);
                    
                        return $response;
                    }                        
                    
                    if (!$viewModel->trial_expired && 
                        !$viewModel->subscription_expired &&
                        (!isset($ajaxControllerRequests[$controller]) || !in_array($action, $ajaxControllerRequests[$controller])))
                    {
                        // fetch subscription with max users to validate functionality
                        $totalAllowedUsers = $organizationTable->countMaxAllowedUsers(array('organization'=>$auth->getIdentity()->id_organization,'active'=>'1'));
                        // validate total active users against max users allowed
                        $totalActiveUsers = $userTable->countAll(array('organization'=>$auth->getIdentity()->id_organization,'active'=>'1'));
                        
                        $viewModel->total_allowed_users = $totalAllowedUsers;
                        $viewModel->total_active_users = $totalActiveUsers;
                        $viewModel->remove_users_needed = $totalActiveUsers > $totalAllowedUsers;
                        
                        if ($viewModel->remove_users_needed && 
                            (!in_array($controller, array('user','organization','auth','index'))) &&
                            (!in_array($action, $ajaxControllerRequests[$controller])))
                        {
                            // Redirect to the user login page
                            $router   = $e->getRouter();
                            $url      = $router->assemble(array(), array(
                                'name' => $session->role->hasPermission('organization_manage-subsctiption') ? 'plan_details' : 'home',
                            ));
                        
                            $response = $e->getResponse();
                            $response->getHeaders()->addHeaderLine('Location', $url);
                            $response->setStatusCode(302);
                        
                            return $response;
                        }
                        // end total users validation                        
                    }
                    // END SUBSCRIPTIONS AND USERS VALIDATIONS

                    return;
                }
            }
            
            // Redirect to the user login page
            $router   = $e->getRouter();
            $url      = $router->assemble(array(), array(
                'name' => 'login'
            ));
        
            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
        
            return $response;
        }, -100);
        
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    public function getAutoloaderConfig()
    {
        return array(
                'Zend\Loader\StandardAutoloader' => array(
                        'namespaces' => array(
                                __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                        ),
                ),
        );
    }

    public function getServiceConfig()
    {
        
        return array(
                'factories' => array(
                        'Application\Model\UserTable' =>  function($sm) {
                            $tableGateway = $sm->get('UserTableGateway');
                            $table = new UserTable($tableGateway);
                            $table->setServiceLocator($sm);
                            return $table;
                        },
                        'UserTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new User());
                            return new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
                        },   
                        'Application\Model\QuestionGroupTable' =>  function($sm) {
                            $tableGateway = $sm->get('QuestionGroupTableGateway');
                            $table = new QuestionGroupTable($tableGateway);
                            return $table;
                        },
                        'QuestionGroupTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new QuestionGroup());
                            return new TableGateway('questions_groups', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\QuestionTable' =>  function($sm) {
                            $tableGateway = $sm->get('QuestionTableGateway');
                            $table = new QuestionTable($tableGateway);
                            return $table;
                        },
                        'QuestionTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Question());
                            return new TableGateway('questions', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\RoleTable' =>  function($sm) {
                            $tableGateway = $sm->get('RoleTableGateway');
                            $table = new RoleTable($tableGateway);
                            return $table;
                        },                        
                        'RoleTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Role());
                            return new TableGateway('roles', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\PermissionTable' =>  function($sm) {
                            $tableGateway = $sm->get('PermissionTableGateway');
                            $table = new PermissionTable($tableGateway);
                            return $table;
                        },                        
                        'PermissionTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Permission());
                            return new TableGateway('permissions', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\LanguageTable' =>  function($sm) {
                            $tableGateway = $sm->get('LanguageTableGateway');
                            $table = new LanguageTable($tableGateway);
                            return $table;
                        },
                        'LanguageTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Language());
                            return new TableGateway('languages', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\ProjectTable' =>  function($sm) {
                            $tableGateway = $sm->get('ProjectTableGateway');
                            $table = new ProjectTable($tableGateway);
                            $table->setServiceLocator($sm);
                            return $table;
                        },
                        'ProjectTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Project());
                            return new TableGateway('projects', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\ChannelTable' =>  function($sm) {
                            $tableGateway = $sm->get('ChannelTableGateway');
                            $table = new ChannelTable($tableGateway);
                            return $table;
                        },
                        'ChannelTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Channel());
                            return new TableGateway('channels', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\FormTable' =>  function($sm) {
                            $tableGateway = $sm->get('FormTableGateway');
                            $table = new FormTable($tableGateway);
                            return $table;
                        },
                        'FormTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Form());
                            return new TableGateway('forms', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\ListeningTable' =>  function($sm) {
                            $tableGateway = $sm->get('ListeningTableGateway');
                            $table = new ListeningTable($tableGateway);
                            return $table;
                        },
                        'ListeningTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Listening());
                            return new TableGateway('listenings', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\ReportTable' =>  function($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $table = new ReportTable($dbAdapter);
                            return $table;
                        },
                        'Application\Model\OrganizationTable' =>  function($sm) {
                            $tableGateway = $sm->get('OrganizationTableGateway');
                            $table = new OrganizationTable($tableGateway);
                            $table->setServiceLocator($sm);
                            return $table;
                        },
                        'OrganizationTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Organization());
                            return new TableGateway('organizations', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\ProjectRoleTable' =>  function($sm) {
                            $tableGateway = $sm->get('ProjectRoleTableGateway');
                            $table = new ProjectRoleTable($tableGateway);
                            return $table;
                        },
                        'ProjectRoleTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new ProjectRole());
                            return new TableGateway('projects_roles', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\SubjectTable' =>  function($sm) {
                            $tableGateway = $sm->get('SubjectTableGateway');
                            $table = new SubjectTable($tableGateway);
                            return $table;
                        },
                        'SubjectTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new ProjectRole());
                            return new TableGateway('subjects', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\DashboardReportTable' =>  function($sm) {
                            $tableGateway = $sm->get('DashboardReportTableGateway');
                            $table = new DashboardReportTable($tableGateway);
                            $table->setServiceLocator($sm);
                            return $table;
                        },
                        'DashboardReportTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new DashboardReport());
                            return new TableGateway('dashboard_reports', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\MembershipTable' =>  function($sm) {
                            $tableGateway = $sm->get('MembershipTableGateway');
                            $table = new MembershipTable($tableGateway);
                            $table->setServiceLocator($sm);
                            return $table;
                        },
                        'MembershipTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Membership());
                            return new TableGateway('memberships', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\LocaleTable' =>  function($sm) {
                            $tableGateway = $sm->get('LocaleTableGateway');
                            $table = new LocaleTable($tableGateway);
                            return $table;
                        },
                        'LocaleTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Locale());
                            return new TableGateway('locales', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\WizardTable' =>  function($sm) {
                            $tableGateway = $sm->get('WizardTableGateway');
                            $table = new WizardTable($tableGateway);
                            return $table;
                        },
                        'WizardTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Wizard());
                            return new TableGateway('wizards', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\CountryTable' =>  function($sm) {
                            $tableGateway = $sm->get('CountryTableGateway');
                            $table = new CountryTable($tableGateway);
                            return $table;
                        },
                        'CountryTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new Country());
                            return new TableGateway('countries', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Application\Model\StateTable' =>  function($sm) {
                            $tableGateway = $sm->get('StateTableGateway');
                            $table = new StateTable($tableGateway);
                            return $table;
                        },
                        'StateTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new State());
                            return new TableGateway('states', $dbAdapter, null, $resultSetPrototype);
                        },
                        'Basic\Form\ListeningForm' => function ($sm) {
                            $form = new ListeningForm($sm);
                            return $form;
                        }
                ),
        );
    }
	/* (non-PHPdoc)
     * @see \Zend\ModuleManager\Feature\FormElementProviderInterface::getFormElementConfig()
     */
//     public function getFormElementConfig()
//     {
//         // TODO Auto-generated method stub
//         return array(
//             'factories' => array(
//                 'ProjectChannelFieldset' => function($sm) {
//                     $serviceLocator = $sm->getServiceLocator();
//                     $formTable = $serviceLocator->get('Application\Model\FormTable');                    
//                     $fieldset = new ProjectChannelFieldset($formTable);
//                     return $fieldset;
//                 },
//             )
//         );
//     }
}
