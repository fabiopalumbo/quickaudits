<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

/**
 * ChannelController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class ChannelController extends AbstractActionController
{
    protected $currentTable;
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\ChannelTable');
        }
        return $this->currentTable;
    }
    
    public function getProjectChannelsAction()
    {
        try {
            
            $idProject = (int) $this->params()->fromPost('id_project', 0);
            
            $channels = $this->getCurrentTable()->fetchAllProjectChannels($idProject);
            
            return new JsonModel(array('success'=>true,'channels'=>$channels->toArray()));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }

    public function getOrganizationChannelsAction()
    {
        try {
            
            $idOrganization = (int) $this->params()->fromPost('id_organization', 0);
           
            $channels = $this->getCurrentTable()->fetchAllOrganizationChannels($idOrganization);
          
            return new JsonModel(array('success'=>true,'channels'=>$channels->toArray()));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
}