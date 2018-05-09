<?php
namespace Basic\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Basic\Model\ListeningGroupScore;

class ListeningGroupScoreFieldset extends Fieldset implements InputFilterProviderInterface 
{
    public function __construct()
    {
        parent::__construct('listenings_group_scores');

        $this->setHydrator(new ClassMethodsHydrator(false))->setObject(new ListeningGroupScore());

        $this->add(array(
            'name' => 'id_question_group',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'score',
            'type' => 'Hidden'
        ));
        
        $this->add(array(
            'name' => 'weight',
            'type' => 'Hidden',
        ));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'id_question_group' => array(
                'required' => true,
            )
        );
    }
}