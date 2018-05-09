<?php
namespace Basic\Filter;

use Zend\InputFilter\InputFilter;
use Basic\Form\ListeningAnswerFieldset;
use Basic\Form\ListeningGroupScoreFieldset;
use Zend\Session\Container;
use Zend\Authentication\AuthenticationService;

class ListeningFilter extends InputFilter
{
    public function __construct() {
        
        $this->add(array(
            'name'     => 'id',
            'required' => true,
            'filters'  => array(
                    array('name' => 'Int'),
            ),
        ));
    
        $this->add(array(
            'name'     => 'id_project',
            'required' => true,
            'allow_empty' => false,
        ));
        
        $this->add(array(
            'name'     => 'id_channel',
            'required' => true,
            'allow_empty' => false,
        ));
        
        $listeningAnswerFieldset = new ListeningAnswerFieldset();

        $this->add($listeningAnswerFieldset->getInputFilterSpecification());

        $ListeningGroupScoreFieldset = new ListeningGroupScoreFieldset();

        $this->add($ListeningGroupScoreFieldset->getInputFilterSpecification());


        $this->add(array(
            'name'     => 'id_agent',
            'required' => true,
            'allow_empty' => false,
        ));
    }
    
    public function isValid($context = null)
    {

        if (!$this->get('is_public_evaluation')->getValue()) {
            
            $session = new Container('role');
            
            if (!$session->role->membership->hasAgents()) {
                $this->get('id_agent')->setRequired(false);
            }    
            
        } else {
            
            // public evaluations
            if (!$this->get('public_by_agents')->getValue()) {
                $this->get('id_agent')->setRequired(false);
            }
        }

            $return = parent::isValid($context);

            if(!$return) {
            //    echo '<pre>';
            //    print_r(parent::getInvalidInput());
            //    echo '</pre>';die;
            }

        return $return;
    }
}