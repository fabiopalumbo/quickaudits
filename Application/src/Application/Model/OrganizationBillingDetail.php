<?php
namespace Application\Model;

class OrganizationBillingDetail
{
    public $id;
    public $id_organization;
    public $organization;
    public $creditcard_id;
    public $start_date;
    public $end_date;
    public $active;
    public $id_state;
    public $state;
    public $id_country;
    public $country;
    public $cardholder_name;
    public $address;
    public $city;
    public $postcode;
    public $cardtype;
    public $cardnumber;
    public $cvv2;
    public $exp_month;
    public $exp_year;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
        $this->creditcard_id = (!empty($data['creditcard_id'])) ? $data['creditcard_id'] : null;
        $this->start_date = (!empty($data['start_date'])) ? $data['start_date'] : null;
        $this->end_date = (!empty($data['end_date'])) ? $data['end_date'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->id_state = (!empty($data['id_state'])) ? $data['id_state'] : null;
        $this->state = (!empty($data['state'])) ? $data['state'] : null;
        $this->id_country = (!empty($data['id_country'])) ? $data['id_country'] : null;
        $this->country = (!empty($data['country'])) ? $data['country'] : null;
        $this->cardholder_name = (!empty($data['cardholder_name'])) ? $data['cardholder_name'] : null;
        $this->address = (!empty($data['address'])) ? $data['address'] : null;
        $this->city = (!empty($data['city'])) ? $data['city'] : null;
        $this->postcode = (!empty($data['postcode'])) ? $data['postcode'] : null;
        $this->cardtype = (!empty($data['cardtype'])) ? $data['cardtype'] : null;
        $this->cardnumber = (!empty($data['cardnumber'])) ? $data['cardnumber'] : null;
        $this->cvv2 = (!empty($data['cvv2'])) ? $data['cvv2'] : null;
        $this->exp_month = (!empty($data['exp_month'])) ? $data['exp_month'] : null;
        $this->exp_year = (!empty($data['exp_year'])) ? $data['exp_year'] : null;
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
     * @return the $creditcard_id
     */
    public function getCreditcard_id()
    {
        return $this->creditcard_id;
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
     * @param Ambigous <NULL, unknown> $id_organization
     */
    public function setId_organization($id_organization)
    {
        $this->id_organization = $id_organization;
    }

	/**
     * @param Ambigous <NULL, unknown> $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

	/**
     * @param field_type $creditcard_id
     */
    public function setCreditcard_id($creditcard_id)
    {
        $this->creditcard_id = $creditcard_id;
    }

	/**
     * @param field_type $start_date
     */
    public function setStart_date($start_date)
    {
        $this->start_date = $start_date;
    }

	/**
     * @param field_type $end_date
     */
    public function setEnd_date($end_date)
    {
        $this->end_date = $end_date;
    }

	/**
     * @param field_type $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }
	/**
     * @return the $id_state
     */
    public function getId_state()
    {
        return $this->id_state;
    }

	/**
     * @return the $state
     */
    public function getState()
    {
        return $this->state;
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
     * @return the $cardholder_name
     */
    public function getCardholder_name()
    {
        return $this->cardholder_name;
    }

	/**
     * @return the $address
     */
    public function getAddress()
    {
        return $this->address;
    }

	/**
     * @return the $city
     */
    public function getCity()
    {
        return $this->city;
    }

	/**
     * @return the $postcode
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

	/**
     * @param field_type $id_state
     */
    public function setId_state($id_state)
    {
        $this->id_state = $id_state;
    }

	/**
     * @param field_type $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

	/**
     * @param field_type $id_country
     */
    public function setId_country($id_country)
    {
        $this->id_country = $id_country;
    }

	/**
     * @param field_type $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

	/**
     * @param field_type $cardholder_name
     */
    public function setCardholder_name($cardholder_name)
    {
        $this->cardholder_name = $cardholder_name;
    }

	/**
     * @param field_type $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

	/**
     * @param field_type $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

	/**
     * @param field_type $postcode
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
    }
	/**
     * @return the $cardtype
     */
    public function getCardtype()
    {
        return $this->cardtype;
    }

	/**
     * @return the $cardnumber
     */
    public function getCardnumber()
    {
        return $this->cardnumber;
    }

	/**
     * @return the $cvv2
     */
    public function getCvv2()
    {
        return $this->cvv2;
    }

	/**
     * @return the $exp_month
     */
    public function getExp_month()
    {
        return $this->exp_month;
    }

	/**
     * @return the $exp_year
     */
    public function getExp_year()
    {
        return $this->exp_year;
    }

	/**
     * @param field_type $cardtype
     */
    public function setCardtype($cardtype)
    {
        $this->cardtype = $cardtype;
    }

	/**
     * @param field_type $cardnumber
     */
    public function setCardnumber($cardnumber)
    {
        $this->cardnumber = $cardnumber;
    }

	/**
     * @param field_type $cvv2
     */
    public function setCvv2($cvv2)
    {
        $this->cvv2 = $cvv2;
    }

	/**
     * @param field_type $exp_month
     */
    public function setExp_month($exp_month)
    {
        $this->exp_month = $exp_month;
    }

	/**
     * @param field_type $exp_year
     */
    public function setExp_year($exp_year)
    {
        $this->exp_year = $exp_year;
    }



}