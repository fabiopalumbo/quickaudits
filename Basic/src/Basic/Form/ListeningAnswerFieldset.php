<?php
namespace Basic\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Basic\Model\ListeningAnswer;

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
            'type' => 'Zend\Form\Element\Radio',
            'options' => array(
                'disable_inarray_validator' => true
            )
        ));
        
        $this->add(array(
            'name' => 'answers',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'weight',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'weight_percentage',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'is_fatal',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'ml_fatal',
            'type' => 'Hidden',
        ));

        $this->add(array(
            'name' => 'free_answer',
        ));
        
        $this->add(array(
            'name' => 'free_answers',
            'type' => 'Hidden',
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
                'required' => true,
                'allow_empty' => false,
            )
        );
    }
}