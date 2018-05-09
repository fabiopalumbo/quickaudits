<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ProjectTable
{
    /**
     * 
     * @var Zend\Db\TableGateway\TableGateway
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

    /**
     * 
     * @param Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }
    
    /**
     *
     * @return \Zend\Db\Sql\Select
     */
    public function getCurrentUserProjectsSelect() {
    
        $auth = new AuthenticationService();
        
        $where = array();
        $where[] = "up.id_user='" . $auth->getIdentity()->id . "'";
        $where[] = "up.active='1'";
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('p'=>'projects'))
        ->join(array('up'=>'users_projects'), 'p.id=up.id_project', array())
        ->where(implode(' AND ', $where))
        ->group('p.id');
        
        return $select;
    }

    public function fetchAll($paginated=false, $filter = array(), $order = null)
    {
       
        $where = array();
        
        if (isset($filter['keyword']))
            $where[] = '(p.name LIKE \'%'.$filter['keyword'].'%\')';
        
        if (isset($filter['active']))
        {
            if (!$filter['active'])
                $where[] = '(p.active = \''.(int)$filter['active'].'\' OR p.active = \'\')';
            else
                $where[] = 'p.active = \''.(int)$filter['active'].'\'';
        }
        
        if (isset($filter['organization']))
            $where[] = '(p.id_organization = \''.$filter['organization'].'\')';
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());

        $select = $sql->select()
                        ->from(array('p' => $this->getCurrentUserProjectsSelect()))
                        ->join(array('o'=>'organizations'), 'p.id_organization=o.id', array('organization'=>'name'))
                        ->where(!empty($where) ? implode(' AND ', $where) : 1)
                        ->order(!is_null($order) ? $order : 'p.name ASC');


        if ($paginated) {
            
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Project());
            
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
                ->from(array('p' => 'projects'))
                ->join(array('o'=>'organizations'), 'p.id_organization=o.id', array('organization'=>'name'))
                ->join(array('l'=>'locales'), 'p.id_locale=l.id', array('locale'=>'display_name'), 'left')
                ->where(array('p.id'=>$id));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        $languageTable = new LanguageTable($this->tableGateway);
        $languages = $languageTable->fetchAllProjectLanguages($id);
        
//         $row['languages']=array();
//         foreach ($languages as $item)
//         {
// //             array_push($row['languages'], array('id'=>$item->id,'name'=>$item->name));
//             array_push($row['languages'], $item->id);
//         }
        $row['languages']=$languages;
        
        $row['projects_channels'] = $this->fetchProjectChannelsForms($id);
        
        $project = new Project();
        $project->exchangeArray($row);

        return $project;
    }
    
    public function getByToken($token)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
                ->from(array('p' => 'projects'))
                ->join(array('o'=>'organizations'), 'p.id_organization=o.id', array('organization'=>'name'))
                ->join(array('l'=>'locales'), 'p.id_locale=l.id', array('locale'=>'display_name'), 'left')
                ->where(array('p.public_token'=>$token));
        
        $selectString = $sql->getSqlStringForSqlObject($select);
        
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $token");
        }

        $id = $row['id'];
        
        $languageTable = new LanguageTable($this->tableGateway);
        $languages = $languageTable->fetchAllProjectLanguages($id);
        
//         $row['languages']=array();
//         foreach ($languages as $item)
//         {
// //             array_push($row['languages'], array('id'=>$item->id,'name'=>$item->name));
//             array_push($row['languages'], $item->id);
//         }
        $row['languages']=$languages;
        
        $row['projects_channels'] = $this->fetchProjectChannelsForms($id);
        
        $project = new Project();
        $project->exchangeArray($row);

        return $project;
    }
    
    public function save(Project $entity)
    {
        $dbAdapter = $this->tableGateway->getAdapter();
        
        $connection = $dbAdapter->getDriver()->getConnection();
        
        try {
            
            $connection->beginTransaction();
            
            $auth = new AuthenticationService();
            
            $data = array(
                'name' => $entity->name,
                'min_performance_required' => $entity->min_performance_required,
                'id_organization' => $auth->getIdentity()->id_organization,
                'enable_public' => $entity->enable_public?'1':'0',
                'require_public_names'=>(int)$entity->require_public_names,
                'public_description'=>$entity->public_description,
                'id_locale'=>$entity->id_locale,
                'public_by_agents' => $entity->public_by_agents?'1':'0',
                'enable_form_selector' => $entity->enable_form_selector?'1':'0',
                'be_anonymous' => $entity->be_anonymous?'1':'0',
                'form_selector_question' => $entity->form_selector_question
            );
            
            $id = (int) $entity->id;
            
            if ($id == 0) {

                if($data['enable_form_selector']=='1'){

                    $uid = uniqid(null, true);
                    // Random SHA1 hash
                    $rawid = strtoupper(sha1(uniqid(rand(), true)));
                    // Produce the results
                    $uuid = substr($uid, 6, 8);
                    $uuid .= '-'.substr($uid, 0, 4);
                    $uuid .= '-'.substr(sha1(substr($uid, 3, 3)), 0, 4);
                    $uuid .= '-'.substr(sha1(substr(time(), 3, 4)), 0, 4);
                    $uuid .= '-'.strtolower(substr($rawid, 10, 12));

                    $data['public_token'] = $uuid;
                };
                
                $data['created'] = date("Y-m-d H:i:s");
                $data['created_by'] = $auth->getIdentity()->id;
                $data['modified'] = date("Y-m-d H:i:s");
                $data['modified_by'] = $auth->getIdentity()->id;
                                
                $this->tableGateway->insert($data);
                $entity->id = $this->tableGateway->lastInsertValue;
                
                // set current user as project manager
                // get default project role
                $projectRoleTable = new ProjectRoleTable($this->tableGateway);
                $projectRole = $projectRoleTable->getByKey('manager');
                $userProject = new UserProject();
                $userProject->id_user = $auth->getIdentity()->id;
                $userProject->id_project = $entity->id;
                $userProject->id_project_role = $projectRole->id;
                $userTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                $userTable->saveUserProject($userProject, true);
                
            } else {
                $echk = $this->getById($id);
                if ($echk) {
                    $data['modified'] = date("Y-m-d H:i:s");
                    $data['modified_by'] = $auth->getIdentity()->id;

                    if(!$echk->public_token) {
                        if($data['enable_form_selector']=='1'){

                            $uid = uniqid(null, true);
                            // Random SHA1 hash
                            $rawid = strtoupper(sha1(uniqid(rand(), true)));
                            // Produce the results
                            $uuid = substr($uid, 6, 8);
                            $uuid .= '-'.substr($uid, 0, 4);
                            $uuid .= '-'.substr(sha1(substr($uid, 3, 3)), 0, 4);
                            $uuid .= '-'.substr(sha1(substr(time(), 3, 4)), 0, 4);
                            $uuid .= '-'.strtolower(substr($rawid, 10, 12));

                            $data['public_token'] = $uuid;
                        };                        
                    };
                    
                    $this->tableGateway->update($data, array('id' => $id));
                } else {
                    throw new \Exception('Entity id does not exist');
                }
            }
            
            if (!empty($entity->languages)) {
                
                // save project languages
                $projectsLanguagesTable = new TableGateway('projects_languages', $dbAdapter);
                
                $projectsLanguagesTable->delete(array('id_project'=>$entity->id));
                
                foreach($entity->languages as $language)
                {
                    $projectsLanguagesTable->insert(array('id_project'=>$entity->id,'id_language'=>$language));
                }    
            }
            
            
            // save project channels
            $projectsChannelsTable = new TableGateway('projects_channels', $dbAdapter);
            
            $projectsChannelsTable->delete(array('id_project'=>$entity->id));
            
            foreach ($entity->projects_channels as $project_channel)
            {
                if (is_object($project_channel) && get_class($project_channel) == 'Application\Model\ProjectChannel')
                    $project_channel = $project_channel->getArrayCopy();

                if ($entity->id && $project_channel['id_channel'] && $project_channel['id_form'])
                {
                    $publicToken = md5($entity->id.$project_channel['id_channel'].$project_channel['id_form']);
                    $projectsChannelsTable->insert(array('id_project'=>$entity->id,'id_channel'=>$project_channel['id_channel'],'id_form'=>$project_channel['id_form'],'public_token'=>$publicToken));
                }
            }
            
            // complete wizard step
            $wizardTable = new WizardTable($this->tableGateway);
            $wizardTable->completeWizardStep('manage_project');
            
            $connection->commit();
            
            return $id;
            
        } catch (\Exception $e) {
            $connection->rollback();
            
            throw $e;
        }
    }

    public function delete($id)
    {
        $entity = $this->getById($id);
        $data = array(
        	'active' => $entity->active?'0':'1',
        );
        $this->tableGateway->update($data, array('id' => $id));
    }
    
    public function fetchAllProjectChannels($id)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('pc' => 'projects_channels'))
            ->join(array('c' => 'channels'), 'pc.id_channel=c.id', array('channel'=>'name'))
            ->join(array('f' => 'forms'), 'pc.id_form=f.id', array('form'=>'name'))
            ->where('pc.id_project=\''.$id.'\'')
            ->order(array('c.name ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $projects_channels = array();
        foreach ($results as $item)
        {
            $project_channel = new ProjectChannel();
            $project_channel->exchangeArray($item);
            
            array_push($projects_channels, $project_channel);
        }
        
        return $projects_channels;
    }

    public function fetchAllChannelProjects($idChannel)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('pc' => 'projects_channels'))
            ->join(array('p' => 'projects'), 'pc.id_project=p.id')
            ->join(array('f' => 'forms'), 'pc.id_form=f.id', array('form'=>'name'))
            ->where('p.active=1')
            ->where('pc.id_channel=\''.$idChannel.'\'')
            ->order(array('p.name ASC'));
    
        $selectString = $sql->getSqlStringForSqlObject($select);

        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $projects = array();
        foreach ($results as $item)
        {
            $project = new Project();
            $project->exchangeArray($item);
            
            array_push($projects, $project);
        }

        return $projects;
    }
    

    
    public function fetchProjectChannelsForms($id=NULL)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
                ->from(array('c' => 'channels'))
                ->columns(array('channel'=>'name','id_channel'=>'id'))
                ->join(array('pc' => 'projects_channels'), new Expression('pc.id_channel=c.id AND pc.id_project '.(is_null($id) ? ' IS NULL ' : ' = \''.$id.'\'')), array(),'left')
                ->join(array('f' => 'forms'), 'pc.id_form=f.id', array('form'=>'name','id_form'=>'id'),'left')
                ->order(array('c.name ASC'));
    
        $statement = $sql->prepareStatementForSqlObject($select);

        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new ProjectChannel());
        $results = $resultSetPrototype->initialize($results);
        
        $results->buffer();
    
        return $results;
    }
    
    public function getOptionsForSelect()
    {
        $auth = new AuthenticationService();
        
        $data  = $this->fetchAll(false, array('active'=>'1','organization'=>$auth->getIdentity()->id_organization));
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
    
    public function fetchProjectChannelsFormsByToken($token)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
        ->from(array('pc' => 'projects_channels'))
        ->columns(array('id_project','id_channel','id_form'))
        ->join(array('p'=>'projects'), 'pc.id_project=p.id', array('project'=>'name','id_organization','enable_public','require_public_names','public_description','id_locale', 'public_by_agents', 'form_selector_question', 'enable_form_selector', 'be_anonymous'))
        ->join(array('c'=>'channels'), 'pc.id_channel=c.id', array('channel'=>'name'))
        ->join(array('f' => 'forms'), 'pc.id_form=f.id', array('form'=>'name'))
        ->join(array('l' => 'locales'), 'p.id_locale=l.id', array('display_name','culture_name'), 'left')
        ->where(array('pc.public_token'=>$token));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results      = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        return $results->current();
    }
    
    public function fetchQaAgentProjectsForListening()
    {
        $auth = new AuthenticationService();
        
        $where = array();
        
        $where[] = "p.active = '1'";
        $where[] = "up.id_user='" . (int)$auth->getIdentity()->id . "'";
        $where[] = "up.active = '1'";
        $where[] = "pr.`key` IN ('auditor','manager')";
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        $select = $sql->select()
        ->from(array('p' => 'projects'))
        ->join(array('up'=>'users_projects'), 'p.id=up.id_project', array())
        ->join(array('pr' => 'projects_roles'), 'up.id_project_role=pr.id', array())
        ->where(!empty($where) ? implode(' AND ', $where) : 1)
        ->order('p.name ASC');
        
        $statement = $sql->prepareStatementForSqlObject($select);
/*
echo '<pre>';
print_r($sql->getSqlStringForSqlObject($select));
echo '</pre>';die;
*/
        $result = $statement->execute();
        
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new HydratingResultSet(new \Zend\Stdlib\Hydrator\ClassMethods(), new Project());
        
            return $resultSet->initialize($result);
        }
        
        return array();
    }   
}