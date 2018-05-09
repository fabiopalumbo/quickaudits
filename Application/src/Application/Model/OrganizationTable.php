<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\ResultSet;
use Zend\View\Helper\ServerUrl;
// use Zend\Mail\Message;
// use Zend\Mime\Message as MimeMessage;
// use Zend\Mime\Part as MimePart;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mail\Transport\Smtp;
use Zend\View\Model\ViewModel;
use Application\Helper\PayPal;
use Zend\Session\Container;
use Zend\Mail\Transport\Sendmail;
// use Zend\Db\Sql\Expression;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Db\Sql\Expression;

class OrganizationTable
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

    public function fetchAll($paginated=false, $filter = array(), $order = null)
    {
        $where = array();
        
        if (isset($filter['keyword']))
            $where[] = '(o.name LIKE \'%'.$filter['keyword'].'%\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(o.active = \''.$filter['active'].'\' OR o.active = \'\')';
            else
                $where[] = 'o.active = \''.$filter['active'].'\'';
        }
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
        ->from(array('o' => 'organizations'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order(!is_null($order) ? $order : 'o.name ASC');
        
        if ($paginated) {
            // create a new result set based on the entity
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Organization());
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

    public function getById($id)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from(array('o' => 'organizations'))
        ->join(array('om'=>'organizations_memberships'), 'o.id=om.id_organization', array('id_membership'))
        ->join(array('m'=>'memberships'), 'om.id_membership=m.id', array('membership'=>'name','trial_days'))
        ->where(array('o.id'=>$id,'om.active'=>'1'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $entity = new Organization();
        $entity->exchangeArray($row);
        
        return $entity;
    }
    
    public function save(Organization $entity)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {

            $connection->beginTransaction();
            
            $data = array(
                'name' => $entity->name,
                'firstname' => ucwords($entity->firstname),
                'lastname' => ucwords($entity->lastname),
                'email' => $entity->email,
                'phone' => $entity->phone,
                );
            
            $id = (int) $entity->id;
            
            $auth = new AuthenticationService();
            
            if ($id == 0) {

                $data['created'] = date("Y-m-d H:i:s");
                $data['created_by'] = $auth->getIdentity()->id;
                $data['modified'] = date("Y-m-d H:i:s");
                $data['modified_by'] = $auth->getIdentity()->id;

                $this->tableGateway->insert($data);
                $entity->id = $this->tableGateway->lastInsertValue;
                
            } else {
                if ($this->getById($id)) {
                    $data['modified'] = date("Y-m-d H:i:s");
                    $data['modified_by'] = $auth->getIdentity()->id;
                    
                    $this->tableGateway->update($data, array('id' => $id));
                } else {
                    throw new \Exception('Entity id does not exist');
                }
            }
            
            $connection->commit();
            
        } catch (\Exception $e) {
            $connection->rollback();
            
            throw $e;
        }
    }

    public function delete($id)
    {
        $entity = $this->getById($id);
        $data = array(
        	'active' => !$entity->active,
            );
        $this->tableGateway->update($data, array('id' => $id));
    }
    
    public function register(Organization $organization, User $user, $idMembership)
    {
        $dbAdapter = $this->tableGateway->getAdapter();

        $connection = $dbAdapter->getDriver()->getConnection();

        try {

            $connection->beginTransaction();
            
            $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
            $membership = $membershipTable->getById($idMembership);
		//print_r($membership);

		//echo $membership->package;

            $data = array(
                'name'=>$organization->name,
                'firstname'=>$organization->firstname,
                'lastname'=>$organization->lastname,
                'phone'=>$organization->phone,
                'email'=>$organization->email,
                'created'=>date("Y-m-d H:i:s"),
                'modified'=>date("Y-m-d H:i:s"),
//                 'in_trial'=>$membership->trial_days ? '1' : '0',
                );

            // insert organization
            $this->tableGateway->insert($data);
            $organization->id = $this->tableGateway->lastInsertValue;
            
            // get default role
            $roleTable = new RoleTable($this->tableGateway);
            $role=$roleTable->getDefault();

            // generate user token
            $token = md5(uniqid(mt_rand(), true));
            
            $userData = array(
                'name' => ucwords($user->name),
                'email' => $user->email,
                'id_role' => $role->id,
                'id_organization' => $organization->id,
                'token'=>$token,
                'created'=>date("Y-m-d H:i:s"),
                'modified'=>date("Y-m-d H:i:s")
                );
            
            // insert user
            $userTable = new TableGateway('users', $dbAdapter);
            $userTable->insert($userData);
            $user->id = $userTable->lastInsertValue;
            
            $organizationMembershipTable = new TableGateway('organizations_memberships', $dbAdapter);
            $organizationMembershipTable->insert(array(
                'id_organization'=>$organization->id,
                'id_membership'=>$idMembership,
                'created'=>date("Y-m-d H:i:s"),
                'created_by'=>$user->id,
                ));
            
            $maxUsers = $membership->max_users?:$membership->min_users;
            $next_billing_date = date_create(date('Y-m-d'));
            date_add($next_billing_date,date_interval_create_from_date_string(intval(($membership->trial_days?:30))." days"));
            $next_billing_date = date_format($next_billing_date, 'Y-m-d');
            
            // create new subscription details
            $organizationSubscriptionsTable = new TableGateway('organizations_subscriptions', $dbAdapter);
            $subscription = array(
                'id_organization' => $organization->id,
                'id_membership' => $idMembership,
                'max_users' => $maxUsers, 
                'start_date' => date("Y-m-d H:i:s"),
                'billing_period' => 'month',  
                'unit_price' => $membership->price_month, 
                'total_price' => ($maxUsers * $membership->price_month), 
                'next_billing_date' => $next_billing_date,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $user->id,
                'in_trial'=>$membership->trial_days ? '1' : '0',
                );
            
            $organizationSubscriptionsTable->insert($subscription);
            
            // create organization dummy data
            $this->createDummyData($organization->id, $user->id, $role->id, $membership->package);
            
            // send user account creation email
            $config = $this->getServiceLocator()->get('config');
            
            $helper = new ServerUrl();
            $url = $helper->__invoke(false);
            
            $view = new \Zend\View\Renderer\PhpRenderer();
            $resolver = new \Zend\View\Resolver\TemplateMapResolver();
            $resolver->setMap(array(
                'mailTemplate' => __DIR__ . '/../../../view/mails/account-creation.phtml'
                ));
            $view->setResolver($resolver);
            
            $viewModel = new ViewModel();
            $viewModel->setTemplate('mailTemplate')->setVariables(array(
                'user'  => $user,
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
            ->addTo($user->email)
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
            
            $connection->commit();

        } catch (\Exception $e) {

            $connection->rollback();

            throw $e;
        }
    }
    
    private function createDummyData($idOrganization, $idUser, $idRole, $idMembership)
    {

        try {
            $dbAdapter = $this->tableGateway->getAdapter();


		//echo $idRole;		1-basic 2-pro 3-hotel 4-restaurant 5-contact center

		//die(print_r($idMembership, true ));

           // different role, different questions 

            if ($idMembership == 'restaurant') { 
           // create first question group default

              $questions_groups1 = array(
                 'Arrival',
                 'Service',
                 'Food',
                 'Conclusion'
                 );

              for ($i=0;$i<=3;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

	//die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

			//die(var_dump($tableGateway));
            }

	//die(var_dump($idQuestionGroup));	

            // create first questions question_groups
            $questions1 = array(

             array ('Were you approached by our staff within 5 minutes of your arrival?',4,40,2,0),
             array ('Do you consider the wait to get a table acceptable?',5,50,2,1),
             array ('On a scale of 1 to 5, 5 being the highest score, how would you rate the ambiance of our restaurant?',1,10,2,2)
             );

            $questions2 = array(
             array ('Did a waiter approach your table within the first 5 minutes after you sat at the table?',12,30,2,0),
             array ('Was a waiter visible at all times in case you needed anything?',3,10,2,1),
             array ('Were you told about the day’s specials or chef’s recommendations?',3,10,2,2),
             array ('Would you say the time between ordering and getting your food was acceptable',6,30,2,3),
             array ('Are you satisfied with the time it took us to process your payment?',6,20,2,4)
             );
            $questions3 = array(
             array ('Were the food and drinks served to the right person?',4,10,2,0),
             array ('On a scale of 1 to 5, 5 being the highest score, how would you rate the presentation of your dish?',12,30,5,1),
             array ('On a scale of 1 to 5, 5 being the highest score, how would you rate the taste of your food?',24,60,5,2),

             );

            $questions4 = array(
             array ('Would you say our restaurant offers a good price / quality relation?',6,30,2,0),
             array ('Would you recommend our restaurant to friends and family?',14,70,2,1),
             );

		//die(var_dump($questions1));

		// create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Restaurant'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

		//die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=2;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

		// forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

		//die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=4;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

		// forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

		//die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=2;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

		// forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

		//die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 4

        for ($i=0;$i<=1;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions4[$i][0]),
                'id_group' => $idQuestionGroup[3],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion4 = $tableGateway->lastInsertValue;                

		// forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion4,
                'answers' => sprintf($questions4[$i][3],$i),
                'weight' => sprintf($questions4[$i][1],$i),
                'weight_percentage' => sprintf($questions4[$i][2],$i),
                'order' => sprintf($questions4[$i][4],$i),

                );

		//die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }



            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Restaurant'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


	 // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);

           // create first question group default

              $questions_groups1 = array(
                 'Arrival',
                 'Service',
                 'Food',
                 'Conclusion'
                 );

              for ($i=0;$i<=3;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

	//die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

			//die(var_dump($tableGateway));
            }

	//die(var_dump($idQuestionGroup));	

            // create first questions question_groups
            $questions1 = array(

             array ('Were you approached by our staff within 5 minutes of your arrival?',5,50,2,0),
             array ('Do you consider the wait to get a table acceptable?',5,50,2,1),
             );

            $questions2 = array(
             array ('Did a waiter approach your table within the first 5 minutes after you sat at the table?',12,30,2,0),
             array ('Were you told about the day’s specials or chef’s recommendations?',6,20,2,1),
             array ('Would you say the time between ordering and getting your food was acceptable',6,30,2,2),
             array ('Are you satisfied with the time it took us to process your payment?',6,20,2,3)
             );
            $questions3 = array(
             array ('Were the food and drinks served to the right person?',16,40,2,0),
             array ('On a scale of 1 to 5, 5 being the highest score, how would you rate the taste of your food?',24,60,5,1),
             );

            $questions4 = array(
             array ('Would you say our restaurant offers a good price / quality relation?',20,100,2,0),
             );

		//die(var_dump($questions1));

		// create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Restaurant Short'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

		//die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=1;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

		// forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

		//die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=3;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

		// forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

		//die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=1;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

		// forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

		//die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 4

        for ($i=0;$i<=0;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions4[$i][0]),
                'id_group' => $idQuestionGroup[3],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion4 = $tableGateway->lastInsertValue;                

		// forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion4,
                'answers' => sprintf($questions4[$i][3],$i),
                'weight' => sprintf($questions4[$i][1],$i),
                'weight_percentage' => sprintf($questions4[$i][2],$i),
                'order' => sprintf($questions4[$i][4],$i),

                );

		//die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }



            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Restaurant Short'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


	 // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);



            // assign dashboard to current role
        $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
            // get default dashboards
        $dashboardReport = $dashboardReportTable->getByAction('today-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('mtd-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('score-needed');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('global-daily-progress');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);


    }
    elseif ($idMembership == 'hotel') {
           // create first question group default

              $questions_groups1 = array(
            'Reservation',
            'Check in',
            'Your room',
            'Services',
            'Check out',
            'Conclusion'
                 );

              for ($i=0;$i<=5;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

    //die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

            //die(var_dump($tableGateway));
            }

    //die(var_dump($idQuestionGroup));  

            // create first questions question_groups
    $questions1 = array(

     array ('When choosing where to stay, were you able to find our hotel right away?',8,80,2,0),
     array ('Did you receive all information about your reservation in a timely manner?',2,20,2,1),
     );

    $questions2 = array(
     array ('Were you welcomed to our hotel within 5 minutes of your arrival?',5,50,2,0),
     array ('Were you able to go through the check in process without problems?',2,20,2,1),
     array ('Was your room ready by the check in time?',3,30,2,2),
     );
    $questions3 = array(
     array ('Would you say your room had all the amenities you needed?',3,10,2,0),
     array ('Did you find your room quiet enough to work and/or rest?',6,20,2,1),
     array ('Was your room clean and neat?',9,30,2,2),
     array ('Were your mattress and pillows comfortable?',6,20,2,3),
     array ('On a scale of 1 to 5, 5 being the highest score, how would you rate your room?',6,20,5,4),

     );

    $questions4 = array(
     array ('Did you find your room clean and neat every day?',6,30,2,0),
     array ('Did you feel our room service was available every time you needed it?',2,10,2,1),
     array ('On a scale of 1 to 5, 5 being the highest score, how would you rate our internet connection?',4,20,5,2),
     array ('Did you feel our front desk service was available every time you needed it?',4,20,2,3),
     array ('How would you rate our breakfast?',4,20,5,4),
     );

    $questions5 = array(
     array ('How would you rate our check out process?',8,80,5,0),
     array ('Were you offered bag storage and transportation during the checkout process?',2,20,2,1),
     );

    $questions6 = array(
     array ('On a scale of 1 to 5, 5 being the highest score, how would you rate your stay in our hotel?',8,40,5,0),
     array ('Would you say our hotel offers a good price / quality relation?',2,10,2,1),
     array ('Would you stay in our hotel again or recommend it to friends and family?',10,50,2,2),
     );

        //die(var_dump($questions6));

        // create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Hotel'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

        //die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=1;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

        // forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

        //die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=2;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

        // forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

        //die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=4;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 4

        for ($i=0;$i<=4;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions4[$i][0]),
                'id_group' => $idQuestionGroup[3],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion4 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion4,
                'answers' => sprintf($questions4[$i][3],$i),
                'weight' => sprintf($questions4[$i][1],$i),
                'weight_percentage' => sprintf($questions4[$i][2],$i),
                'order' => sprintf($questions4[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 5

        for ($i=0;$i<=1;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions5[$i][0]),
                'id_group' => $idQuestionGroup[4],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion5 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion5,
                'answers' => sprintf($questions5[$i][3],$i),
                'weight' => sprintf($questions5[$i][1],$i),
                'weight_percentage' => sprintf($questions5[$i][2],$i),
                'order' => sprintf($questions5[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 6

        for ($i=0;$i<=2;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions6[$i][0]),
                'id_group' => $idQuestionGroup[5],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion6 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion6,
                'answers' => sprintf($questions6[$i][3],$i),
                'weight' => sprintf($questions6[$i][1],$i),
                'weight_percentage' => sprintf($questions6[$i][2],$i),
                'order' => sprintf($questions6[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }


            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Hotel'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


     // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);

          // create second question group default

              $questions_groups1 = array(
            'Check in',
            'Your room',
            'Services',
            'Check out',
            'Conclusion'
                 );

              for ($i=0;$i<=4;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

    //die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

            //die(var_dump($tableGateway));
            }

    //die(var_dump($idQuestionGroup));  

            // create second questions question_groups
    $questions1 = array(

     array ('Were you welcomed to our hotel within 5 minutes of your arrival?',5,50,2,0),
     array ('Was your room ready by the check in time?',5,50,2,1),
     );

    $questions2 = array(
     array ('Was your room clean and neat?',15,50,2,0),
     array ('Were your mattress and pillows comfortable?',6,20,2,1),
     array ('On a scale of 1 to 5, 5 being the highest score, how would you rate your room?',9,30,5,2),

     );

    $questions3 = array(
     array ('Did you feel our room service was available every time you needed it?',4,20,2,0),
     array ('On a scale of 1 to 5, 5 being the highest score, how would you rate our internet connection?',8,40,5,1),
     array ('How would you rate our breakfast?',8,40,5,2),
     );

    $questions4 = array(
     array ('How would you rate our check out process?',8,80,5,0),
     array ('Were you offered bag storage and transportation during the checkout process?',2,20,2,1),
     );

    $questions5 = array(
     array ('On a scale of 1 to 5, 5 being the highest score, how would you rate your stay in our hotel?',8,40,5,0),
     array ('Would you say our hotel offers a good price / quality relation?',22,60,2,1),
     );

        //die(var_dump($questions6));

        // create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Hotel Short'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

        //die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=1;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

        // forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

        //die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=2;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

        // forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

        //die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=2;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 4

        for ($i=0;$i<=1;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions4[$i][0]),
                'id_group' => $idQuestionGroup[3],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion4 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion4,
                'answers' => sprintf($questions4[$i][3],$i),
                'weight' => sprintf($questions4[$i][1],$i),
                'weight_percentage' => sprintf($questions4[$i][2],$i),
                'order' => sprintf($questions4[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // question group 5

        for ($i=0;$i<=1;$i++)
        {
            $data = array(
                'name' => $this->getTranslator()->translate($questions5[$i][0]),
                'id_group' => $idQuestionGroup[4],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion5 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion5,
                'answers' => sprintf($questions5[$i][3],$i),
                'weight' => sprintf($questions5[$i][1],$i),
                'weight_percentage' => sprintf($questions5[$i][2],$i),
                'order' => sprintf($questions5[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

 

            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Hotel Short'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


     // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);




            // assign dashboard to current role
        $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
            // get default dashboards
        $dashboardReport = $dashboardReportTable->getByAction('today-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('mtd-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('score-needed');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('global-daily-progress');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);


    }
     elseif ($idMembership == 'contact_center') { 
          // create first question group default

              $questions_groups1 = array(
            'Protocol',
            'Product',
            'Sales skills',
                 );

              for ($i=0;$i<=2;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

    //die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

            //die(var_dump($tableGateway));
            }

    //die(var_dump($idQuestionGroup));  

            // create first questions question_groups
    $questions1 = array(

     array ('Is the greeting correct and complete?',10,70,2,0),
     array ('Is the agent friendly and respectful throughout the call?',10,20,2,1),
     array ('Call close and goodbye complete?',10,10,2,2),
     );

    $questions2 = array(
     array ('Explains all product characteristics?',10,50,2,0),
     array ('Informs correct price of the product/s?',10,50,2,1),
     );
    $questions3 = array(
     array ('Handles objections successfully?',10,10,2,0),
     array ('Does the agent transition to sale close successfully?',10,20,2,1),
     array ('Verifies client’s details?',30,70,2,2),
     );
        //die(var_dump($questions6));

        // create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Call Center Sales Short'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

        //die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=2;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

        // forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

        //die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=1;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

        // forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

        //die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=2;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Call Center Sales Short'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


     // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);

  // create second question group default

              $questions_groups1 = array(
            'Protocol',
            'Product',
            'Soft Skills',
                 );

              for ($i=0;$i<=2;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

    //die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

            //die(var_dump($tableGateway));
            }

    //die(var_dump($idQuestionGroup));  

            // create second questions question_groups
    $questions1 = array(

     array ('Is the greeting correct and complete?',6,60,2,0),
     array ('Asks for personal details?',2,20,2,1),
     array ('Notifies about call being recorded?',1,10,2,2),
     array ('Is the agent friendly and respectful throughout the call?',1,10,2,3),
     );

    $questions2 = array(
     array ('Inquiries about reason for the call and repeats to confirm?',5,50,2,0),
     array ('Seems knowledgeable about product / service?',2,20,2,1),
     array ('Solves the issue or escalates to the right area?',3,30,2,2),
     );
    $questions3 = array(
     array ('Actively listens and replies accordingly?',20,10,2,0),
     array ('Maintains control of the call?',20,20,2,1),
     array ('Call close and goodbye complete?',20,30,2,2),
     array ('Offers reference number?',20,40,2,3),
     );
        //die(var_dump($questions6));

        // create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Call Center Customer Care'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

        //die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=3;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

        // forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

        //die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=2;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

        // forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

        //die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=3;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

 

            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Call Center Customer Care'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


     // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);

 // create second question group default

              $questions_groups1 = array(
            'Protocol',
            'Product',
            'Soft Skills',
                 );

              for ($i=0;$i<=2;$i++)
              {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions_groups1[$i]),
                    'is_fatal' => '0',
                    'ml_fatal' => '0',
                    'id_organization' => $idOrganization,
                    'order'=>'0',
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );

    //die(var_dump($data));

                $tableGateway = new TableGateway('questions_groups', $dbAdapter);
                $tableGateway->insert($data);
                $idQuestionGroup[] = $tableGateway->lastInsertValue;

            //die(var_dump($tableGateway));
            }

    //die(var_dump($idQuestionGroup));  

            // create third questions question_groups
    $questions1 = array(

     array ('Is the greeting correct and complete?',6,60,2,0),
     array ('Notifies about call being recorded?',2,20,2,1),
     array ('Is the agent friendly and respectful throughout the call?',2,20,2,2),
     );

    $questions2 = array(
     array ('Inquiries about reason for the call and repeats to confirm?',5,50,2,0),
     array ('Seems knowledgeable about product / service?',2,20,2,1),
     array ('Solves the issue or escalates to the right area?',3,30,2,2),
     );
    $questions3 = array(
     array ('Call close and goodbye complete?',40,60,2,0),
     array ('Offers reference number?',40,40,2,1),
     );
        //die(var_dump($questions6));

        // create an empty form

            $data = array(
                'name' => $this->getTranslator()->translate('Call Center Customer Care Short'),
                'id_organization'=>$idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );
            $tableGateway = new TableGateway('forms', $dbAdapter);
            $tableGateway->insert($data);
            $idForm = $tableGateway->lastInsertValue;

        //die(var_dump($tableGateway));

           // question group 1

            for ($i=0;$i<=2;$i++)
            {
                $data = array(
                    'name' => $this->getTranslator()->translate($questions1[$i][0]),
                    'id_group' => $idQuestionGroup[0],
                    'id_organization' => $idOrganization,
                    'created' => date("Y-m-d H:i:s"),
                    'created_by' => $idUser,
                    'modified' => date("Y-m-d H:i:s"),
                    'modified_by' => $idUser,
                    );
                
                $tableGateway = new TableGateway('questions', $dbAdapter);
                $tableGateway->insert($data);
                $idquestion1 = $tableGateway->lastInsertValue;                

        // forms_questions

                $data = array(
                    'id_form' => $idForm,
                    'id_question' => $idquestion1,
                    'answers' => sprintf($questions1[$i][3],$i),
                    'weight' => sprintf($questions1[$i][1],$i),
                    'weight_percentage' => sprintf($questions1[$i][2],$i),
                    'order' => sprintf($questions1[$i][4],$i),
                    
                    );

        //die(var_dump($data));

                $tableGateway = new TableGateway('forms_questions', $dbAdapter);
                $tableGateway->insert($data);


            }
            
            // question group 2

            for ($i=0;$i<=2;$i++)
            {
             $data = array(
                'name' => $this->getTranslator()->translate($questions2[$i][0]),
                'id_group' => $idQuestionGroup[1],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

             $tableGateway = new TableGateway('questions', $dbAdapter);
             $tableGateway->insert($data);
             $idquestion2 = $tableGateway->lastInsertValue;                

        // forms_questions

             $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion2,
                'answers' => sprintf($questions2[$i][3],$i),
                'weight' => sprintf($questions2[$i][1],$i),
                'weight_percentage' => sprintf($questions2[$i][2],$i),
                'order' => sprintf($questions2[$i][4],$i),

                );

        //die(var_dump($data));

             $tableGateway = new TableGateway('forms_questions', $dbAdapter);
             $tableGateway->insert($data);

         }

            // question group 3

         for ($i=0;$i<=1;$i++)
         {
            $data = array(
                'name' => $this->getTranslator()->translate($questions3[$i][0]),
                'id_group' => $idQuestionGroup[2],
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);
            $idquestion3 = $tableGateway->lastInsertValue;                

        // forms_questions

            $data = array(
                'id_form' => $idForm,
                'id_question' => $idquestion3,
                'answers' => sprintf($questions3[$i][3],$i),
                'weight' => sprintf($questions3[$i][1],$i),
                'weight_percentage' => sprintf($questions3[$i][2],$i),
                'order' => sprintf($questions3[$i][4],$i),

                );

        //die(var_dump($data));

            $tableGateway = new TableGateway('forms_questions', $dbAdapter);
            $tableGateway->insert($data);
        }

 

            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Call Center Customer Care Short'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;


     // create project channel default Email

        $data = array(
            'id_project' => $idProject,
            'id_channel' => '2',
            'id_form' => $idForm,
            'public_token' => md5(uniqid(rand(), true)),
            );

        $tableGateway = new TableGateway('projects_channels', $dbAdapter);
        $tableGateway->insert($data);


            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);




            // assign dashboard to current role
        $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
            // get default dashboards
        $dashboardReport = $dashboardReportTable->getByAction('today-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('mtd-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('score-needed');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('global-daily-progress');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);





    }

    else {

            // create first question group default


        $data = array(
            'name' => $this->getTranslator()->translate('Question Default Group 1'),
            'is_fatal' => '0',
            'ml_fatal' => '0',
            'id_organization' => $idOrganization,
            'order'=>'0',
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );
        $tableGateway = new TableGateway('questions_groups', $dbAdapter);
        $tableGateway->insert($data);
        $idQuestionGroup = $tableGateway->lastInsertValue;

            // create first 10 questions
        for ($i=1;$i<=10;$i++)
        {
            $data = array(
                'name' => sprintf($this->getTranslator()->translate('Question Default  %s'),$i),
                'id_group' => $idQuestionGroup,
                'id_organization' => $idOrganization,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $idUser,
                'modified' => date("Y-m-d H:i:s"),
                'modified_by' => $idUser,
                );

            $tableGateway = new TableGateway('questions', $dbAdapter);
            $tableGateway->insert($data);                
        }

            // create an empty form
        $data = array(
            'name' => $this->getTranslator()->translate('Form Default  1'),
            'id_organization'=>$idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );
        $tableGateway = new TableGateway('forms', $dbAdapter);
        $tableGateway->insert($data);

            // create project
        $data = array(
            'name' => $this->getTranslator()->translate('Project Default 1'),
            'min_performance_required' => '90',
            'id_organization' => $idOrganization,
            'created' => date("Y-m-d H:i:s"),
            'created_by' => $idUser,
            'modified' => date("Y-m-d H:i:s"),
            'modified_by' => $idUser,
            );

        $tableGateway = new TableGateway('projects', $dbAdapter);
        $tableGateway->insert($data);
        $idProject = $tableGateway->lastInsertValue;

            // assign project to user as a manager
        $projectRoleTable = $this->getServiceLocator()->get('Application\Model\ProjectRoleTable');
        $projectRole = $projectRoleTable->getByKey('manager');
        $data = array(
            'id_user' => $idUser,
            'id_project' => $idProject,
            'id_project_role' => $projectRole->id,
            'active' => '1',
            'blocked' => '0'
            );
        $tableGateway = new TableGateway('users_projects', $dbAdapter);
        $tableGateway->insert($data);

            // assign dashboard to current role
        $dashboardReportTable = $this->getServiceLocator()->get('Application\Model\DashboardReportTable');
            // get default dashboards
        $dashboardReport = $dashboardReportTable->getByAction('today-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('mtd-score');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('score-needed');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);
        $dashboardReport = $dashboardReportTable->getByAction('global-daily-progress');
        $dashboardReportTable->addOrganizationRoleDashboard($idOrganization, $idRole, $dashboardReport->id, true);



    }





} catch (\Exception $e) {
    throw $e;
}
}

    /**
     * 
     * @param int $idOrganization
     * @throws \Exception
     * @return \Application\Model\OrganizationBillingDetail
     */
    public function fetchCurrentBillingDetails($idOrganization)
    {
        $id  = (int) $id;
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from(array('obd' => 'organizations_billing_details'))
        ->join(array('s'=>'states'), 'obd.id_state=s.id', array('state'=>'name','id_country'))
        ->join(array('c'=>'countries'), 's.id_country=c.id', array('country'=>'name'))
        ->where(array('obd.id_organization'=>$idOrganization,'obd.active'=>'1'));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            return null;
        }
        
        $paypalHelper = new PayPal($this->getServiceLocator());
        
        try {
            $card = $paypalHelper->getCreditCard($row['creditcard_id']);
            
            $row['cardtype']=$card->getType();
            $row['cardnumber']=$card->getNumber();
            $row['cvv2']=$card->getCvv2();
            $row['exp_month']=$card->getExpireMonth();
            $row['exp_year']=$card->getExpireYear();
            
            $entity = new OrganizationBillingDetail();
            $entity->exchangeArray($row);

            return $entity;
        } catch (\Exception $e) {
            return null;
        }        
        
    }
    
    public function saveBillingDetails(OrganizationBillingDetail $entity, $existsTransaction=false)
    {
        $paypalHelper = new PayPal($this->getServiceLocator());
        
        $dbAdapter = $this->tableGateway->getAdapter();

        if (!$existsTransaction)
            $connection = $dbAdapter->getDriver()->getConnection();

        try {

            if (!$existsTransaction)
                $connection->beginTransaction();
            
            $billingDetailsTable = new TableGateway('organizations_billing_details', $dbAdapter);
            $auth = new AuthenticationService();
            
            // disable other billing details attached to the current organization
            $billingDetailsTable->update(
                array(
                    'active'=>'0',
                    'end_date'=>date("Y-m-d H:i:s"),
                    'modified'=>date("Y-m-d H:i:s"),        
                    'modified_by'=>$auth->getIdentity()->id
                    ),
                array(
                    'id_organization'=>$auth->getIdentity()->id_organization,
                    'active'=>'1'
                    )
                );

            try {
                // save in paypals vault the credit card details
                $creditCardId = $paypalHelper->saveCreditCard(array(
                    'type'=>$entity->cardtype,
                    'number'=>$entity->cardnumber,
                    'expire_month'=>$entity->exp_month,
                    'expire_year'=>$entity->exp_year,
                    'cvv2'=>$entity->cvv2,
                    ));                
            } catch (\Exception $e) {
                throw $e;
            }
            
            // store in database the new billing details with the creditcard id received from paypal
            $data = array(
                'id_organization' => $auth->getIdentity()->id_organization,
                'id_state' => $entity->id_state,
                'creditcard_id' => $creditCardId,
                'start_date' => date("Y-m-d H:i:s"),
                'cardholder_name' => $entity->cardholder_name,
                'address' => $entity->address,
                'city' => $entity->city,
                'postcode' => $entity->postcode,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $auth->getIdentity()->id
                );
            
            $billingDetailsTable->insert($data);

            if (!$existsTransaction)
                $connection->commit();

        } catch (\Exception $e) {

            try {
                if ($creditCardId)
                    $paypalHelper->deleteCreditCard($creditCardId);
            } catch (\Exception $e) {
            }
            
            if (!$existsTransaction)
                $connection->rollback();

            throw $e;
        }
    }
    
    /**
     *
     * @param int $idOrganization
     * @throws \Exception
     * @return \Application\Model\OrganizationSubscription
     */
    public function fetchCurrentSubscription($idOrganization)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
        ->from(array('os'=>'organizations_subscriptions'))
        ->join(array('m'=>'memberships'), 'os.id_membership=m.id', array('membership'=>'name','trial_days'))
        ->where(array('os.id_organization'=>$idOrganization))
        ->order('os.id DESC')
        ->limit(1);

        $selectString = $sql->getSqlStringForSqlObject($select);

        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        $row = $rowset->current();

        if (!$row) {
            throw new \Exception("Could not find row");
        }

        $entity = new OrganizationSubscription();
        $entity->exchangeArray($row);

        return $entity;
    }
    
    /**
     * 
     * @param \Application\Model\OrganizationSubscription $entity
     * @param bool $updateCC
     * @throws Exception
     */
    public function changeSubscriptionPlan(OrganizationSubscription $entity, $updateCC=false)
    {
        $dbAdapter = $this->tableGateway->getAdapter();

        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {

            $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            // check if it's only plan details update or if it's also a credit card update
            // if it's a credit card update, disable the previous credit card and insert the new one in PayPal and in the db
            if ($updateCC)
            {
                $this->saveBillingDetails($entity->getBillingDetails(), true);           
            }
            
            // get current subscription details to use it in following operations
            $currentSubscriptionDetails = $this->fetchCurrentSubscription($auth->getIdentity()->id_organization);
            
            // get current billing details from paypal to charge on the organization account if it's necessary
            // this method has to be called after the credit card update
            $currentBillingDetails = $this->fetchCurrentBillingDetails($auth->getIdentity()->id_organization);
            
            // get selected membership
            $membershipTable = $this->getServiceLocator()->get('Application\Model\MembershipTable');
            $membership = $membershipTable->getById($entity->id_membership);
            
            // get organization details
            $organizationTable = $this->getServiceLocator()->get('Application\Model\OrganizationTable');
            $organization = $organizationTable->getById($auth->getIdentity()->id_organization);
            
            // disable current subscription setting end_date, active "false", modified and modified_by if it's hasn't ended before
            $organizationSubscriptionsTable = new TableGateway('organizations_subscriptions', $dbAdapter);
            if (!$currentSubscriptionDetails->end_date)
            {
                $totalDaysInPeriod = $currentSubscriptionDetails->billing_period=='month'?30:365;
                
                $organizationSubscriptionsTable->update(
                    array(
                        'active'=>'0',
                        'end_date'=>date_format(date_add(date_create($currentSubscriptionDetails->start_date), date_interval_create_from_date_string("$totalDaysInPeriod days")), 'Y-m-d'),
                        'modified'=>date("Y-m-d H:i:s"),
                        'modified_by'=>$auth->getIdentity()->id
                        ),
                    array(
                        'id'=>$currentSubscriptionDetails->id
                        )
                    );
            }

            // calculate membership total
            $totalSubscription = $membershipTable->calculatePrice($entity->id_membership, $entity->max_users, $entity->billing_period);
            
            // set price per user from membership
            $pricePerUser = $entity->billing_period=='month'?$membership->price_month:$membership->price_year;
            
            if ($entity->id_membership != $currentSubscriptionDetails->id_membership || 
                $currentSubscriptionDetails->end_date || 
                $currentSubscriptionDetails->in_trial)
            {
//                 print"<pre>";print_r('0) different product type');print"</pre>";
                // 0) different product type
                // calculate new prices => $totalSubscription price
                // calculate money to charge for users in current period
                $totalDaysInPeriod = $entity->billing_period=='month'?30:365;
                
                $paymentDesc = sprintf($this->getTranslator()->translate('New subscription for %s users per %s.'), $entity->max_users, $entity->billing_period);
                $paymentTotal = $entity->max_users * $pricePerUser;
                
                $lastBillignDate = $currentSubscriptionDetails->last_billing_date;
                $nextBillingDate = date('Y-m-d',strtotime('+'.$totalDaysInPeriod.' days'));
                
                $newSubscriptionStartDate = date("Y-m-d H:i:s");
            }
            elseif ($entity->billing_period == $currentSubscriptionDetails->billing_period && 
                $entity->max_users > $currentSubscriptionDetails->max_users)
            {
//                 print"<pre>";print_r('1) same billing period but more users');print"</pre>";
                // 1) same billing period but more users
                // calculate new prices => $totalSubscription price
                // calculate money to charge for aditional users in current period
                $totalDaysInPeriod = $currentSubscriptionDetails->billing_period=='month'?30:365;
                
                $dateInterval = date_diff(date_create(date('Y-m-d')), date_create($currentSubscriptionDetails->next_billing_date));
                $remainingPercentageInPeriod = $dateInterval->days / $totalDaysInPeriod;
                $aditionalUsers = $entity->max_users - $currentSubscriptionDetails->max_users;
                $aditionalUsersCharge = ($aditionalUsers * $pricePerUser) * $remainingPercentageInPeriod; 
                
                $paymentDesc = sprintf($this->getTranslator()->translate('Charge for %s aditional users.'), $aditionalUsers);
                $paymentTotal = $aditionalUsersCharge;
                
                $lastBillignDate = $currentSubscriptionDetails->last_billing_date;
                $nextBillingDate = $currentSubscriptionDetails->next_billing_date;
                
                $newSubscriptionStartDate = $currentSubscriptionDetails->next_billing_date;
            }
            elseif ($entity->billing_period != $currentSubscriptionDetails->billing_period && 
                ($entity->max_users == $currentSubscriptionDetails->max_users || $entity->max_users > $currentSubscriptionDetails->max_users))
            {
//                 print"<pre>";print_r('3) different billing period (month > year), same users or more users');print"</pre>";
                // 3) different billing period (month > year), same users or more users
                // calculate new prices,
                // calculate money to discount in the new subscription for missing days in current period
                $totalDaysInPeriod = $currentSubscriptionDetails->billing_period=='month'?30:365;
                
                $dateInterval = date_diff(date_create(date('Y-m-d')), date_create($currentSubscriptionDetails->next_billing_date));
                $remainingPercentageInPeriod = $dateInterval->days / $totalDaysInPeriod;
                $currentUsersDiscount = ($currentSubscriptionDetails->max_users * $currentSubscriptionDetails->unit_price) * $remainingPercentageInPeriod;
                
                $paymentDesc = sprintf($this->getTranslator()->translate('New subscription for %s users per %s with discount for %s remaining days.'), $entity->max_users, $entity->billing_period, $dateInterval->days);
                $paymentTotal = ($entity->max_users * $pricePerUser) - $currentUsersDiscount;
                
                $lastBillignDate = $currentSubscriptionDetails->last_billing_date;
                $nextBillingDate = date('Y-m-d',strtotime('+1 year'));
                
                $newSubscriptionStartDate = date("Y-m-d H:i:s");
            }
            
            // 2) same billing period but less users DEPRECATED, NOT DEVELOPED SO FAR
            // 5) different billing period (month > year), but less users DEPRECATED, NOT DEVELOPED SO FAR
            
            try {

                // create payment in paypal
                $paypalHelper = new PayPal($this->getServiceLocator());
                $payment = $paypalHelper->makePaymentUsingCC($currentBillingDetails->creditcard_id, $paymentTotal, 'USD', $paymentDesc, $organization->firstname, $organization->lastname, $organization->email);

                if ($payment->state!='approved')
                    throw new \Exception($this->getTranslator()->translate('Your credit card wasn\'t approved. Please verify your details and try again.'));

                $paymentId = $payment->id;
                $paymentStatus = $payment->state;

            } catch (\Exception $e) {
                throw new \Exception($this->getTranslator()->translate('An error ocurred processing your credit card. Please check the credit card details and try again.'));
            }
            
            // create new subscription details
            $subscription = array(
                'id_organization' => $auth->getIdentity()->id_organization,
                'id_membership' => $entity->id_membership,
                'max_users' => $entity->max_users,
                'start_date' => $newSubscriptionStartDate,
                'billing_period' => $entity->billing_period,
                'unit_price' => $pricePerUser,
                'total_price' => $totalSubscription,
                'last_billing_date' => $lastBillignDate,
                'next_billing_date' => $nextBillingDate,
                'created' => date("Y-m-d H:i:s"),
                'created_by' => $auth->getIdentity()->id,
                );
            
            $organizationSubscriptionsTable->insert($subscription);
            $subscriptionId = $organizationSubscriptionsTable->lastInsertValue;
            
            if ($currentSubscriptionDetails->id_membership!=$entity->id_membership)
            {
                $organizationMembershipTable = new TableGateway('organizations_memberships', $dbAdapter);
                $organizationMembershipTable->update(array(
                    'active'=>'0',
                    'modified'=>date("Y-m-d H:i:s"),
                    'modified_by'=>$auth->getIdentity()->id
                    ),array('id_organization'=>$auth->getIdentity()->id_organization,'active'=>'1'));
                $organizationMembershipTable->insert(array(
                    'id_organization'=>$auth->getIdentity()->id_organization,
                    'id_membership'=>$entity->id_membership,
                    'created'=>date("Y-m-d H:i:s"),
                    'created_by'=>$auth->getIdentity()->id,
                    ));
            }
            
            // create transaction
            $transaction = array(
                'id_organization' => $auth->getIdentity()->id_organization,
                'id_organization_subscription' => $subscriptionId,
                'id_membership' => $entity->id_membership,
                'id_organization_billing_detail' => $currentBillingDetails->id,
                'payment_id' => $paymentId,
                'payment_status' => $paymentStatus,
                'payment_total' => $paymentTotal,
                'payment_currency' => 'USD',
                'payment_description' => $paymentDesc,
                );
            $transactionsTable = new TableGateway('transactions', $dbAdapter);
            $transactionsTable->insert($transaction);

            $connection->commit();
            
            // update session with new membership details
            $session = new Container('role');
            $session->role->membership = $membershipTable->fetchOrganizationMembership($auth->getIdentity()->id_organization);
            
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }       
    }
    
    public function cancelSubscription()
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $auth = new AuthenticationService();
        $currentSubscription = $this->fetchCurrentSubscription($auth->getIdentity()->id_organization);
        
        $organizationSubscriptionsTable = new TableGateway('organizations_subscriptions', $dbAdapter);
        $organizationSubscriptionsTable->update(
            array(
                'end_date'=>$currentSubscription->next_billing_date,
                'modified'=>date("Y-m-d H:i:s"),
                'modified_by'=>$auth->getIdentity()->id
                ),
            array(
                'id'=>$currentSubscription->id
                )
            );
    }
    
    /**
     * Returns a single organization subscription
     * @param array $filter
     * @param array $orderby
     * @throws \Exception
     * @return \Application\Model\OrganizationSubscription
     */
    public function fetchSubscription($filter=array(), $orderby=array())
    {
        $where = array();
        $order = array();
        
        if (isset($filter['organization']))
            array_push($where, '(os.id_organization=\''.$filter['organization'].'\')');
        
        if (isset($filter['active']))
            array_push($where, '(os.active=\''.$filter['active'].'\')');
        
        if (isset($orderby['max_users']))
            array_push($order, 'os.max_users '.$orderby['max_users']);
        
        if (isset($orderby['last_end_date']))
            array_push($order, 'os.end_date '.$orderby['last_end_date']);
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
        ->from(array('os'=>'organizations_subscriptions'))
        ->where(implode(' AND ', $where))
        ->order(implode(',', $order))
        ->limit(1);

        $selectString = $sql->getSqlStringForSqlObject($select);

        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);

        $row = $rowset->current();

        $entity = new OrganizationSubscription();
        $entity->exchangeArray($row);

        return $entity;
    }
    
    public function countMaxAllowedUsers($filter=array())
    {
        $where = array();
        
        if (isset($filter['active']))
            $where[] = 'active = \''.$filter['active'].'\'';
        
        if (isset($filter['organization']))
            $where[] = '(id_organization = \''.$filter['organization'].'\')';

        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from('organizations_subscriptions')
        ->columns(array('max_users'))
        ->where(implode(' AND ', $where))
        ->order('max_users DESC')
        ->limit(1);
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        return $row->max_users?:0;
    }
    
    public function fetchAllSubscriptions($filter=array())
    {
        $where = array();
        
        if (isset($filter['not_id']))
            $where[] = '(os.id != \''.$filter['not_id'].'\')';
        
        if (isset($filter['active']))
            $where[] = 'os.active = \''.$filter['active'].'\'';
        
        if (isset($filter['organization']))
            $where[] = '(os.id_organization = \''.$filter['organization'].'\')';
        
        if (isset($filter['not_end_date']))
            $where[] = '(os.end_date IS NOT NULL)';
        
        if (isset($filter['next_billing_date']))
            $where[] = 'DATE(os.next_billing_date) = \''.$filter['next_billing_date'].'\'';
        
        if (isset($filter['trial']))
            $where[] = 'os.in_trial = \''.$filter['trial'].'\'';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
        ->from(array('os'=>'organizations_subscriptions'))
        ->join(array('m'=>'memberships'), 'os.id_membership=m.id' ,array('membership'=>'name'))
        ->where(!empty($where) ? implode(' AND ', $where) : 1);

        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new OrganizationSubscription());
        $results = $resultSetPrototype->initialize($results);

        return $results;
    }
    
    /**
     * 
     * @param \Application\Model\OrganizationSubscription $subscription
     * @throws Exception
     */
    public function executePayment($subscription)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        $err = '';
        
        try {

            $connection->beginTransaction();
            
            $organization = $this->getById($subscription->id_organization);
            $organizationBillingDetails = $this->fetchCurrentBillingDetails($subscription->id_organization);
            $paymentTotal = $subscription->max_users * $subscription->unit_price;
            $paymentDesc = sprintf($this->getTranslator()->translate('Subscription payment for %s users per %s.'), $subscription->max_users, $subscription->billing_period);
            
            $paypalHelper = new PayPal($this->getServiceLocator());
            $payment = $paypalHelper->makePaymentUsingCC($organizationBillingDetails->creditcard_id, $paymentTotal, 'USD', $paymentDesc, $organization->firstname, $organization->lastname, $organization->email);
            
            $paymentId = $payment->id;
            $paymentStatus = $payment->state;
            
            if ($paymentStatus!='approved')
                $err = sprintf('Credit card for subscription %s was not approved.', $subscription->id);
            
            // create transaction
            $transaction = array(
                'id_organization' => $subscription->id_organization,
                'id_organization_subscription' => $subscription->id,
                'id_membership' => $subscription->id_membership,
                'id_organization_billing_detail' => $organizationBillingDetails->id,
                'payment_id' => $paymentId,
                'payment_status' => $paymentStatus,
                'payment_total' => $paymentTotal,
                'payment_currency' => 'USD',
                'payment_description' => $paymentDesc,
                );
            $transactionsTable = new TableGateway('transactions', $dbAdapter);
            $transactionsTable->insert($transaction);
            
            // set last and next billing date
            $organizationSubscriptionsTable = new TableGateway('organizations_subscriptions', $dbAdapter);
            $totalDaysInPeriod = $subscription->billing_period=='month'?30:365;
            $organizationSubscriptionsTable->update(
                array(
                    'last_billing_date'=>date('Y-m-d'),
                    'next_billing_date'=>date_format(date_add(date_create(date('Y-m-d')), date_interval_create_from_date_string("$totalDaysInPeriod days")), 'Y-m-d'),
                    ),
                array(
                    'id'=>$subscription->id
                    )
                );
            
            $connection->commit();
            
        } catch (\Exception $e) {
            $connection->rollback();
            $err = sprintf('Transaction for subscription %s failed.', $subscription->id);
        }
        
        if (!empty($err))
        {
            try {
                // log error and then submit email
                $writer = new \Zend\Log\Writer\Stream(APPLICATION_PATH.'/logs/transactions');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info($err);
                
                // send user account creation email
                $config = $this->getServiceLocator()->get('config');
                
                $message = new \Zend\Mail\Message();
                
                $message->addFrom($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
                ->setSender($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
                ->addReplyTo($config['smtp_options']['from_email'], $config['smtp_options']['from_name'])
                ->addTo($config['smtp_options']['from_email'])
                ->setSubject($this->getTranslator()->translate("Quick Audits - Transaction Failed!"))
                ->setEncoding('UTF-8')
                ->setBody($err);
                
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
                
            } catch (\Exception $e) {

            }
        }
    }
    
    public function disableSubscriptionFromCron($subscription)
    {
        try {
            $dbAdapter = $this->tableGateway->getAdapter();
            
            $organizationSubscriptionsTable = new TableGateway('organizations_subscriptions', $dbAdapter);
            $organizationSubscriptionsTable->update(array('active'=>'0'),array('id'=>$subscription->id));
            
        } catch (\Exception $e) {
            throw $e;
        }       
    }
    
    public function increaseSubscriptionUsers($subscription)
    {
        try {
            $dbAdapter = $this->tableGateway->getAdapter();

            $organizationSubscriptionsTable = new TableGateway('organizations_subscriptions', $dbAdapter);
            $organizationSubscriptionsTable->update(array('max_users'=>new Expression('max_users+1')),array('id'=>$subscription->id));

        } catch (\Exception $e) {
            throw $e;
        }
    }
}