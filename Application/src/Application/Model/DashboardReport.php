<?php
namespace Application\Model;

class DashboardReport
{
    public $id;
    public $name;
    public $description;
    public $action;
    public $active;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->description = (!empty($data['description'])) ? $data['description'] : null;
        $this->action = (!empty($data['action'])) ? $data['action'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
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
     * @return the $description
     */
    public function getDescription()
    {
        return $this->description;
    }

	/**
     * @return the $action
     */
    public function getAction()
    {
        return $this->action;
    }

	/**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

	/**
     * @param Ambigous <NULL, unknown> $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

	/**
     * @param Ambigous <NULL, unknown> $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }


}