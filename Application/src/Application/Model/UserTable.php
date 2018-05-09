<?php
namespace Application\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Sql\Expression;
// use Zend\Mail\Message;
// use Zend\Mime\Message as MimeMessage;
// use Zend\Mime\Part as MimePart;
use Zend\View\Helper\ServerUrl;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mail\Transport\Smtp;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Mail\Transport\Sendmail;

class UserTable
{
    /**
     * 
     * @var \Zend\Db\TableGateway\TableGateway
     */
    protected $tableGateway;
    
    /**
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;
    
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
    
    public function getDbAdapter()
    {
        return $this->tableGateway->getAdapter();
    }

    /**
     * 
     * @param \Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll($paginated=false, $filter = array())
    {
        $where = array();
        
        if (isset($filter['keyword']))
            $where[] = '(u.name LIKE \'%'.$filter['keyword'].'%\' OR u.username LIKE \'%'.$filter['keyword'].'%\' OR u.email LIKE \'%'.$filter['keyword'].'%\')';
        
        if (isset($filter['email']))
            $where[] = '(u.email = \''.$filter['email'].'\')';
        
        if (isset($filter['token']))
            $where[] = '(u.token = \''.$filter['token'].'\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(u.active = \''.$filter['active'].'\' OR u.active = \'\')';
            else
                $where[] = 'u.active = \''.$filter['active'].'\'';
        }
        
        if (isset($filter['organization']))
            $where[] = '(u.id_organization = \''.$filter['organization'].'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
                        ->from(array('u' => 'users'))
                        ->columns(array('*'))
                        ->join(array('r' => 'roles'), 'r.id = u.id_role', array('role' => 'name'))
                        ->join(array('o'=>'organizations'), 'u.id_organization=o.id', array('organization'=>'name'))
                        ->where(!empty($where) ? implode(' AND ', $where) : 1)
                        ->order('u.name ASC');
        
        if ($paginated) {
            // create a new result set based on the entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new User());
            
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
                ->join(array('r' => 'roles'), 'u.id_role = r.id', array('role'=>'name'))
                ->join(array('ul' => 'users_languages'), 'u.id = ul.id_user', array('id_language' => new Expression('GROUP_CONCAT(ul.id_language SEPARATOR \', \')')), 'left')
                ->join(array('l' => 'languages'), 'ul.id_language = l.id', array('language' => new Expression('GROUP_CONCAT(l.name SEPARATOR \', \')')), 'left')
                ->join(array('o'=>'organizations'), 'u.id_organization=o.id', array('organization'=>'name'))
                ->where(array('u.id'=>$id))
                ->group(array('u.id'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $role = new Role();
        $role->name = $row->role;
        $row->role = $role;
        
        $row->id_language = array_map('trim', explode(',', $row->id_language));
        
        $languages = array_map('trim', explode(',', $row->language));
        
        $row->languages = array();
        foreach ($languages as $key=>$item)
        {
            $language = new Language();
            $language->exchangeArray(array('id'=>$row->id_language[$key], 'name'=>$item));
            array_push($row->languages, $language);
        }
        
        $user = new User();
        $user->exchangeArray($row);
        
        return $user;
    }
    
    public function getUserByToken($token=null, $tokenPassword=null)
    {
        if (!is_null($token))
            $where = array('u.token'=>$token);
        
        if (!is_null($tokenPassword))
            $where = array('u.token_password'=>$tokenPassword);
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()->from(array('u' => 'users'))->where($where);
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            throw new \Exception("Could not find row");
        }
    
        $user = new User();
        $user->exchangeArray($row);
    
        return $user;
    }

    public function saveUser(User $entity, UserProject $userProject = NULL)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            $data = array(
                'name' => ucwords($entity->name),
                'email' => $entity->email,
                'id_organization' => $auth->getIdentity()->id_organization,
            );
            
            if ($entity->id_role)
                $data['id_role']=$entity->id_role;
            
            if ($entity->id_locale)
                $data['id_locale']=$entity->id_locale;
            
            $id = (int) $entity->id;
            if ($id == 0) {
                
                $token = md5(uniqid(mt_rand(), true));
                
                $data['created'] = date("Y-m-d H:i:s");
                $data['created_by'] = $auth->getIdentity()->id;
                $data['token'] = $token;
                                
                $this->tableGateway->insert($data);
                $entity->id = $this->tableGateway->lastInsertValue;
                
                // insert user project
                $userProject->id_user = $entity->id;
                $this->saveUserProject($userProject, $connection);
                
                // increase total users for current subscription if it's a trial & the organization has more users than the default min users of the membership
                $organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
                $currentSubscription = $organizationTable->fetchCurrentSubscription($auth->getIdentity()->id_organization);
                $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
                $currentMembership = $membershipTable->getById($currentSubscription->id_membership);
                $totalUsers = $this->countAll(array('organization'=>$auth->getIdentity()->id_organization,'active'=>'1'));
                
                if ($currentSubscription->in_trial && (int)$totalUsers >= (int)$currentMembership->min_users)
                {
                    $organizationTable->increaseSubscriptionUsers($currentSubscription);
                }
                
                // send user account creation email
                $config = $this->getServiceLocator()->get('config');
                
                $helper = new ServerUrl();
                $url = $helper->__invoke(false);
                
                $view = new \Zend\View\Renderer\PhpRenderer();
                $resolver = new \Zend\View\Resolver\TemplateMapResolver();
                $resolver->setMap(array(
                    'mailTemplate' => __DIR__ . '/../../../view/mails/user-creation.phtml'
                ));
                $view->setResolver($resolver);
                
                $viewModel = new ViewModel();
                $viewModel->setTemplate('mailTemplate')->setVariables(array(
                    'user'  => $entity,
                    'reseturl'   => $url."/auth/confirm-token?tkn=".$token,
                    'serviceLocator' => $this->getServiceLocator()
                ));
                
                $bodyPart = new \Zend\Mime\Message();
                $bodyMessage = new \Zend\Mime\Part($view->render($viewModel));
                $bodyMessage->type = "text/html";
                $bodyPart->setParts(array($bodyMessage));
                
                $message = new \Zend\Mail\Message();
                
                $message->addFrom($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
                ->setSender($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
                ->addReplyTo($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
                ->addTo($entity->email)
                ->setSubject($this->getTranslator()->translate("Quick Audits welcomes you aboard!"))
                ->setEncoding('UTF-8')
                ->setBody($bodyPart);
                
                if ($config['smtp_options']['host']!='localhost')
                {
                    $smtpOptions = new \Zend\Mail\Transport\SmtpOptions();
                    $smtpOptions->setHost($config['smtp_options']['host'])
                    ->setConnectionClass('login')
                    ->setName($config['smtp_options']['host'])
                    ->setConnectionConfig(array(
                        'username' => $config['smtp_options']['username'],
                        'password' => $config['smtp_options']['password'],
                        'ssl' => $config['smtp_options']['ssl'],))
                        ->setPort($config['smtp_options']['port']);
                    
                    $transport = new Smtp($smtpOptions);
                    $transport->send($message);
                }
                else
                {
                    $transport = new Sendmail();
                    $transport->send($message);                
                }
                
            } else {
                
                if ($this->getUser($id)) {
                    
                    $this->tableGateway->update($data, array('id' => $id));
                    
                    // update current locale
                    if (isset($data['id_locale']))
                    {
                        $localeTable = new LocaleTable($this->tableGateway);
                        $session = new Container('role');
                        $session->role->locale = $localeTable->getById($data['id_locale']);
                    }
                    
                } else {
                    throw new \Exception('User id does not exist');
                }
            }

            if (!empty($entity->id_language))
            {
                // save user languages
                $userLanguagesTable = new TableGateway('users_languages', $dbAdapter);
                
                $userLanguagesTable->delete(array('id_user'=>$entity->id));
                
                foreach($entity->id_language as $language){
                    $userLanguagesTable->insert(array('id_user'=>$entity->id,'id_language'=>$language));
                }    
            }
            
            $connection->commit();
            
        } catch (\Exception $e) {
            $connection->rollback();
            
            throw $e;
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
    
    /**
     * Confirm user token and if password is passed also save new user password
     * @param int $id
     * @param string $password
     * @throws Exception
     */
    public function confirmUserToken($id, $password=NULL)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
        
            $connection->beginTransaction();
            
            $user = new UserTable($this->tableGateway);
            
            $user = $user->getUser($id);
            
            $data = array(
                'token_confirm' => '1',
            );
            
            if (!is_null($password))
                $data['password'] = md5($password);
            
            $this->tableGateway->update($data, array('id' => $id));
            
            $connection->commit();
        } 
        catch (\Exception $e)
        {
            $connection->rollback();
            
            throw $e;
        }
    }
    
    /**
     * Change users password
     * @param int $id
     * @param string $password
     * @throws Exception
     */
    public function changePassword($id, $password)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        $connection = $dbAdapter->getDriver()->getConnection();
    
        try {
    
            $connection->beginTransaction();
    
            $user = new UserTable($this->tableGateway);
    
            $data = array('password' => md5($password));
    
            $this->tableGateway->update($data, array('id' => $id));
    
            $connection->commit();
        }
        catch (\Exception $e)
        {
            $connection->rollback();
    
            throw $e;
        }
    }

    /**
     * 
     * @param string $idProject
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, \Zend\Db\ResultSet\HydratingResultSet>
     */
    public function fetchAllProjectAgents($idProject = NULL)
    {
        $auth = new AuthenticationService();
        
        $where = array();
        
        array_push($where, '(u.`active`=\'1\')');
        array_push($where, '(pr.`key`=\'operator\')');
        array_push($where, "(u.id_organization='".$auth->getIdentity()->id_organization."')");
        
        if (!is_null($idProject))
            array_push($where, '(up.id_project=\''.$idProject.'\' AND up.active=\'1\')');
        
        $projectTable = new ProjectTable($this->tableGateway);
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
                        ->from(array('u' => 'users'))
                        ->join(array('up' => 'users_projects'), 'u.id=up.id_user', array())
                        ->join(array('pr' => 'projects_roles'), 'up.id_project_role=pr.id', array())
                        ->join(array('p' => $projectTable->getCurrentUserProjectsSelect()), 'up.id_project=p.id', array())
                        ->where(implode(' AND ', $where))
                        ->group('u.id')
                        ->order('u.name ASC');
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
    
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new User());
        $results = $resultSetPrototype->initialize($results);
    
        return $results;
    }
    
    public function fetchAllProjectAuditors($idProject = NULL, $idOrganization=NULL)
    {
        $where = array();
    
        array_push($where, '(pr.`key`=\'auditor\' OR pr.`key`=\'manager\')');
    
        if (!is_null($idProject))
            array_push($where, '(up.id_project=\''.$idProject.'\' AND up.active=\'1\')');
    
        if (!is_null($idOrganization))
            array_push($where, '(u.id_organization=\''.$idOrganization.'\')');
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('u' => 'users'))
        ->join(array('up' => 'users_projects'), 'u.id=up.id_user', array())
        ->join(array('pr' => 'projects_roles'), 'up.id_project_role=pr.id', array())
        ->where(implode(' AND ', $where))
        ->group('u.id')
        ->order('u.name ASC');
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
    
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new User());
        $results = $resultSetPrototype->initialize($results);
    
        return $results;
    }
    
    public function fetchAllUserProjects($id)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from(array('up' => 'users_projects'))
        ->columns(array('id_user','id_project','id_project_role','active','blocked'))
        ->join(array('u'=>'users'), 'up.id_user=u.id', array('user'=>'name'))
        ->join(array('p'=>'projects'), 'up.id_project=p.id', array('project'=>'name'))
        ->join(array('pr'=>'projects_roles'), 'up.id_project_role=pr.id', array('project_role'=>'name'))
        ->where('up.id_user='.$id)
        ->order(array('p.name ASC','pr.name ASC'));
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new UserProject());
        $results = $resultSetPrototype->initialize($results);
        
        return $results;
    }
    
    public function saveUserProject(UserProject $entity, $existingConnection=NULL)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        if (is_null($existingConnection))
            $connection = $dbAdapter->getDriver()->getConnection();
    
        try {
    
            if (is_null($existingConnection))
                $connection->beginTransaction();
            
            $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
            $select = $sql->select()
            ->from('users_projects')
            ->columns(array('total'=>new Expression('COUNT(*)')))
            ->where('id_user=\''.$entity->id_user.'\' AND id_project=\''.$entity->id_project.'\' AND id_project_role=\''.$entity->id_project_role.'\'');
            
            $selectString = $sql->getSqlStringForSqlObject($select);
            $results = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
            
            if ($results->current()->total > 0)
                throw new \Exception($this->getTranslator()->translate('Role is already assigned for current project'));
            
            $data = array(
                'id_user' => $entity->id_user,
                'id_project' => $entity->id_project,
                'id_project_role' => $entity->id_project_role,
                'active' => '1',
                'blocked' => '0'
            );
    
            $usersProjectsTable = new TableGateway('users_projects', $dbAdapter);
            
            $usersProjectsTable->insert($data);
            
            if (is_null($existingConnection))
                $connection->commit();
    
        } catch (\Exception $e) {
            if (is_null($existingConnection))
                $connection->rollback();
    
            throw $e;
        }
    }
    
    /**
     * 
     * @param int $idUser
     * @param int $idProject
     * @param int $idProjectRole
     * @throws \Exception
     * @return UserProject
     */
    public function getUserProject($idUser,$idProject,$idProjectRole)
    {
        $idUser  = (int) $idUser;
        $idProject = (int) $idProject;
        $idProjectRole  = (int) $idProjectRole;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
                    ->from(array('up' => 'users_projects'))
                    ->columns(array('id_user','id_project','id_project_role','active','blocked'))
                    ->join(array('u'=>'users'), 'up.id_user=u.id', array('user'=>'name'))
                    ->join(array('p'=>'projects'), 'up.id_project=p.id', array('project'=>'name'))
                    ->join(array('pr'=>'projects_roles'), 'up.id_project_role=pr.id', array('project_role'=>'name'))
                    ->where('up.id_user=\''.$idUser.'\' AND up.id_project=\''.$idProject.'\' AND up.id_project_role=\''.$idProjectRole.'\'');
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new UserProject());
        $results = $resultSetPrototype->initialize($results);
        
        if (!$results->current())
            throw new \Exception('Could not find requested row');
        
        return $results->current();
    }
    
    public function changeStatusUserProject($idUser,$idProject,$idProjectRole)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $userProject = $this->getUserProject($idUser, $idProject, $idProjectRole);

        $data = array(
            'active' => $userProject->active ? '0' : '1',
        );
        
        $usersProjectsTable = new TableGateway('users_projects', $dbAdapter);
        
        $usersProjectsTable->update($data, 'id_user=\''.$idUser.'\' AND id_project=\''.$idProject.'\' AND id_project_role=\''.$idProjectRole.'\'');
    }
    
    public function deleteUserProject($idUser,$idProject,$idProjectRole)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        $usersProjectsTable = new TableGateway('users_projects', $dbAdapter);
    
        $usersProjectsTable->delete('id_user=\''.$idUser.'\' AND id_project=\''.$idProject.'\' AND id_project_role=\''.$idProjectRole.'\'');
    }
    
    public function resetPassword($email)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
    
        $connection = $dbAdapter->getDriver()->getConnection();
    
        try {
    
            $connection->beginTransaction();
    
            $token = md5(uniqid(mt_rand(), true));
            
            $data = array('token_password' => $token);
    
            $this->tableGateway->update($data, array('email' => $email));
            
            // get user details
            $user = $this->fetchAll(false, array('email'=>$email))->current();
               
            // send user account creation email
            $config = $this->getServiceLocator()->get('config');
            
            $helper = new ServerUrl();
            $url = $helper->__invoke(false);
            
            $view = new \Zend\View\Renderer\PhpRenderer();
            $resolver = new \Zend\View\Resolver\TemplateMapResolver();
            $resolver->setMap(array(
                'mailTemplate' => __DIR__ . '/../../../view/mails/reset-password.phtml'
            ));
            $view->setResolver($resolver);
            
            $viewModel = new ViewModel();
            $viewModel->setTemplate('mailTemplate')->setVariables(array(
                'user'  => $user,
                'reseturl'   => $url."/auth/reset-password?tkn=".$token,
            ));
            
            $bodyPart = new \Zend\Mime\Message();
            $bodyMessage = new \Zend\Mime\Part($view->render($viewModel));
            $bodyMessage->type = "text/html";
            $bodyPart->setParts(array($bodyMessage));
            
            $message = new \Zend\Mail\Message();
            
            $message->addFrom($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
            ->setSender($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
            ->addReplyTo($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
            ->addTo($email)
            ->setSubject("Password recovery")
            ->setEncoding('UTF-8')
            ->setBody($bodyPart);
            
            if ($config['smtp_options']['host']!='localhost')
            {
                $smtpOptions = new \Zend\Mail\Transport\SmtpOptions();
                $smtpOptions->setHost($config['smtp_options']['host'])
                ->setConnectionClass('login')
                ->setName($config['smtp_options']['host'])
                ->setConnectionConfig(array(
                    'username' => $config['smtp_options']['username'],
                    'password' => $config['smtp_options']['password'],
                    'ssl' => $config['smtp_options']['ssl'],))
                    ->setPort($config['smtp_options']['port']);
                
                $transport = new Smtp($smtpOptions);
                $transport->send($message);
            }
            else
            {
                $transport = new Sendmail();
                $transport->send($message);                
            }
    
            $connection->commit();
        }
        catch (\Exception $e)
        {
            $connection->rollback();
    
            throw $e;
        }
    }
    
    public function updateSessionId($id, $sessionId)
    {
        $this->tableGateway->update(array('session_id'=>$sessionId), array('id' => $id));
    }
    
    public function getSessionId($id)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()->from(array('u' => 'users'))->columns(array('session_id'))->where(array('u.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            return null;
        }
        
        return $row->session_id;
    }    

    public function confirmUserPassword($id, $password)
    {
        $id  = (int) $id;
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('u' => 'users'))
        ->columns(array('total'=>new Expression('COUNT(u.id)')))
        ->where(array('u.id'=>$id, 'u.password'=>md5($password)));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        return $row->total > 0;
    }
    
    public function countAll($filter = array())
    {
        $where = array();
    
        if (isset($filter['active']))
            $where[] = 'active = \''.$filter['active'].'\'';
    
        if (isset($filter['organization']))
            $where[] = '(id_organization = \''.$filter['organization'].'\')';
    
        $results = $this->tableGateway->select($where)->count();
    
        return $results;
    }
    
}