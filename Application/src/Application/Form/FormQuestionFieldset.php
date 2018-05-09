<?php
namespace Application\Form;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Application\Model\FormQuestion;

class FormQuestionFieldset extends Fieldset implements InputFilterProviderInterface 
{
    public function __construct()
    {
        parent::__construct('forms_questions');

        $this->setHydrator(new ClassMethodsHydrator(false))->setObject(new FormQuestion());

        $this->add(array(
            'name' => 'id_question',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'question',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'question_checked',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'use_hidden_element' => true,
            ),
        ));
        
        $this->add(array(
            'name' => 'id_group',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'question_group',
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
            'type' => 'Zend\Form\Element\Text',
             'name' => 'answers',
             'options' => array(
                     'label' => 'Answers'
             ),
             'attributes' => array(
                 'class' => 'form-control text-right answers positive-integer',
                 'value' => 0
             )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Hidden',
            'name' => 'weight',
            'attributes' => array(
                'value' => 0
            )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Text',
            'name' => 'weight_percentage',
            'options' => array(
                'label' => 'Weight Percentage'
            ),
            'attributes' => array(
                'class' => 'form-control text-right weight-percentage positive',
                'value' => 0
            )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Number',
            'name' => 'order',
            'options' => array(
                'label' => 'Order'
            ),
            'attributes' => array(
                'min' => '0',
                'step' => '1',
                'value' => 0
            )
        ));

        //KHB - Agregado en tarea NA
        $this->add(array(
            'name' => 'allow_na',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'use_hidden_element' => true,
                'label' => 'Allow NA'
            ),
        ));

        $this->add(array(
            'name' => 'question_type',
            'type' => 'Zend\Form\Element\Hidden',
            'options' => array(
                'value' => '0'
            ),
        ));

        $this->add(array(
            'name' => 'question_options',
            'type' => 'Zend\Form\Element\Hidden',
            'options' => array(
                'value' => ''
            ),
        ));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'order' => array(
                'required' => false,
            ),
            'weight' => array(
                'required' => false,
//                 'filters'  => array(
//                     array('name' => 'Int'),
//                 ),
            ),
            'answers' => array(
                'required' => false,
            ),
            'weight_percentage' => array(
                'required' => false,
            ),
            'question_checked' => array(
                'required' => false,
            ),
        );
    }
}