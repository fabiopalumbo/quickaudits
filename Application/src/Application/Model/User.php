<?php
namespace Application\Model;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;

class User
{
    public $id;
    public $id_role;
    public $sid;
    public $username;
    public $name;
    public $email;
    public $password;
    public $active;
    public $id_language;
    public $tokenConfirm;
    public $role;
    public $languages;
    public $id_organization;
    public $organization;
    public $id_locale;
    public $locale;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_role = (!empty($data['id_role'])) ? $data['id_role'] : null;
        $this->role = (!empty($data['role'])) ? $data['role'] : null;
        $this->sid = (!empty($data['sid'])) ? $data['sid'] : null;
        $this->username = (!empty($data['username'])) ? $data['username'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->email = (!empty($data['email'])) ? $data['email'] : null;
        $this->password = (!empty($data['password'])) ? $data['password'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->id_language = (!empty($data['id_language'])) ? $data['id_language'] : null;
        $this->languages = (!empty($data['languages'])) ? $data['languages'] : null;
        $this->tokenConfirm = (!empty($data['token_confirm'])) ? $data['token_confirm'] : null;
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
        $this->id_locale = (!empty($data['id_locale'])) ? $data['id_locale'] : null;
        $this->locale = (!empty($data['locale'])) ? $data['locale'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }
    
    /**
     * 
     * @param Zend\Db\Adapter\Adapter $dbAdapter
     * @return \Zend\InputFilter\InputFilter
     */
    public function getInputFilter($dbAdapter, $roleRequired=true, $addUserAction=false)
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
                'name'     => 'id_role',
                'required' => $roleRequired,
                'filters'  => array(
                    array('name' => 'Int'),
                ),
            ));
    
            $inputFilter->add(array(
                    'name'     => 'name',
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
                    array(
                        'name'=>'EmailAddress'
                    ),
                    array(
                        'name'=>'Db\NoRecordExists',
                        'options'=>array(
                            'table'=>'users',
                            'field'=>'email',
                            'adapter'=>$dbAdapter,
                            'exclude' => array(
                                'field' => 'id',
                                'value' => $this->id?:'0'
                            )
                        )
                    ),
                ),
            ));

            $inputFilter->add(array(
                'name'     => 'id_language',
                'required' => false,
                'allow_empty' => true,
            ));
    
            $inputFilter->add(array(
                'name'     => 'id_locale',
                'required' => false,
                'filters'  => array(
                    array('name' => 'Int'),
                ),
            ));
            
            if ($addUserAction)
            {
                $this->add(array(
                    'name'     => 'id_project_role',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'Int'),
                    ),
                ));
                
                $this->add(array(
                    'name'     => 'id_project',
                    'required' => true,
                    'filters'  => array(
                        array('name' => 'Int'),
                    ),
                ));
            }
            
            $this->inputFilter = $inputFilter;
        }
    
        return $this->inputFilter;
    }
    
    public function getLanguagesFormatted()
    {
        $languages = array();
        foreach ($this->languages as $item)
        {
            array_push($languages, $item->name);
        }
        return implode(', ', $languages);
    }
	/**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

	/**
     * @return the $id_role
     */
    public function getId_role()
    {
        return $this->id_role;
    }

	/**
     * @return the $sid
     */
    public function getSid()
    {
        return $this->sid;
    }

	/**
     * @return the $username
     */
    public function getUsername()
    {
        return $this->username;
    }

	/**
     * @return the $name
     */
    public function getName()
    {
        return $this->name;
    }

	/**
     * @return the $email
     */
    public function getEmail()
    {
        return $this->email;
    }

	/**
     * @return the $password
     */
    public function getPassword()
    {
        return $this->password;
    }

	/**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

	/**
     * @return the $id_language
     */
    public function getId_language()
    {
        return $this->id_language;
    }

	/**
     * @return the $tokenConfirm
     */
    public function getTokenConfirm()
    {
        return $this->tokenConfirm;
    }

	/**
     * @return the $role
     */
    public function getRole()
    {
        return $this->role;
    }

	/**
     * @return the $languages
     */
    public function getLanguages()
    {
        return $this->languages;
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
     * @param Ambigous <NULL, unknown> $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_role
     */
    public function setId_role($id_role)
    {
        $this->id_role = $id_role;
    }

	/**
     * @param Ambigous <NULL, unknown> $sid
     */
    public function setSid($sid)
    {
        $this->sid = $sid;
    }

	/**
     * @param Ambigous <NULL, unknown> $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

	/**
     * @param Ambigous <NULL, unknown> $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

	/**
     * @param Ambigous <NULL, unknown> $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

	/**
     * @param Ambigous <NULL, unknown> $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_language
     */
    public function setId_language($id_language)
    {
        $this->id_language = $id_language;
    }

	/**
     * @param Ambigous <NULL, unknown> $tokenConfirm
     */
    public function setTokenConfirm($tokenConfirm)
    {
        $this->tokenConfirm = $tokenConfirm;
    }

	/**
     * @param Ambigous <NULL, unknown> $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

	/**
     * @param Ambigous <NULL, unknown> $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
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
     * @return the $id_locale
     */
    public function getId_locale()
    {
        return $this->id_locale;
    }

	/**
     * @return the $locale
     */
    public function getLocale()
    {
        return $this->locale;
    }

	/**
     * @param field_type $id_locale
     */
    public function setId_locale($id_locale)
    {
        $this->id_locale = $id_locale;
    }

	/**
     * @param field_type $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }


}