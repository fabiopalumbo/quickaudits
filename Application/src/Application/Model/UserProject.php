<?php
namespace Application\Model;

class UserProject
{
    public $id_user;
    public $user;
    public $id_project;
    public $project;
    public $id_project_role;
    public $project_role;
    public $active;
    public $blocked;
    
    public function exchangeArray($data)
    {
        $this->id_user     = (!empty($data['id_user'])) ? $data['id_user'] : null;
        $this->user = (!empty($data['user'])) ? $data['user'] : null;
        $this->id_project     = (!empty($data['id_project'])) ? $data['id_project'] : null;
        $this->project = (!empty($data['project'])) ? $data['project'] : null;
        $this->id_project_role     = (!empty($data['id_project_role'])) ? $data['id_project_role'] : null;
        $this->project_role = (!empty($data['project_role'])) ? $data['project_role'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->blocked = (!empty($data['blocked'])) ? $data['blocked'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
	/**
     * @return the $id_user
     */
    public function getId_user()
    {
        return $this->id_user;
    }

	/**
     * @return the $user
     */
    public function getUser()
    {
        return $this->user;
    }

	/**
     * @return the $id_project
     */
    public function getId_project()
    {
        return $this->id_project;
    }

	/**
     * @return the $project
     */
    public function getProject()
    {
        return $this->project;
    }

	/**
     * @return the $id_project_role
     */
    public function getId_project_role()
    {
        return $this->id_project_role;
    }

	/**
     * @return the $project_role
     */
    public function getProject_role()
    {
        return $this->project_role;
    }

	/**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

	/**
     * @return the $blocked
     */
    public function getBlocked()
    {
        return $this->blocked;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_user
     */
    public function setId_user($id_user)
    {
        $this->id_user = $id_user;
    }

	/**
     * @param Ambigous <NULL, unknown> $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_project
     */
    public function setId_project($id_project)
    {
        $this->id_project = $id_project;
    }

	/**
     * @param Ambigous <NULL, unknown> $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_project_role
     */
    public function setId_project_role($id_project_role)
    {
        $this->id_project_role = $id_project_role;
    }

	/**
     * @param Ambigous <NULL, unknown> $project_role
     */
    public function setProject_role($project_role)
    {
        $this->project_role = $project_role;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

	/**
     * @param Ambigous <NULL, unknown> $blocked
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;
    }

	
}