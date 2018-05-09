<?php
namespace Application\Model;

class Channel
{
    public $id;
    public $name;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
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

}