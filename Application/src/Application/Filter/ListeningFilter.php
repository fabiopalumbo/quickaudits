<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;
use Application\Form\ListeningAnswerFieldset;

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
        
        $this->add(array(
            'name'     => 'id_agent',
            'required' => true,
            'allow_empty' => false,
        ));
        
        $this->add(array(
            'name'     => 'id_language',
            'required' => true,
            'allow_empty' => false,
        ));
        
        $listeningAnswerFieldset = new ListeningAnswerFieldset();
        $this->add($listeningAnswerFieldset->getInputFilterSpecification());
    }
}