<?php
namespace Application\Model;

class QuestionGroup
{
    public $id;
    public $name;
    public $order;
    public $is_fatal;
    public $ml_fatal;
    public $active;
    public $blocked;
    public $id_organization;
    public $organization;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->order = (!empty($data['order'])) ? $data['order'] : null;
        $this->is_fatal = (is_numeric($data['is_fatal'])) ? $data['is_fatal'] : null;
        $this->ml_fatal = (is_numeric($data['ml_fatal'])) ? $data['ml_fatal'] : null;
        $this->active = (is_numeric($data['active'])) ? $data['active'] : null;
        $this->blocked = (is_numeric($data['blocked'])) ? $data['blocked'] : null;
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
     * @return the $order
     */
    public function getOrder()
    {
        return $this->order;
    }

	/**
     * @return the $is_fatal
     */
    public function getIs_fatal()
    {
        return $this->is_fatal;
    }
    public function getMl_fatal()
    {
        return $this->ml_fatal;
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
     * @param Ambigous <NULL, unknown> $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

	/**
     * @param Ambigous <NULL, unknown> $is_fatal
     */
    public function setIs_fatal($is_fatal)
    {
        $this->is_fatal = $is_fatal;
    }

    public function setMl_fatal($ml_fatal)
    {
        $this->ml_fatal = $ml_fatal;
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