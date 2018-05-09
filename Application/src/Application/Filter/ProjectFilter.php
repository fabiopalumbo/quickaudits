<?php
namespace Application\Filter;

use Zend\InputFilter\InputFilter;

class ProjectFilter extends InputFilter
{
    public function __construct()
    {
        $this->add(array(
            'name' => 'id',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'Int'
                )
            )
        ));
        
        $this->add(array(
            'name' => 'name',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'StripTags'
                ),
                array(
                    'name' => 'StringTrim'
                )
            ),
            'validators' => array(
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 100
                    )
                )
            )
        ));
        
        $this->add(array(
            'name' => 'min_performance_required',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'Int'
                )
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty'
                )
            )
        ));
        
        $this->add(array(
            'name' => 'projects_channels',
            'required' => false
        ));
        
        $this->add(array(
            'name' => 'public_description',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'StripTags'
                ),
                array(
                    'name' => 'StringTrim'
                )
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty'
                ),
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 1,
                        'max' => 255
                    )
                )
            )
        ));

        $this->add(array(
            'name' => 'public_by_agents',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'NotEmpty'
                ),
            )
        ));
        
        $this->add(array(
            'name' => 'enable_form_selector',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'NotEmpty'
                ),
            )
        ));
        
        $this->add(array(
            'name' => 'form_selector_question',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'StripTags'
                ),
                array(
                    'name' => 'StringTrim'
                )
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty'
                ),
                array(
                    'name' => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 10,
                        'max' => 255
                    )
                )
            )
        ));

        $this->add(array(
            'name' => 'id_locale',
            'required' => true,
            'allow_empty' => false
        ));
    }

    public function isValid($context = null)
    {
        if (!$this->get('enable_public')->getValue()) {
            $this->get('id_locale')->setRequired(false);
            $this->get('public_description')->setRequired(false);
            $this->get('public_description')->setRequired(false);
            $this->get('public_by_agents')->setRequired(false);
            $this->get('public_by_agents')->setAllowEmpty(true);

            $this->get('form_selector_question')->setRequired(false);
            $this->get('form_selector_question')->setAllowEmpty(true);

            $this->get('enable_form_selector')->setRequired(false);
            $this->get('enable_form_selector')->setAllowEmpty(true);

        } elseif(!$this->get('enable_form_selector')->getValue()) {
            $this->get('form_selector_question')->setRequired(false);
            $this->get('form_selector_question')->setAllowEmpty(true);

        }
        
        return parent::isValid($context);
    }

}