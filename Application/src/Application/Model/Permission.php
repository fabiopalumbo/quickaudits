<?php
namespace Application\Model;

class Permission
{
    public $id;
    public $name;
    public $category;
    public $key;
    public $controller;
    public $action;
    public $checked;

    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->category = (!empty($data['category'])) ? $data['category'] : null;
        $this->key = (!empty($data['key'])) ? $data['key'] : null;
        $this->controller= (!empty($data['controller'])) ? $data['controller'] : null;
        $this->action = (!empty($data['action'])) ? $data['action'] : null;
        $this->checked = (!empty($data['action'])) ? $data['checked'] : null;
        
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
     * @return the $category
     */
    public function getCategory()
    {
        return $this->category;
    }

	/**
     * @return the $key
     */
    public function getKey()
    {
        return $this->key;
    }

	/**
     * @return the $controller
     */
    public function getController()
    {
        return $this->controller;
    }

	/**
     * @return the $action
     */
    public function getAction()
    {
        return $this->action;
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
     * @param Ambigous <NULL, unknown> $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

	/**
     * @param Ambigous <NULL, unknown> $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

	/**
     * @param Ambigous <NULL, unknown> $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

	/**
     * @param Ambigous <NULL, unknown> $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }
	/**
     * @return the $checked
     */
    public function getChecked()
    {
        return $this->checked;
    }

	/**
     * @param Ambigous <NULL, unknown> $checked
     */
    public function setChecked($checked)
    {
        $this->checked = $checked;
    }


}