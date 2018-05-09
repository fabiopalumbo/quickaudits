<?php
namespace Application\Model;

class MembershipPrice
{
    public $id;
    public $id_membership;
    public $min_users;
    public $max_users;
    public $price_month;
    public $price_year;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_membership = (!empty($data['id_membership'])) ? $data['id_membership'] : null;
        $this->min_users = (!empty($data['min_users'])) ? $data['min_users'] : null;
        $this->max_users = (!empty($data['max_users'])) ? $data['max_users'] : null;
        $this->price_month = (!empty($data['price_month'])) ? $data['price_month'] : null;
        $this->price_year = (!empty($data['price_year'])) ? $data['price_year'] : null;
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
     * @return the $id_membership
     */
    public function getId_membership()
    {
        return $this->id_membership;
    }

	/**
     * @param Ambigous <NULL, unknown> $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_membership
     */
    public function setId_membership($id_membership)
    {
        $this->id_membership = $id_membership;
    }
	/**
     * @return the $min_users
     */
    public function getMin_users()
    {
        return $this->min_users;
    }

	/**
     * @return the $max_users
     */
    public function getMax_users()
    {
        return $this->max_users;
    }

	/**
     * @return the $price_month
     */
    public function getPrice_month()
    {
        return $this->price_month;
    }

	/**
     * @return the $price_year
     */
    public function getPrice_year()
    {
        return $this->price_year;
    }

	/**
     * @param Ambigous <NULL, unknown> $min_users
     */
    public function setMin_users($min_users)
    {
        $this->min_users = $min_users;
    }

	/**
     * @param Ambigous <NULL, unknown> $max_users
     */
    public function setMax_users($max_users)
    {
        $this->max_users = $max_users;
    }

	/**
     * @param Ambigous <NULL, unknown> $price_month
     */
    public function setPrice_month($price_month)
    {
        $this->price_month = $price_month;
    }

	/**
     * @param Ambigous <NULL, unknown> $price_year
     */
    public function setPrice_year($price_year)
    {
        $this->price_year = $price_year;
    }



}