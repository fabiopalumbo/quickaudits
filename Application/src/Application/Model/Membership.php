<?php
namespace Application\Model;

class Membership
{
    public $id;
    public $name;
    public $package;
    public $module;
    public $upgrade;
    public $min_users;
    public $max_users;
    public $price_month;
    public $price_year;
    public $trial_days;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->package = (!empty($data['package'])) ? $data['package'] : null;
        $this->module = (!empty($data['module'])) ? $data['module'] : null;
        $this->upgrade = (!empty($data['upgrade'])) ? $data['upgrade'] : null;
        $this->min_users = (!empty($data['min_users'])) ? $data['min_users'] : null;
        $this->max_users = (!empty($data['max_users'])) ? $data['max_users'] : null;
        $this->price_month = (!empty($data['price_month'])) ? $data['price_month'] : null;
        $this->price_year = (!empty($data['price_year'])) ? $data['price_year'] : null;
        $this->trial_days = (!empty($data['trial_days'])) ? $data['trial_days'] : null;
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
     * @return the $package
     */
    public function getPackage()
    {
        return $this->package;
    }

	/**
     * @param Ambigous <NULL, unknown> $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }
	/**
     * @return the $module
     */
    public function getModule()
    {
        return $this->module;
    }

	/**
     * @return the $upgrade
     */
    public function getUpgrade()
    {
        return $this->upgrade;
    }

	/**
     * @param field_type $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

	/**
     * @param field_type $upgrade
     */
    public function setUpgrade($upgrade)
    {
        $this->upgrade = $upgrade;
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
	/**
     * @return the $trial_days
     */
    public function getTrial_days()
    {
        return $this->trial_days;
    }

	/**
     * @param Ambigous <NULL, unknown> $trial_days
     */
    public function setTrial_days($trial_days)
    {
        $this->trial_days = $trial_days;
    }

    public function hasAgents() {
        return $this->package == 'contact_center';
    }



}