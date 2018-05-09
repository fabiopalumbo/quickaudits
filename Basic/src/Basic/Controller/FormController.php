<?php
namespace Basic\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;

/**
 * FormController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class FormController extends AbstractActionController
{
    protected $currentTable;
    protected $auth;
    protected $session;
    
    public function __construct(){
        $this->auth = new AuthenticationService();
        $this->session = new Container('role');
    }
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\FormTable');
        }
        return $this->currentTable;
    }
    
    public function getProjectChannelFormAction()
    {
        $error = '';

        try {
            $idProject = (int) $this->params()->fromPost('id_project', 0);
            $idChannel = (int) $this->params()->fromPost('id_channel', 0);

            $idAgent = (int) $this->params()->fromPost('id_agent', 0);
                    
            if ($idProject && $idChannel)
            {
                // get form for current convination
                $form = $this->getCurrentTable()->fetchProjectChannelForm($idProject, $idChannel);
    
                $results = $this->getCurrentTable()->fetchAllFormsQuestions(array('id_form'=>$form->id,'active'=>1));
                
                $groupsWeights = array();
                
                $questions = array();
                foreach ($results as $item)
                {
                    array_push($questions, $item);
                    
                    $groupsWeights[$item->id_group]+=$item->weight;
                }
            }

            $agent = ['name' => 'Anonymous'];

            if ($idAgent)
            {
                $agentsTable = $this->getServiceLocator()->get('Application\Model\UserTable');
                $agentObj = $agentsTable->getUser($idAgent);
                $agent['name'] = $agentObj->name;
            };
            
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        
        $viewModel = new ViewModel(array(
                'form' => $form,
                'forms_questions' => $questions,
                'groupsWeights' => $groupsWeights,
                'error' => $error,
                'agent' => $agent
        ));
        
        $viewModel->setTerminal(true);
        $viewModel->setTemplate('basic/listening/questions');

        return $viewModel;
    }
}