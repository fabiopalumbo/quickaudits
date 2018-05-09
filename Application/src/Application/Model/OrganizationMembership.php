<?php
namespace Application\Model;

class OrganizationMembership
{
    public $id;
    public $id_organization;
    public $organization;
    public $id_membership;
    public $membership;
    public $id_plan;
    public $plan;
    public $package;
    public $active;
    public $module;
    public $upgrade;
    public $min_users;
    public $max_dashboard_reports;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
        $this->id_membership = (!empty($data['id_membership'])) ? $data['id_membership'] : null;
        $this->membership = (!empty($data['membership'])) ? $data['membership'] : null;
        $this->id_plan = (!empty($data['id_plan'])) ? $data['id_plan'] : null;
        $this->plan = (!empty($data['plan'])) ? $data['plan'] : null;
        $this->package = (!empty($data['package'])) ? $data['package'] : null;
        $this->active = (is_numeric($data['active'])) ? $data['active'] : null;
        $this->module = (!empty($data['module'])) ? $data['module'] : null;
        $this->upgrade = (!empty($data['upgrade'])) ? $data['upgrade'] : null;
        $this->min_users = (!empty($data['min_users'])) ? $data['min_users'] : null;
        $this->max_dashboard_reports = (!empty($data['max_dashboard_reports'])) ? $data['max_dashboard_reports'] : null;
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
     * @return the $id_organization
     */
    public function getId_organization()
    {
        return $this->id_organization;
    }

	/**
     * @return the $organization
     */
    public function getOrganization()
    {
        return $this->organization;
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
     * @return the $id_plan
     */
    public function getId_plan()
    {
        return $this->id_plan;
    }

	/**
     * @return the $plan
     */
    public function getPlan()
    {
        return $this->plan;
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
     * @param field_type $id_organization
     */
    public function setId_organization($id_organization)
    {
        $this->id_organization = $id_organization;
    }

	/**
     * @param field_type $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
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
     * @param field_type $id_plan
     */
    public function setId_plan($id_plan)
    {
        $this->id_plan = $id_plan;
    }

	/**
     * @param field_type $plan
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
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
     * @param Ambigous <NULL, unknown> $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

	/**
     * @param Ambigous <NULL, unknown> $upgrade
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
     * @param Ambigous <NULL, unknown> $min_users
     */
    public function setMin_users($min_users)
    {
        $this->min_users = $min_users;
    }
	/**
     * @return the $max_dashboard_reports
     */
    public function getMax_dashboard_reports()
    {
        return $this->max_dashboard_reports;
    }

	/**
     * @param field_type $max_dashboard_reports
     */
    public function setMax_dashboard_reports($max_dashboard_reports)
    {
        $this->max_dashboard_reports = $max_dashboard_reports;
    }
    
    public function hasAgents() {
        
        $data = array(
            'id' => $this->id_membership,
            'package' => $this->package,
        );
        $membership = new Membership();
        $membership->exchangeArray($data);
        
        return $membership->hasAgents();                
    }

}