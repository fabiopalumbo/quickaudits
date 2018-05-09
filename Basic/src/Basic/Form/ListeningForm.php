<?php
namespace Basic\Form;

use Zend\Form\Form;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;
use Zend\Authentication\AuthenticationService;

class ListeningForm extends Form
{
    private $projects;
    private $session;
    
    public function __construct(ServiceLocatorInterface $sl)
    {
        // we want to ignore the name passed
        parent::__construct('listening');
        
        $this->session = new Container('role');
        
        $this->setAttribute('class', 'form-horizontal form-bordered');
        
        $this->add(array(
                'name' => 'id',
                'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'id_form',
            'type' => 'Hidden',
        ));
        
        $this->add(array(
            'name' => 'score',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'score',
            )
        ));
        
        $projectTable = $sl->get('Application\Model\ProjectTable');
        $this->projects = $projectTable->fetchQaAgentProjectsForListening();
        

        $this->add(array(
            'name' => 'id_project',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control select-chosen',
            ),
            'options' => array(
                'label' => 'Project',
                'value_options' => $this->getProjectOptions(),
                'disable_inarray_validator' => true,
            ),
        ));
        
        $this->add(array(
            'name' => 'id_channel',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'form-control select-chosen',
            ),
            'options' => array(
                'label' => $sl->get('translator')->translate('Channel'),
                'empty_option' => $sl->get('translator')->translate('Choose a Channel'),
                'disable_inarray_validator' => true,
            ),
        ));
        
        $auth = new AuthenticationService();
        
        if ($auth->hasIdentity()) {
            $membershipTable = $sl->get('Application\Model\MembershipTable');
            $hasAgents = $membershipTable->hasAgents($this->session->role->membership->id_membership);
            
            if ($hasAgents) {
                $this->add(array(
                    'name' => 'id_agent',
                    'type' => 'Zend\Form\Element\Select',
                    'attributes' => array(
                        'class' => 'form-control select-chosen',
                    ),
                    'options' => array(
                        'label' => $sl->get('translator')->translate('Agent'),
                        'empty_option' => $sl->get('translator')->translate('Choose an Agent'),
                        'disable_inarray_validator' => true,
                    ),
                ));
            }    
        }
        
        $this->add(array(
            'name' => 'comments',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class' => 'form-control',
                'rows' => 5,
            )
        ));

	        $this->add(array(
            'name' => 'case',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class' => 'form-control',
                'rows' => 1,
            )
        ));

        $this->add(array(
            'name' => 'teamlead',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class' => 'form-control',
                'rows' => 1,
            )
        ));

        $this->add(array(
            'name' => 'incident',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'class' => 'form-control',
                'rows' => 1,
            )
        ));


	$this->add(array(
        'type' => 'Zend\Form\Element\Radio',
        'name' => 'pnorte_radio1',
        'options' => array(
            'label' => 'How did know the Hotel?',
            'value_options' => array(
                '0' => 'Recommendation',
                '1' => 'Online travel agencies',
                '2' => 'Social Media',
                '3' => 'Press',
                '4' => 'Other',
            ),
            'disable_inarray_validator' => true,
        )
    ));

	$this->add(array(
        'type' => 'Zend\Form\Element\Radio',
        'name' => 'pnorte_radio2',
        'options' => array(
            'label' => 'How did you make your reservation?',
            'value_options' => array(
                 '0' => 'Email',
                 '1' => 'Telephone',
                 '2' => 'Hotel Web',
                 '3' => 'Company-Travel Agency',
                 '4' => 'Online travel agency',
            ),
            'disable_inarray_validator' => true,
        )
     ));


        $this->add(array(
            'name' => 'pnorte_room',
            'type' => 'Zend\Form\Element\Text',
            'required'=>false,'allowEmpty'=>true,
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));
        $this->add(array(
            'name' => 'pnorte_name',
            'required'=>false,'allowEmpty'=>true,
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_company',
            'required'=>false,'allowEmpty'=>true,
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_city',
            'required'=>false,'allowEmpty'=>true,
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_recommend',
            'required'=>false,'allowEmpty'=>true,
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_country',
            'required'=>false,'allowEmpty'=>true,
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_arrival',
            'type' => 'Zend\Form\Element\Text',
            'required'=>false,'allowEmpty'=>true,
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_departure',
            'type' => 'Zend\Form\Element\Text',
            'required'=>false,'allowEmpty'=>true,
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));

        $this->add(array(
            'name' => 'pnorte_email',
            'type' => 'Zend\Form\Element\Text',
            'required'=>false,'allowEmpty'=>true,
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
		    )
        ));
        
        $this->add(array(
            'name' => 'qa_agent_fullname',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'class' => 'form-control',
                'maxlength' => 100,
            )
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Collection',
            'name' => 'listenings_answers',
            'options' => array(
                'should_create_template' => false,
                'allow_add' => true,
                'target_element' => new ListeningAnswerFieldset(),
            ),
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Collection',
            'name' => 'listenings_group_scores',
            'options' => array(
                'should_create_template' => false,
                'allow_add' => true,
                'target_element' => new ListeningGroupScoreFieldset(),
            ),
        ));
        
        $this->add(array(
            'type' => 'Hidden',
            'name' => 'is_public_evaluation',
        ));

        $this->add(array(
            'type' => 'Hidden',
            'name' => 'public_by_agents',
        ));
   
    }
    
    public function getProjectOptions()
    {
        $selectData = array();
    
        foreach ($this->projects as $selectOption) {
            $selectData[$selectOption->id] = $selectOption->name;
        }
    
        return $selectData;
    }


}