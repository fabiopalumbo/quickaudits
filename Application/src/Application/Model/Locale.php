<?php
namespace Application\Model;

class Locale
{
    public $id;
    public $display_name;
    public $culture_name;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->display_name = (!empty($data['display_name'])) ? $data['display_name'] : null;
        $this->culture_name = (!empty($data['culture_name'])) ? $data['culture_name'] : null;
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
     * @return the $display_name
     */
    public function getDisplay_name()
    {
        return $this->display_name;
    }

	/**
     * @return the $culture_name
     */
    public function getCulture_name()
    {
        return $this->culture_name;
    }

	/**
     * @param Ambigous <NULL, unknown> $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

	/**
     * @param Ambigous <NULL, unknown> $display_name
     */
    public function setDisplay_name($display_name)
    {
        $this->display_name = $display_name;
    }

	/**
     * @param Ambigous <NULL, unknown> $culture_name
     */
    public function setCulture_name($culture_name)
    {
        $this->culture_name = $culture_name;
    }

	

}