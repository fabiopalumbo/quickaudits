<?php
namespace Application\Model;

class Organization
{
    public $id;
    public $name;
    public $firstname;
    public $lastname;
    public $phone;
    public $email;
    public $active;
    public $created;
    public $id_membership;
    public $membership;
    public $trial_days;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->firstname = (!empty($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname = (!empty($data['lastname'])) ? $data['lastname'] : null;
        $this->phone = (!empty($data['phone'])) ? $data['phone'] : null;
        $this->email = (!empty($data['email'])) ? $data['email'] : null;
        $this->active = (is_numeric($data['active'])) ? $data['active'] : null;
        $this->created = (!empty($data['created'])) ? $data['created'] : null;
        $this->id_membership = (is_numeric($data['id_membership'])) ? $data['id_membership'] : null;
        $this->membership = (!empty($data['membership'])) ? $data['membership'] : null;
        $this->trial_days = (is_numeric($data['trial_days'])) ? $data['trial_days'] : null;
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
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
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
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }
	/**
     * @return the $firstname
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

	/**
     * @return the $lastname
     */
    public function getLastname()
    {
        return $this->lastname;
    }

	/**
     * @return the $phone
     */
    public function getPhone()
    {
        return $this->phone;
    }

	/**
     * @return the $email
     */
    public function getEmail()
    {
        return $this->email;
    }

	/**
     * @param Ambigous <NULL, unknown> $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

	/**
     * @param Ambigous <NULL, unknown> $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

	/**
     * @param Ambigous <NULL, unknown> $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

	/**
     * @param Ambigous <NULL, unknown> $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
	/**
     * @return the $created
     */
    public function getCreated()
    {
        return $this->created;
    }

	/**
     * @param Ambigous <NULL, unknown> $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }
	/**
     * @return the $id_membership
     */
    public function getId_membership()
    {
        return $this->id_membership;
    }

	/**
     * @return the $membership
     */
    public function getMembership()
    {
        return $this->membership;
    }

	/**
     * @return the $trial_days
     */
    public function getTrial_days()
    {
        return $this->trial_days;
    }

	/**
     * @param field_type $id_membership
     */
    public function setId_membership($id_membership)
    {
        $this->id_membership = $id_membership;
    }

	/**
     * @param field_type $membership
     */
    public function setMembership($membership)
    {
        $this->membership = $membership;
    }

	/**
     * @param field_type $trial_days
     */
    public function setTrial_days($trial_days)
    {
        $this->trial_days = $trial_days;
    }
}