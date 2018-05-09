<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

use Zend\Authentication\AuthenticationService;

use Zend\Authentication\Result;

use Admin\Form\LoginForm;
use Admin\Filter\LoginFilter;

use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;

use Admin\Model\Permission;
use Admin\Model\PermissionTable;

use Zend\Session\Container;
use Zend\Session\SessionManager;
use Zend\Cache\Pattern\OutputCache;

class AuthController extends AbstractActionController
{
    
    /**
     * 
     * @var Zend\Authentication\AuthenticationService
     */
    private $auth;
    
    public function __construct() {
        $this->auth = new AuthenticationService();
    }
    
    public function indexAction()
    {

        if ($this->auth->hasIdentity())
            return $this->redirect()->toRoute('home');


        $form = new LoginForm();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            $data = $request->getPost();
            $form->setData($data);
            $form->setInputFilter(new LoginFilter());
            
            if ($form->isValid()) {
                $validatedData = $form->getData();
                
                // Create a SQLite database connection
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                
                // Configure the instance with constructor parameters...
                $authAdapter = new AuthAdapter($dbAdapter,'users','email','password','MD5(?)');
                $authAdapter->setIdentity($data['email'])->setCredential($data['password']);
                $result = $authAdapter->authenticate();
                
                switch ($result->getCode()) {
                
                	case Result::FAILURE_IDENTITY_NOT_FOUND:
                	    /** do stuff for nonexistent identity **/
                	    $messages = 'nonexistent identity';
                	    break;
                
                	case Result::FAILURE_CREDENTIAL_INVALID:
                	    /** do stuff for invalid credential **/
                	    $messages = 'invalid credential';
                	    break;
                
                	case Result::SUCCESS:
                	    /** do stuff for successful authentication **/
                	    $this->auth->setAdapter($authAdapter);
                	    $storage = $this->auth->getStorage();
                	    // store the identity as an object where the password column has
                	    // been omitted
                	    $storage->write($authAdapter->getResultRowObject(null,'password'));
                	    
                	    $rbac = new Rbac();
                	    $role  = new Role($this->auth->getIdentity()->id_role);
                	    
                	    // obtener todos los permisos del role
                	    $permissionsTable = $this->getServiceLocator()->get('Admin\Model\PermissionTable');
                	    
                	    $permissions = $permissionsTable->fetchByRole($this->auth->getIdentity()->id_role);
                	                    	    
                	    foreach($permissions as $permission){

                	        $role->addPermission($permission->key);
                	    
                	    }
                	    
                	    $session = new Container('role');
                	    $session->role = $role;
                	    $session->role->usuario = $this->auth->getIdentity()->id;

                	    return $this->redirect()->toRoute('home');

                	    break;
                
                	default:
                	    /** do stuff for other failure **/
                	    $messages = 'unknown failure';
                	    break;
                }
                
            } /*else {
                $messages = $form->getMessages();
            }*/
            
        }
        
        $viewModel = new ViewModel(array('form'=>$form,'messages'=>$messages));
        $viewModel->setTerminal(true);
        
        return $viewModel;
    }
    
    public function logoutAction()
    {
        $this->auth->clearIdentity();
        
        return $this->redirect()->toRoute('login');
    }
}
