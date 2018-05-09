<?php
namespace Application\Model;

class State
{
    public $id;
    public $name;
    public $id_country;
    public $country;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->id_country     = (!empty($data['id_country'])) ? $data['id_country'] : null;
        $this->country = (!empty($data['country'])) ? $data['country'] : null;
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
     * @return the $id_country
     */
    public function getId_country()
    {
        return $this->id_country;
    }

	/**
     * @return the $country
     */
    public function getCountry()
    {
        return $this->country;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_country
     */
    public function setId_country($id_country)
    {
        $this->id_country = $id_country;
    }

	/**
     * @param Ambigous <NULL, unknown> $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }


}