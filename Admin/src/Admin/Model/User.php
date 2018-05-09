<?php
namespace Admin\Model;

// use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
// use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class User
{
    public $id;
    public $firstname;
    public $lastname;
    public $email;
    public $id_role;
    public $active;
    
    /**
     * 
     * @var Role
     */
    public $role;
    
    public function __construct()
    {
    }

    public function exchangeArray($data)
    {
        $this->id     = (!empty($data['id'])) ? $data['id'] : null;
        $this->firstname = (!empty($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname  = (!empty($data['lastname'])) ? $data['lastname'] : null;
        $this->email  = (!empty($data['email'])) ? $data['email'] : null;
        $this->id_role = (!empty($data['id_role'])) ? $data['id_role'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->role = (!empty($data['role'])) ? $data['role'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    
    
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }
    
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
    
            $inputFilter->add(array(
                    'name'     => 'id',
                    'required' => true,
                    'filters'  => array(
                            array('name' => 'Int'),
                    ),
            ));
    
            $inputFilter->add(array(
                    'name'     => 'firstname',
                    'required' => true,
                    'filters'  => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                            array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                            'encoding' => 'UTF-8',
                                            'min'      => 1,
                                            'max'      => 100,
                                    ),
                            ),
                    ),
            ));
    
            $inputFilter->add(array(
                    'name'     => 'lastname',
                    'required' => true,
                    'filters'  => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                            array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                            'encoding' => 'UTF-8',
                                            'min'      => 1,
                                            'max'      => 100,
                                    ),
                            ),
                    ),
            ));
            
            $inputFilter->add(array(
                    'name'     => 'email',
                    'required' => true,
                    'filters'  => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                    ),
                    'validators' => array(
                            array(
                                    'name'    => 'StringLength',
                                    'options' => array(
                                            'encoding' => 'UTF-8',
                                            'min'      => 1,
                                            'max'      => 100,
                                    ),
                            ),
                    ),
            ));
            
            $inputFilter->add(array(
                    'name'     => 'id_role',
                    'required' => true,
                    'filters'  => array(
                            array('name' => 'Int'),
                    ),
            ));
    
            $this->inputFilter = $inputFilter;
        }
    
        return $this->inputFilter;
    }
	/**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
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
     * @return the $email
     */
    public function getEmail()
    {
        return $this->email;
    }

	/**
     * @return the $id_role
     */
    public function getId_role()
    {
        return $this->id_role;
    }

	/**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

	/**
     * @return the $role
     */
    public function getRole()
    {
        return $this->role;
    }

	/**
     * @param Ambigous <NULL, unknown> $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @param Ambigous <NULL, unknown> $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_role
     */
    public function setId_role($id_role)
    {
        $this->id_role = $id_role;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

	/**
     * @param \Admin\Model\Role $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    
}