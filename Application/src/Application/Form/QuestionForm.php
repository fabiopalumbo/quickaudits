<?php
namespace Application\Form;

use Zend\Form\Form;
use Zend\ServiceManager\ServiceLocatorInterface;

class QuestionForm extends Form
{
    protected $questionsGroups;
    
    /**
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;
    
    public function setServiceLocator(ServiceLocatorInterface $sl)
    {
        $this->serviceLocator = $sl;
        return $this;
    }
    
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    public function getTranslator()
    {
        return $this->getServiceLocator()->get('translator');
    }
    
    /**
     * 
     * @param unknown $sl
     * @param unknown $questionsGroups
     */
    public function __construct($sl, $questionsGroups)
    {
        // we want to ignore the name passed
        parent::__construct();
        
        $this->setServiceLocator($sl);
        
        $this->questionsGroups = $questionsGroups;

        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
                'name' => 'name',
                'type' => 'Text',
                'attributes' => array(
                        'class' => 'form-control',
//                         'placeholder' => 'Enter Question..'
                ),
        ));
        
        $this->add(array(
            'name' => 'id_group',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->getQuestionsGroupsOptions(),
                'empty_option' => $this->getTranslator()->translate('Please choose a Question Group'),
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));

        $this->add(array(
                'name' => 'submit',
                'type' => 'Submit',
                'attributes' => array(
                        'value' => $this->getTranslator()->translate('Save Changes'),
                        'class' => 'btn btn-sm btn-primary',
                ),
        ));
        
        $this->add(array(
            'name' => 'submitandadd',
            'type' => 'Submit',
            'attributes' => array(
                'value' => $this->getTranslator()->translate('Save Changes & Add New'),
                'class' => 'btn btn-sm btn-success',
            ),
        ));

        $this->add(array(
            'name' => 'type',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => [
                    'closed'=>$this->getTranslator()->translate('Closed'),
                    'inverted'=>$this->getTranslator()->translate('Inverted closed'),
                    'binary'=>$this->getTranslator()->translate('Binary'),
                    'options'=>$this->getTranslator()->translate('Options'),
                    'email'=>$this->getTranslator()->translate('Email'),
                    'text'=>$this->getTranslator()->translate('Free text'),
                    'date'=>$this->getTranslator()->translate('Date'),
                    'datetime'=>$this->getTranslator()->translate('Date & Time'),
                    'number'=>$this->getTranslator()->translate('Number'),
                ],
                'empty_option' => $this->getTranslator()->translate('Please choose a Question Type'),
            ),
            'attributes' => array(
                'class' => 'form-control',
            ),
        ));

        $this->add(array(
                'name' => 'options',
                'type' => 'Hidden',
                'attributes' => array(
                    'class' => 'form-control',
                ),
        ));

    }
    
    public function getQuestionsGroupsOptions()
    {
        $data = $this->questionsGroups;
    
        $selectData = array();
    
        foreach ($data as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }
    
//     /**
//      * Bind an object to the form
//      *
//      * Ensures the object is populated with validated values.
//      *
//      * @param  object $object
//      * @param  int $flags
//      * @return self
//      * @throws Exception\InvalidArgumentException
//      */
//     public function bind($object, $flags = FormInterface::VALUES_NORMALIZED)
//     {
//         parent::bind($object, $flags);
        
//         $this->bindValues(array('id_group'=>$object->question_group->id));
//     }
}