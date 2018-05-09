<?php
namespace Application\Model;

class Role
{
    public $id;
    public $name;
    public $permissions;
    public $priority;
    public $is_admin;
    public $is_default;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->permissions = (!empty($data['permissions'])) ? $data['permissions'] : array();
        $this->priority = (!empty($data['priority'])) ? $data['priority'] : null;
        $this->is_admin = (!empty($data['is_admin'])) ? $data['is_admin'] : null;
        $this->is_default = (!empty($data['is_default'])) ? $data['is_default'] : null;
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
     * @return the $permissions
     */
    public function getPermissions()
    {
        return $this->permissions;
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
     * @param Ambigous <multitype:, unknown> $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }
	/**
     * @return the $priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

	/**
     * @param field_type $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }
	/**
     * @return the $is_admin
     */
    public function getIs_admin()
    {
        return $this->is_admin;
    }

	/**
     * @param Ambigous <NULL, unknown> $is_admin
     */
    public function setIs_admin($is_admin)
    {
        $this->is_admin = $is_admin;
    }
	/**
     * @return the $is_default
     */
    public function getIs_default()
    {
        return $this->is_default;
    }

	/**
     * @param Ambigous <NULL, unknown> $is_default
     */
    public function setIs_default($is_default)
    {
        $this->is_default = $is_default;
    }




}