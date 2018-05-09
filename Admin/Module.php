<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin;

use Admin\Model\User;
use Admin\Model\UserTable;
use Admin\Model\Role;
use Admin\Model\RoleTable;
use Admin\Model\Permission;
use Admin\Model\PermissionTable;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Router\Http\RouteMatch;

use Zend\View\Model\ViewModel;

use Zend\Session\Config\SessionConfig;
use Zend\Session\Container;
use Zend\Session\SessionManager;

class Module
{
    protected $whitelist = array('login');
    
    public function onBootstrap(MvcEvent $e)
    {
        
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $app = $e->getApplication();
        $sm  = $app->getServiceManager();

        $list = $this->whitelist;
        $auth = new AuthenticationService();
        
        $eventManager->attach(MvcEvent::EVENT_ROUTE, function($e) use ($list, $auth) {
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
                        
            // User is authenticated
            if ($auth->hasIdentity()) {
                
                // Validate if user has permission to access or not
                $controller = $match->getParam('controller');
                $controller = strtolower(end(explode('\\',$controller)));
                $action     = $match->getParam('action');
                
                $session = new Container('role');
                
                if ($session->role && $session->role->hasPermission($controller.'_'.$action)
                || ($controller == 'auth' && $action=='logout')
                || ($controller == 'index')
                ){
                
                    $viewModel = $e->getApplication()->getMvcEvent()->getViewModel();
                    $viewModel->role = $session->role;
                    
                    return;
                }
            }
            
            // Redirect to the user login page, as an example
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
                        'Admin\Model\UserTable' =>  function($sm) {
                            $tableGateway = $sm->get('UserTableGateway');
                            $table = new UserTable($tableGateway);
                            return $table;
                        },

                        'UserTableGateway' => function ($sm) {
                            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                            $resultSetPrototype = new ResultSet();
                            $resultSetPrototype->setArrayObjectPrototype(new User());
                            return new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
                        },
                        
                        'Admin\Model\RoleTable' =>  function($sm) {
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
                        'Admin\Model\PermissionTable' =>  function($sm) {
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
                ),
        );
    }
}
