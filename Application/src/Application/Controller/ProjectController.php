<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Form\ProjectForm;
use Application\Model\Project;
use Application\Filter\ProjectFilter;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;

/**
 * ProjectController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class ProjectController extends AbstractActionController
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
            $this->currentTable = $sm->get('Application\Model\ProjectTable');
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
            'subtitle'=>$this->auth->getIdentity()->id_organization==177?$this->getTranslator()->translate('Stores List'):$this->getTranslator()->translate('Projects List'),
        ));
    }
    
    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $m = $this->params()->fromQuery('m');
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }

        $ret = array(
            'id'=>$id,
            'entity'=>$entity,
            'subtitle'=>$entity->id_organization==177?$this->getTranslator()->translate('Store Details'):$this->getTranslator()->translate('Project Details'),
            'm'=>$m,
            'users'=>[]
        );

        if($entity->public_by_agents) {
            $usersTable = $this->getServiceLocator()->get('Application\Model\UserTable');
            
            $ret['users'] = $usersTable->fetchAllProjectAgents($entity->id);
        }
        
        return $ret;
    }
    
    public function addAction()
    {        
        // validate if membership allows to create more items
        $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
        if ($this->session->role->membership->package=='basic' && $items->count()>=1)
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'), array('query' => array('m' => 10)));
        
        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $formTable = $this->getServiceLocator()->get('Application\Model\FormTable');
        $localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');
        
        $form = new ProjectForm($languageTable->fetchAll(), $formTable->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization)), $localeTable);
        
        $projectChannelsForms = $this->getCurrentTable()->fetchProjectChannelsForms(null);
        
        $form->populateValues(array('projects_channels'=>$projectChannelsForms->toArray()));
    
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
    
                $project = new Project();
                $form->setInputFilter(new ProjectFilter());
                $form->setData($request->getPost());
    
                if ($form->isValid()) {

                    $project->exchangeArray($form->getData());

                    $id = $this->getCurrentTable()->save($project);
    
                    return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'view', 'id' => $id), array('query'=>array('m'=>1)));
                }
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    
        return array(
            'form' => $form, 
            'error' => $error, 
            'subtitle'=>$this->getTranslator()->translate('Add new Project'),
        );
    }
    
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'add'));
        }
    
        // Get the entity with the specified id.  An exception is thrown
        // if it cannot be found, in which case go to the index page.
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
    
            if ($entity->blocked)
                return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'view','id'=>$id));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }

        $languageTable = $this->getServiceLocator()->get('Application\Model\LanguageTable');
        $formTable = $this->getServiceLocator()->get('Application\Model\FormTable');
        $localeTable = $this->getServiceLocator()->get('Application\Model\LocaleTable');

        $form = new ProjectForm($languageTable->fetchAll(), $formTable->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization)), $localeTable);

        $entity->languages = $entity->getLanguagesIds();
        
        $form->bind($entity);

//        echo '<pre>';
//        print_r($form->get('enable_form_selector')->getValue()?'ture':'false');
//        echo '</pre>';die;
        
        $request = $this->getRequest();
        if ($request->isPost()) {
    
            try {
                $form->setInputFilter(new ProjectFilter());
    
                $form->setData($request->getPost());
    
                if ($form->isValid()) {
    
                    $this->getCurrentTable()->save($entity);
                     
                    return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'view', 'id' => $id), array('query'=>array('m'=>1)));
                }
                
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    
        return array(
            'id' => $id,
            'form' => $form,
            'error' => $error,
            'subtitle'=>$this->getTranslator()->translate('Edit Project'),
        );
    }
    
    public function changeStatusAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }
        
        try {
            $entity = $this->getCurrentTable()->getById($id);
            
            if ($entity->id_organization != $this->auth->getIdentity()->id_organization)
                return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }
        catch (\Exception $ex) {
            return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'));
        }
        
        if (!$entity->active)
        {
            // validate if membership allows to create more items
            $items = $this->getCurrentTable()->fetchAll(false, array('active'=>'1','organization'=>$this->auth->getIdentity()->id_organization));
            if ($this->session->role->membership->package=='basic' && $items->count()>=1)
                return $this->redirect()->toRoute('application/default', array('controller'=>'project', 'action' => 'index'), array('query' => array('m' => 10)));
        }
        
        $request = $this->getRequest();
        
        if ($request->isPost()) {
            $this->getCurrentTable()->delete($id);
            
            return $this->redirect()->toRoute('application/default', array('controller'=>'project','action'=>'view','id'=>$id));
        }
        
        return array(
            'id'    => $id,
            'entity' => $entity,
            'subtitle'=>$this->getTranslator()->translate('Change Project Status'),
        );
    }
    
    public function renderQrCodeAction()
    {
        $url = base64_decode($this->params()->fromQuery('url'));

        $idAgent = $this->params()->fromQuery('ag');
        
        if($idAgent) {
            $url .= $idAgent;
        };

        $viewModel = new ViewModel(
            array(
                'url'=>$url
            )
        );
        $viewModel->setTerminal(true);
        
        return $viewModel;
    }

    public function renderQrCodesAction()
    {
        $token = $this->params()->fromQuery('token');
        
        $viewModel = new ViewModel(
            array(
                'url'=>base64_decode($url)
            )
        );
        $viewModel->setTerminal(true);
        
        return $viewModel;
    }

    public function getChannelProjectsAction()
    {
        try {
            
            $idChannel = (int) $this->params()->fromPost('id_channel', 0);
            
            $projects = $this->getCurrentTable()->fetchAllChannelProjects($idChannel);
            return new JsonModel(array('success'=>true,'projects'=>$projects));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }


}