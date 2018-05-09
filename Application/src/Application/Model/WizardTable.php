<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;
use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Zend\Db\Sql\Expression;

class WizardTable
{
    /**
     * 
     * @var \Zend\Db\TableGateway\TableGateway
     */
    protected $tableGateway;

    /**
     * 
     * @param \Zend\Db\TableGateway\TableGateway $tableGateway
     */
    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getWizard($idMembership, $idRole, $idLocale)
    {
        $idMembership = (int) $idMembership;
        $idRole = (int) $idRole;
        $idLocale = (int) $idLocale;
        
        $auth = new AuthenticationService();
    
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
    
        $select = $sql->select()
            ->from(array('w'=>'wizards'))
            ->join(array('wl'=>'wizards_locales'), 'w.id=wl.id_wizard', array('title','description'))
            ->where(array('w.id_membership'=>$idMembership, 'w.id_role'=>$idRole, 'wl.id_locale'=>$idLocale));
    
        $selectString = $sql->getSqlStringForSqlObject($select);
    
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
    
        $row = $rowset->current();
    
        if (!$row) {
            return null;
        }
    
        $entity = new Wizard();
        $entity->exchangeArray($row);
        
        // get wizard steps for current user
        $filter = array();
        
        $filter[]="(ws.id_wizard='".$row->id."')";
        $filter[]="(wsl.id_locale='".$idLocale."')";
//         $filter[]="(uws.id_user='".$auth->getIdentity()->id."' OR uws.id_user IS NULL)";

        $select = $sql->select()
            ->from(array('ws' => 'wizards_steps'))
            ->join(array('wsl'=>'wizards_steps_locales'), 'ws.id=wsl.id_wizard_step', array('name'))
            ->join(array('uws'=>'users_wizards_steps'), new Expression('ws.id=uws.id_wizard_step AND (uws.id_user=\''.$auth->getIdentity()->id.'\' OR uws.id_user IS NULL)'), array('completed'), 'left')
            ->where(implode(' AND ', $filter))
            ->order('ws.display_order ASC');
        

        $statement = $sql->prepareStatementForSqlObject($select);        
        $results = $statement->execute();
        
        $resultSetPrototype = new HydratingResultSet();
        $resultSetPrototype->setHydrator(new ObjectProperty());
        $resultSetPrototype->setObjectPrototype(new WizardStep());
        $results = $resultSetPrototype->initialize($results);
        $results->buffer();
        $entity->steps = $results;        
    
        return $entity;
    }
    
    public function isWizardCompleted($idUser, $idWizard)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        $select = $sql->select()->from(array('uw'=>'users_wizards'))->where(array('uw.id_user'=>$idUser, 'uw.id_wizard'=>$idWizard));
        $selectString = $sql->getSqlStringForSqlObject($select);
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        return $rowset->count() > 0 && $rowset->current()->completed;
    }
    
    /**
     * 
     * @param int $idUser
     * @param \Application\Model\Wizard $wizard
     */
    public function completeWizard($idUser, $wizard)
    {
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        $select = $sql->select()->from(array('uw'=>'users_wizards'))->where(array('uw.id_user'=>$idUser, 'uw.id_wizard'=>$wizard->id));
        $selectString = $sql->getSqlStringForSqlObject($select);
        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $tableGateway = new TableGateway('users_wizards', $this->tableGateway->getAdapter());
        
        if($rowset->count() > 0)
        {
            $tableGateway->update(array('completed'=>'1'),array('id_user'=>$idUser,'id_wizard'=>$wizard->id));
        }
        else
        {
            $tableGateway->insert(array('id_user'=>$idUser,'id_wizard'=>$wizard->id,'completed'=>'1'));
        }
        
        foreach ($wizard->steps as $step)
        {
            /* @var $step \Application\Model\WizardStep */
            $this->completeWizardStep($step->key);
        }
    }
    
    public function completeWizardStep($key)
    {
        // get current user wizard
        $auth = new AuthenticationService();
        $session = new Container('role');
        
        $sql = new \Zend\Db\Sql\Sql($this->tableGateway->getAdapter());
        
        // get wizard step id
        $filter = array();
        $filter[]="(ws.key='$key')";
        $filter[]="(w.id_membership='".$session->role->membership->id_membership."')";
        $filter[]="(w.id_role='".$auth->getIdentity()->id_role."')";

        $select = $sql->select()
        ->from(array('ws' => 'wizards_steps'))
        ->join(array('w'=>'wizards'), 'ws.id_wizard=w.id', array())
        ->join(array('uws'=>'users_wizards_steps'), new Expression('ws.id=uws.id_wizard_step AND (uws.id_user=\''.$auth->getIdentity()->id.'\' OR uws.id_user IS NULL)'), array('completed','user_step_exists'=>new Expression('IF (uws.id_user IS NOT NULL,1,0)')), 'left')
        ->where(implode(' AND ', $filter));
        
        $selectString = $sql->getSqlStringForSqlObject($select);

        $rowset = $this->tableGateway->getAdapter()->query($selectString, \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        
        $row = $rowset->current();
        
        if ($row)
        {
            $wizardStep = new WizardStep();
            
            $wizardStep->exchangeArray($row);
            
            // if step is not complete
            if (!$wizardStep->completed)
            {
                $tableGateway = new TableGateway('users_wizards_steps', $this->tableGateway->getAdapter());

                if (!$row->user_step_exists)
                {
                    $tableGateway->insert(
                        array(
                            'id_user'=>$auth->getIdentity()->id,
                            'id_wizard_step'=>$wizardStep->id,
                            'completed'=>'1'
                        )
                    );
                }
                else 
                {
                    $tableGateway->update(
                        array(
                            'completed'=>'1'                            
                        ), 
                        array(
                            'id_user'=>$auth->getIdentity()->id,
                            'id_wizard_step'=>$wizardStep->id
                        )
                    );    
                }
            }    
        }
    }
    
    public function resetWizard()
    {
        $auth = new AuthenticationService();
        
        $tableGateway = new TableGateway('users_wizards_steps', $this->tableGateway->getAdapter());
        
        $tableGateway->update(array('completed'=>'0'),array('id_user'=>$auth->getIdentity()->id));
        
        $tableGateway = new TableGateway('users_wizards', $this->tableGateway->getAdapter());
        
        $tableGateway->update(array('completed'=>'0'),array('id_user'=>$auth->getIdentity()->id));
    }
    
}