<?php
namespace Application\Model;

class Wizard
{
    public $id;
    public $id_membership;
    public $membership;
    public $id_role;
    public $role;
    public $active;
    public $title;
    public $description;
    public $steps;
    
    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_membership = (!empty($data['id_membership'])) ? $data['id_membership'] : null;
        $this->membership = (!empty($data['membership'])) ? $data['membership'] : null;
        $this->id_role = (!empty($data['id_role'])) ? $data['id_role'] : null;
        $this->role = (!empty($data['role'])) ? $data['role'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->title = (!empty($data['title'])) ? $data['title'] : null;
        $this->description = (!empty($data['description'])) ? $data['description'] : null;
        $this->steps = (!empty($data['steps'])) ? $data['steps'] : null;
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
     * @return the $membership
     */
    public function getMembership()
    {
        return $this->membership;
    }

	/**
     * @return the $id_role
     */
    public function getId_role()
    {
        return $this->id_role;
    }

	/**
     * @return the $role
     */
    public function getRole()
    {
        return $this->role;
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
     * @param Ambigous <NULL, unknown> $id_membership
     */
    public function setId_membership($id_membership)
    {
        $this->id_membership = $id_membership;
    }

	/**
     * @param Ambigous <NULL, unknown> $membership
     */
    public function setMembership($membership)
    {
        $this->membership = $membership;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_role
     */
    public function setId_role($id_role)
    {
        $this->id_role = $id_role;
    }

	/**
     * @param Ambigous <NULL, unknown> $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }
	/**
     * @return the $title
     */
    public function getTitle()
    {
        return $this->title;
    }

	/**
     * @return the $description
     */
    public function getDescription()
    {
        return $this->description;
    }

	/**
     * @param Ambigous <NULL, unknown> $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

	/**
     * @param Ambigous <NULL, unknown> $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
	/**
     * @return the $steps
     */
    public function getSteps()
    {
        return $this->steps;
    }

	/**
     * @param Ambigous <NULL, unknown> $steps
     */
    public function setSteps($steps)
    {
        $this->steps = $steps;
    }
    
    public function getStepByKey($key)
    {
        $step = null;
        foreach ($this->steps as $item)
        {
            if ($item->key==$key)
            {
                $step=$item;
                break;
            }   
        }
        return $step;
    }
    
    public function areStepsCompleted()
    {
        $completed=true;
        foreach ($this->steps as $item)
        {
            if (!$item->completed)
                $completed=false;
        }
        return $completed;
    }
	
}