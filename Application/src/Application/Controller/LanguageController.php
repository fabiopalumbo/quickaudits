<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

/**
 * LanguageController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class LanguageController extends AbstractActionController
{
    protected $currentTable;
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\LanguageTable');
        }
        return $this->currentTable;
    }
    
    public function getProjectLanguagesAction()
    {
        try {
            
            $idProject = (int) $this->params()->fromPost('id_project', 0);
            
            $languages = $this->getCurrentTable()->fetchAllProjectLanguages($idProject);
            
            return new JsonModel(array('success'=>true,'languages'=>$languages->toArray()));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
}