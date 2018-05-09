<?php
namespace Application\Model;

class Question
{
    public $id;
    public $name;
    public $active;
    public $blocked;
    public $id_organization;
    public $organization;
    public $id_group;
    public $question_group;
    public $type;
    public $options;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->active = (is_numeric($data['active'])) ? $data['active'] : null;
        $this->blocked = (is_numeric($data['blocked'])) ? $data['blocked'] : null;
        $this->id_group = (!empty($data['id_group'])) ? $data['id_group'] : null;
        $this->question_group = (!empty($data['question_group'])) ? $data['question_group'] : null;
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
        $this->type = (!empty($data['type'])) ? $data['type'] : null;
        $this->options = (!empty($data['options'])) ? $data['options'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    /**
     * @return the $options
     */
    public function getOptions()
    {
        return $this->options;
    }
    /**
     * @return the $type
     */
    public function getType()
    {
        return $this->type;
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
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return the $blocked
     */
    public function getBlocked()
    {
        return $this->blocked;
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
     * @param Ambigous <NULL, unknown> $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @param Ambigous <NULL, unknown> $type
     */
    public function setType($type)
    {
        $this->type = $type;
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
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @param Ambigous <NULL, unknown> $blocked
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;
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

    /**
     * @return the $id_group
     */
    public function getId_group()
    {
        return $this->id_group;
    }

    /**
     * @return the $question_group
     */
    public function getQuestion_group()
    {
        return $this->question_group;
    }

    /**
     * @param Ambigous <NULL, unknown> $id_group
     */
    public function setId_group($id_group)
    {
        $this->id_group = $id_group;
    }

    /**
     * @param Ambigous <NULL, unknown> $question_group
     */
    public function setQuestion_group($question_group)
    {
        $this->question_group = $question_group;
    }

}