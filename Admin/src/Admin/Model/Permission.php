<?php
namespace Admin\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Permission
{
    public $id;
    public $name;
    public $category;
    public $key;
    public $controller;
    public $action;

    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->category = (!empty($data['category'])) ? $data['category'] : null;
        $this->key = (!empty($data['key'])) ? $data['key'] : null;
        $this->controller= (!empty($data['controller'])) ? $data['controller'] : null;
        $this->action = (!empty($data['action'])) ? $data['action'] : null;
        
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}