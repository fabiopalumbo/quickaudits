<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\Db\ResultSet\ResultSet;

class FormForm extends Form
{
    /**
     * 
     * @param ResultSet $questionsGroups
     * @param ResultSet $formsQuestions
     */
    public function __construct($questionsGroups, $formsQuestions)
    {
        // we want to ignore the name passed
        parent::__construct('form');
        
        $this->setAttribute('class', 'form-horizontal form-bordered');
        $this->setAttribute('id', 'form-builder');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
                'name' => 'name',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
//                         'placeholder' => 'Enter QA Form name..'
                ),
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Collection',
            'name' => 'questions_groups',
            'options' => array(
                'should_create_template' => false,
                'allow_add' => true,
                'target_element' => new FormQuestionGroupFieldset(),
            ),
        ));        
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Collection',
            'name' => 'forms_questions',
            'options' => array(
                'should_create_template' => false,
                'allow_add' => true,
                'allow_remove' => true,
                'target_element' => new FormQuestionFieldset(),
            ),
        ));
        
        $this->setData(array('questions_groups'=>$questionsGroups,'forms_questions'=>$formsQuestions));
    }
}