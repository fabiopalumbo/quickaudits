<?php
namespace Admin\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Role
{
    public $id;
    public $name;
    public $permissions;
    public $active;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->permissions = (!empty($data['permissions'])) ? $data['permissions'] : array();
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}