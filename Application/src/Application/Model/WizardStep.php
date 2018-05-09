<?php
namespace Application\Model;

class WizardStep
{
    public $id;
    public $id_wizard;
    public $name;
    public $module;
    public $controller;
    public $action;
    public $key;
    public $completed;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_wizard = (!empty($data['id_wizard'])) ? $data['id_wizard'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->module = (!empty($data['module'])) ? $data['module'] : null;
        $this->controller = (!empty($data['controller'])) ? $data['controller'] : null;
        $this->action = (!empty($data['action'])) ? $data['action'] : null;
        $this->key = (!empty($data['key'])) ? $data['key'] : null;
        $this->completed = (!empty($data['completed'])) ? $data['completed'] : null;
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
     * @return the $key
     */
    public function getKey()
    {
        return $this->key;
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
     * @param Ambigous <NULL, unknown> $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
	/**
     * @return the $completed
     */
    public function getCompleted()
    {
        return $this->completed;
    }

	/**
     * @param Ambigous <NULL, unknown> $completed
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }
	/**
     * @return the $id_wizard
     */
    public function getId_wizard()
    {
        return $this->id_wizard;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_wizard
     */
    public function setId_wizard($id_wizard)
    {
        $this->id_wizard = $id_wizard;
    }
	/**
     * @return the $module
     */
    public function getModule()
    {
        return $this->module;
    }

	/**
     * @param Ambigous <NULL, unknown> $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }





    
}