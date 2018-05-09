<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Application\Form\LoginForm;
use Application\Filter\LoginFilter;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;
use Zend\Session\Container;
use Application\Form\ConfirmTokenForm;
use Application\Filter\ConfirmTokenFilter;
use Application\Form\RegisterForm;
use Application\Filter\RegisterFilter;
use Application\Model\Organization;
use Application\Model\User;
use Application\Form\RememberPasswordForm;
use Application\Filter\RememberPasswordFilter;
use Application\Form\ConfirmTokenPasswordForm;
use Application\Filter\ConfirmTokenPasswordFilter;
use Zend\Authentication\Storage\Session;
// use SpeckPaypal\Request\CreateRecurringPaymentsProfile;
// use SpeckPaypal\Element\Address;

/**
 * 
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 */
class AuthController extends AbstractActionController
{
    /**
     * 
     * @var \Zend\Authentication\AuthenticationService
     */
    var $auth;
    protected $translator;
    
    public function __construct() {
        $this->auth = new AuthenticationService();
    }
    
    public function getTranslator()
    {
        if (!$this->translator) {
            $sm = $this->getServiceLocator();
            $this->translator = $sm->get('translator');
        }
        return $this->translator;
    }
    
    public function indexAction()
    {
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
        
        if ($this->auth->hasIdentity())
        {
            $sessionId = $userTable->getSessionId($this->auth->getIdentity()->id);
            if (isset($this->auth->getIdentity()->session_id) && $sessionId == $this->auth->getIdentity()->session_id)
                return $this->redirect()->toRoute('home');
        }
        
        $m = $this->params()->fromQuery('m',0);

        $form = new LoginForm();
        $rememberForm = new RememberPasswordForm();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            // Create a SQLite database connection
            $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            
            $data = $request->getPost();
            
            if (isset($data['login']))
            {
                try {
                    $form->setData($data);
                    $form->setInputFilter(new LoginFilter());
                    
                    if ($form->isValid()) {
                        $validatedData = $form->getData();
                    
                        // Configure the instance with constructor parameters...
                        $authAdapter = new AuthAdapter($dbAdapter,'users','email','password','MD5(?) AND active = \'1\' AND token_confirm = \'1\'');
                        $authAdapter->setIdentity($data['email'])->setCredential($data['password']);
                        
                        $authNamespace = new Container(Session::NAMESPACE_DEFAULT);
                        $authNamespace->getManager()->rememberMe(86400);
                        
                        $result = $authAdapter->authenticate();
                    
                        switch ($result->getCode()) {
                    
                            case Result::FAILURE_IDENTITY_NOT_FOUND:
                                /** do stuff for nonexistent identity **/
                                $messages = $this->getTranslator()->translate('nonexistent identity');
                                break;
                    
                            case Result::FAILURE_CREDENTIAL_INVALID:
                                /** do stuff for invalid credential **/
                                $messages = $this->getTranslator()->translate('invalid credential');
                                break;
                    
                            case Result::SUCCESS:
                                
                                try {
                                    
                                    /** do stuff for successful authentication **/
                                    $this->auth->setAdapter($authAdapter);
                                    $storage = $this->auth->getStorage();
                                    // store the identity as an object where the password column has
                                    // been omitted
                                    $storage->write($authAdapter->getResultRowObject(null,'password'));
                                    
                                    $rbac = new Rbac();
                                    $role  = new Role($this->auth->getIdentity()->id_role);
                                     
                                    // obtener todos los permisos del role
                                    $permissionsTable = $this->getServiceLocator()->get('Application\Model\PermissionTable');
                                     
                                    $permissions = $permissionsTable->fetchByRole($this->auth->getIdentity()->id_role);
                                    
                                    foreach($permissions as $permission){
                                        $role->addPermission($permission->key);
                                    }
                                     
                                    $roleTable = $this->getServiceLocator()->get('Application\Model\RoleTable');
                                    $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
                                    $localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');
                                    
                                    $session = new Container('role');
                                    
                                    $session->role = $role;
                                    $session->role->user = $this->auth->getIdentity();
                                     
                                    $session->role->role = $roleTable->getById($this->auth->getIdentity()->id_role);
                                    $session->role->membership = $membershipTable->fetchOrganizationMembership($this->auth->getIdentity()->id_organization);
                                    $session->role->locale = $this->auth->getIdentity()->id_locale ? $localeTable->getById($this->auth->getIdentity()->id_locale) : null;
                                    
                                    // store user session_id in database
                                    $sessionId = session_id();
                                    $userTable->updateSessionId($this->auth->getIdentity()->id, $sessionId);
                                    
                                    $this->auth->getIdentity()->session_id = $sessionId;
                                    
                                    return $this->redirect()->toRoute('home');
                                    
                                    break;
                                    
                                } catch (\Exception $e) {
                                    
                                    $this->logout();
                                    
                                }                    
                                die;
                            default:
                                /** do stuff for other failure **/
                                $messages = $this->getTranslator()->translate('unknown failure');
                                break;
                        }
                    }
                } catch (\Exception $e) {
                    $this->auth->clearIdentity();
                    $messages = $this->getTranslator()->translate('An unknown error has occurred. Please verify your data and try again.');
                }                
            }  
            elseif (isset($data['remember']))
            {
                $rememberForm->setData($data);
                $rememberForm->setInputFilter(new RememberPasswordFilter($dbAdapter));
            
                if ($rememberForm->isValid()) {
                    $validatedData = $rememberForm->getData();
                    
                    try {

                        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                        $userTable->resetPassword($validatedData['email']);
                        
                        // reset form values
                        $rememberForm->get('email')->setValue('');
                        
                        $rememberMessages= $this->getTranslator()->translate("Please verify your email to get the new password");
                        
                    } catch (\Exception $e) {
                        $rememberError=$this->getTranslator()->translate('An unknown error has occurred. Please verify your data and try again.');
                    }                    
                }
            }
        }
        
        $viewModel = new ViewModel(
            array(
                'form'=>$form,
                'rememberForm'=>$rememberForm,
                'messages'=>$messages,
                'rememberMessages'=>$rememberMessages,
                'rememberError'=>$rememberError,
                'm'=>$m,
            )
        );
        
        $this->layout('layout/layout-auth');
        
        return $viewModel;
    }
    
    public function registerAction()
    {
        $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
        
        $form = new RegisterForm($membershipTable);
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            try {
                $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                
                $form->setInputFilter(new RegisterFilter($dbAdapter));
                $form->setData($request->getPost());
                if ($form->isValid()) {
                
                    $organization = new Organization();
                    $organization->exchangeArray($form->getData());
                
                    $userData = $form->getData();
                    $userData['name'] = sprintf('%s %s', $userData['firstname'], $userData['lastname']);
                
                    $user = new User();
                    $user->exchangeArray($userData);
                
                    $idMembership = $userData['id_membership'];
                    
                    $organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
                    $organizationTable->register($organization, $user, $idMembership);

                    return $this->redirect()->toRoute('register-success');
                }
                    
            } catch (\Exception $e) {
                $error = $e->getMessage();                
            }
        }

        
        $viewModel = new ViewModel(array('form'=>$form,'error'=>$error));
        
        $this->layout('layout/layout-auth');
        
        return $viewModel;
    }
    
    public function registerSuccessAction()
    {
        $this->layout('layout/layout-auth');
        
        return new ViewModel();
    }
    
    public function logout()
    {
        $this->auth->getStorage()->clear();
        $this->auth->clearIdentity();
    }
    
    public function logoutAction()
    {
        $this->logout();
        
        return $this->redirect()->toRoute('login');
    }
    
    public function confirmTokenAction()
    {
        if ($this->auth->hasIdentity())
            $this->logout();

        // validate the user receive a token to change password
        $token = $this->params()->fromQuery('tkn');
        
        if (!$token) {
            return $this->redirect()->toRoute('login');
        }
        
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                
        try {
            // validate user exists            
            $user = $userTable->getUserByToken($token);
            
            if ($user->tokenConfirm)
                throw new \Exception('');
            
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('login');
        }

        $form = new ConfirmTokenForm();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        
            try {
                $data = $request->getPost();
                $form->setData($data);
                $form->setInputFilter(new ConfirmTokenFilter());
                
                if ($form->isValid()) {
                    $validatedData = $form->getData();
                
                    $userTable->confirmUserToken($user->id, $validatedData['password']);
                    
                    return $this->redirect()->toRoute('login', array(), array('query' => array('m' => '1')));
                }
                
            } catch (\Exception $e) {
                $messages = $e->getMessage();
            }
        }
        
        $viewModel = new ViewModel(array('form'=>$form, 'messages'=>$messages));
        
        $this->layout('layout/layout-auth');
        
        return $viewModel;
    }
    
    public function resetPasswordAction()
    {
        if ($this->auth->hasIdentity())
            return $this->redirect()->toRoute('home');
    
        // validate the user receive a token to change password
        $token = $this->params()->fromQuery('tkn');
    
        if (!$token) {
            return $this->redirect()->toRoute('login');
        }
    
        $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
    
        try {
            
            // validate user exists
            $user = $userTable->getUserByToken(null, $token);
    
        } catch (\Exception $e) {
            return $this->redirect()->toRoute('login');
        }
    
        $form = new ConfirmTokenPasswordForm();
    
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
                $data = $request->getPost();
                $form->setData($data);
                $form->setInputFilter(new ConfirmTokenPasswordFilter());
    
                if ($form->isValid()) {
                    
                    $validatedData = $form->getData();
    
                    $userTable->changePassword($user->id, $validatedData['password']);
    
                    return $this->redirect()->toRoute('reset-success');
                }
    
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    
        $viewModel = new ViewModel(array('form'=>$form, 'error'=>$error));
    
        $this->layout('layout/layout-auth');
    
        return $viewModel;
    }

    public function resetSuccessAction()
    {
        $this->layout('layout/layout-auth');
    
        return new ViewModel();
    }
}
