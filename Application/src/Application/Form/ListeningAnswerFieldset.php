<?php
namespace Application\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Application\Model\ListeningAnswer;

class ListeningAnswerFieldset extends Fieldset implements InputFilterProviderInterface 
{
    public function __construct()
    {
        parent::__construct('listenings_answers');

        $this->setHydrator(new ClassMethodsHydrator(false))->setObject(new ListeningAnswer());

        $this->add(array(
            'name' => 'id_question',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'answer',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'disable_inarray_validator' => true
            )
        ));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'id_question' => array(
                'required' => true,
            ),
            'answer' => array(
                'required' => false,
                'allow_empty' => false,
            ),
        );
    }
}