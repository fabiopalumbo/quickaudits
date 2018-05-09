<?php
namespace Application\Form;

use Zend\Form\Fieldset;
use Application\Model\QuestionGroup;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
// use Application\Model\ProjectChannel;
// use Application\Model\FormTable;

class FormQuestionGroupFieldset extends Fieldset implements InputFilterProviderInterface 
{
    
    public function __construct()
    {
        parent::__construct('form_question_group');

        $this->setHydrator(new ClassMethodsHydrator(false))->setObject(new QuestionGroup());

        $this->add(array(
            'name' => 'id',
            'type' => 'Zend\Form\Element\Hidden',
        ));
        
        $this->add(array(
            'name' => 'name',
            'type' => 'Zend\Form\Element\Hidden',
        ));
        
        $this->add(array(
            'name' => 'group',
            'type' => 'Zend\Form\Element\Checkbox',
            'options' => array(
                'use_hidden_element' => true,
            ),
        ));
        
        $this->add(array(
            'name' => 'group_weight',
//             'type' => 'Zend\Form\Element\Number',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control text-right group_weight positive-integer',
//                 'min' => 0,
//                 'max' => 100,
//                 'step' => 10,
                'value' => 0                
            ),
        ));
        
        $this->add(array(
            'name' => 'is_fatal',
            'type' => 'Zend\Form\Element\Hidden',
        ));

        $this->add(array(
            'name' => 'ml_fatal',
            'type' => 'Zend\Form\Element\Hidden',
        ));
    }
    
    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return array(
            'group' => array(
                'required' => false,
            ),
            'group_weight' => array(
                'required' => false,
            ),
        );
    }
}