<?php
namespace Application\Model;

class OrganizationSubscription
{
    public $id;
    public $id_organization;
    public $max_users;
    public $start_date;
    public $end_date;
    public $billing_period;
    public $unit_price;
    public $total_price;
    public $last_billing_date;
    public $next_billing_date;
    public $active;
    public $created;
    public $created_by;
    public $modified;
    public $modified_by;
    public $id_membership;
    public $membership;
    public $in_trial;
    public $trial_days;
    
    /**
     * 
     * @var Application\Model\OrganizationBillingDetail
     */
    public $billingDetails;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->max_users = (!empty($data['max_users'])) ? $data['max_users'] : null;
        $this->start_date = (!empty($data['start_date'])) ? $data['start_date'] : null;
        $this->end_date = (!empty($data['end_date'])) ? $data['end_date'] : null;
        $this->billing_period = (!empty($data['billing_period'])) ? $data['billing_period'] : null;
        $this->unit_price = (!empty($data['unit_price'])) ? $data['unit_price'] : null;
        $this->total_price = (!empty($data['total_price'])) ? $data['total_price'] : null;
        $this->last_billing_date = (!empty($data['last_billing_date'])) ? $data['last_billing_date'] : null;
        $this->next_billing_date = (!empty($data['next_billing_date'])) ? $data['next_billing_date'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->created = (!empty($data['created'])) ? $data['created'] : null;
        $this->created_by = (!empty($data['created_by'])) ? $data['created_by'] : null;
        $this->modified = (!empty($data['modified'])) ? $data['modified'] : null;
        $this->modified_by = (!empty($data['modified_by'])) ? $data['modified_by'] : null;
        $this->id_membership = (!empty($data['id_membership'])) ? $data['id_membership'] : null;
        $this->membership = (!empty($data['membership'])) ? $data['membership'] : null;
        $this->in_trial = (is_numeric($data['in_trial'])) ? $data['in_trial'] : null;
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
     * @return the $id_organization
     */
    public function getId_organization()
    {
        return $this->id_organization;
    }

	/**
     * @return the $max_users
     */
    public function getMax_users()
    {
        return $this->max_users;
    }

	/**
     * @return the $start_date
     */
    public function getStart_date()
    {
        return $this->start_date;
    }

	/**
     * @return the $end_date
     */
    public function getEnd_date()
    {
        return $this->end_date;
    }

	/**
     * @return the $billing_period
     */
    public function getBilling_period()
    {
        return $this->billing_period;
    }

	/**
     * @return the $unit_price
     */
    public function getUnit_price()
    {
        return $this->unit_price;
    }

	/**
     * @return the $total_price
     */
    public function getTotal_price()
    {
        return $this->total_price;
    }

	/**
     * @return the $last_billing_date
     */
    public function getLast_billing_date()
    {
        return $this->last_billing_date;
    }

	/**
     * @return the $next_billing_date
     */
    public function getNext_billing_date()
    {
        return $this->next_billing_date;
    }

	/**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

	/**
     * @return the $created
     */
    public function getCreated()
    {
        return $this->created;
    }

	/**
     * @return the $created_by
     */
    public function getCreated_by()
    {
        return $this->created_by;
    }

	/**
     * @return the $modified
     */
    public function getModified()
    {
        return $this->modified;
    }

	/**
     * @return the $modified_by
     */
    public function getModified_by()
    {
        return $this->modified_by;
    }

	/**
     * @param Ambigous <NULL, unknown> $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_organization
     */
    public function setId_organization($id_organization)
    {
        $this->id_organization = $id_organization;
    }

	/**
     * @param Ambigous <NULL, unknown> $max_users
     */
    public function setMax_users($max_users)
    {
        $this->max_users = $max_users;
    }

	/**
     * @param Ambigous <NULL, unknown> $start_date
     */
    public function setStart_date($start_date)
    {
        $this->start_date = $start_date;
    }

	/**
     * @param Ambigous <NULL, unknown> $end_date
     */
    public function setEnd_date($end_date)
    {
        $this->end_date = $end_date;
    }

	/**
     * @param Ambigous <NULL, unknown> $billing_period
     */
    public function setBilling_period($billing_period)
    {
        $this->billing_period = $billing_period;
    }

	/**
     * @param Ambigous <NULL, unknown> $unit_price
     */
    public function setUnit_price($unit_price)
    {
        $this->unit_price = $unit_price;
    }

	/**
     * @param Ambigous <NULL, unknown> $total_price
     */
    public function setTotal_price($total_price)
    {
        $this->total_price = $total_price;
    }

	/**
     * @param Ambigous <NULL, unknown> $last_billing_date
     */
    public function setLast_billing_date($last_billing_date)
    {
        $this->last_billing_date = $last_billing_date;
    }

	/**
     * @param Ambigous <NULL, unknown> $next_billing_date
     */
    public function setNext_billing_date($next_billing_date)
    {
        $this->next_billing_date = $next_billing_date;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

	/**
     * @param Ambigous <NULL, unknown> $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

	/**
     * @param Ambigous <NULL, unknown> $created_by
     */
    public function setCreated_by($created_by)
    {
        $this->created_by = $created_by;
    }

	/**
     * @param Ambigous <NULL, unknown> $modified
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    }

	/**
     * @param Ambigous <NULL, unknown> $modified_by
     */
    public function setModified_by($modified_by)
    {
        $this->modified_by = $modified_by;
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
	 * 
	 * @return \Application\Model\OrganizationBillingDetail
	 */
    public function getBillingDetails()
    {
        if (!$this->billingDetails)
            $this->billingDetails = new OrganizationBillingDetail();
        return $this->billingDetails;
    }

	/**
     * @param \Application\Model\Application\Model\OrganizationBillingDetail $billingDetails
     */
    public function setBillingDetails($billingDetails)
    {
        $this->billingDetails = $billingDetails;
    }


    public function getRemainingDays()
    {
        if (!$this->end_date)
            return null;
        
        $interval = date_diff(date_create(date('Y-m-d')), date_create($this->end_date));
        
        return $interval->days;
    }
    
	/**
     * @return the $in_trial
     */
    public function getIn_trial()
    {
        return $this->in_trial;
    }

	/**
     * @param Ambigous <NULL, unknown> $in_trial
     */
    public function setIn_trial($in_trial)
    {
        $this->in_trial = $in_trial;
    }

    public function getTrialExpirationDate() {
        return date_format(date_add(date_create($this->created), date_interval_create_from_date_string($this->trial_days.' days')), 'Y-m-d');
    }

    public function isTrialExpired()
    {
        return $this->in_trial && strtotime(date('Y-m-d')) > strtotime($this->getTrialExpirationDate());
    }
	/**
     * @return the $trial_days
     */
    public function getTrial_days()
    {
        return $this->trial_days;
    }

	/**
     * @param field_type $trial_days
     */
    public function setTrial_days($trial_days)
    {
        $this->trial_days = $trial_days;
    }

    public function getTrialRemainingDays() {
        $interval = date_diff(date_create(date('Y-m-d')), date_create($this->getTrialExpirationDate()));
        return $interval->days;
    }	
}