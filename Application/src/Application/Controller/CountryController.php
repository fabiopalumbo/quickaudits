<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

/**
 * CountryController
 *
 * @author Gerardo Grinman <ggrinman@clickwayit.com>
 *
 * @version
 *
 */
class CountryController extends AbstractActionController
{
    protected $currentTable;
    
    public function getCurrentTable()
    {
        if (!$this->currentTable) {
            $sm = $this->getServiceLocator();
            $this->currentTable = $sm->get('Application\Model\CountryTable');
        }
        return $this->currentTable;
    }
    
    public function getCountryStatesAction()
    {
        try {
            
            $id = (int) $this->params()->fromPost('id', 0);
            
            $stateTable = $this->getServiceLocator()->get('Application\Model\StateTable');
            $states = $stateTable->fetchAll(false, array('id_country'=>$id));
            
            return new JsonModel(array('success'=>true,'states'=>$states->toArray()));
            
        } catch (\Exception $e) {
            return new JsonModel(array('success'=>false,'message'=>$e->getMessage()));
        }
    }
}