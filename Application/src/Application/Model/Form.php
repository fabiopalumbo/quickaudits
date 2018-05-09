<?php
namespace Application\Model;

class Form
{
    public $id;
    public $name;
    public $blocked;
    public $active;
    public $forms_questions;
    public $id_organization;
    public $organization;
    
	public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->blocked = (!empty($data['blocked'])) ? $data['blocked'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        
        $this->forms_questions = array();
        if (!empty($data['forms_questions']))
        {
            foreach ($data['forms_questions'] as $formQuestion)
            {
                if (is_array($formQuestion))
                {
                    $newFormQuestion = new FormQuestion();
                    $newFormQuestion->exchangeArray($formQuestion);
                    array_push($this->forms_questions, $newFormQuestion);
                }
                else 
                    array_push($this->forms_questions, $formQuestion);
            }
        }
        
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    
    /**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * @return the $blocked
     */
    public function getBlocked()
    {
        return $this->blocked;
    }
    
    /**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }
    
    /**
     * @return the $forms_questions
     */
    public function getForms_questions()
    {
        return $this->forms_questions;
    }
    
    /**
     * @param Ambigous <NULL, unknown> $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * @param Ambigous <NULL, unknown> $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * @param Ambigous <NULL, unknown> $blocked
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;
    }
    
    /**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }
    
    /**
     * @param multitype: $forms_questions
     */
    public function setForms_questions($forms_questions)
    {
        $this->forms_questions = $forms_questions;
    }
	/**
     * @return the $id_organization
     */
    public function getId_organization()
    {
        return $this->id_organization;
    }

	/**
     * @return the $organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_organization
     */
    public function setId_organization($id_organization)
    {
        $this->id_organization = $id_organization;
    }

	/**
     * @param Ambigous <NULL, unknown> $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

}