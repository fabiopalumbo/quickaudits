<?php
namespace Application\Model;

class Project
{
    public $id;
    public $name;
    public $min_performance_required;
    public $active;
    public $languages;
    public $projects_channels;
    public $id_organization;
    public $organization;
    public $enable_public;
    public $require_public_names;
    public $public_description;
    public $id_locale;
    public $locale;
    public $public_by_agents;
    public $enable_form_selector;
    public $form_selector_question;
    public $public_token;
    public $be_anonymous;
    
    public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->name = (!empty($data['name'])) ? $data['name'] : null;
        $this->min_performance_required = (!empty($data['min_performance_required'])) ? $data['min_performance_required'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->languages = (!empty($data['languages'])) ? $data['languages'] : null;
        $this->projects_channels = (!empty($data['projects_channels'])) ? $data['projects_channels'] : null;
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
        $this->enable_public = (!empty($data['enable_public'])) ? $data['enable_public'] : null;
        $this->require_public_names = (!empty($data['require_public_names'])) ? $data['require_public_names'] : null;
        $this->public_description = (!empty($data['public_description'])) ? $data['public_description'] : null;
        $this->id_locale = (!empty($data['id_locale'])) ? $data['id_locale'] : null;
        $this->locale = (!empty($data['locale'])) ? $data['locale'] : null;
        $this->public_by_agents = (!empty($data['public_by_agents'])) ? $data['public_by_agents'] : null;
        $this->enable_form_selector = (!empty($data['enable_form_selector'])) ? $data['enable_form_selector'] : null;
        $this->form_selector_question = (!empty($data['form_selector_question'])) ? $data['form_selector_question'] : null;
        $this->public_token = (!empty($data['public_token'])) ? $data['public_token']:null;
        $this->be_anonymous = (!empty($data['be_anonymous'])) ? $data['be_anonymous']:null;
    }

    /**
     * @return the $be_anonymous
     */
    public function getBe_anonymous()
    {
        return $this->be_anonymous;
    }

    /**
     * @param field_type $be_anonymous
     */
    public function setBe_anonymous($be_anonymous)
    {
        $this->be_anonymous = $be_anonymous;
    }

    /**
     * @return the $form_selector_question
     */
    public function getForm_selector_question()
    {
        return $this->form_selector_question;
    }

    /**
     * @param field_type $form_selector_question
     */
    public function setForm_selector_question($form_selector_question)
    {
        $this->form_selector_question = $form_selector_question;
    }

    /**
     * @return the $enable_form_selector
     */
    public function getEnable_form_selector()
    {
        return $this->enable_form_selector;
    }

    /**
     * @param field_type $enable_form_selector
     */
    public function setEnable_form_selector($enable_form_selector)
    {
        $this->enable_form_selector = $enable_form_selector;
    }

    /**
     * @return the $public_by_agents
     */
    public function getPublic_by_agents()
    {
        return $this->public_by_agents;
    }

    /**
     * @param field_type $public_by_agents
     */
    public function setPublic_by_agents($public_by_agents)
    {
        $this->public_by_agents = $public_by_agents;
    }


    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
    
    public function getLanguagesString()
    {
        $languages = array();
        
        if (!empty($this->languages)) {
            foreach ($this->languages as $item)
            {
                array_push($languages, $item->name);
            }    
        }
        
        return implode(', ', $languages);
    }
    
    public function getLanguagesIds()
    {
        $languages = array();
    
        if (!empty($this->languages)) {
            foreach ($this->languages as $item)
            {
                array_push($languages, $item->id);
            }    
        }
    
        return $languages;
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
     * @return the $min_performance_required
     */
    public function getMin_performance_required()
    {
        return $this->min_performance_required;
    }

	/**
     * @return the $active
     */
    public function getActive()
    {
        return $this->active;
    }

	/**
     * @return the $languages
     */
    public function getLanguages()
    {
        return $this->languages;
    }

	/**
     * @return the $projects_channels
     */
    public function getProjects_channels()
    {
        return $this->projects_channels;
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
     * @param Ambigous <NULL, unknown> $min_performance_required
     */
    public function setMin_performance_required($min_performance_required)
    {
        $this->min_performance_required = $min_performance_required;
    }

	/**
     * @param Ambigous <NULL, unknown> $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

	/**
     * @param multitype: $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

	/**
     * @param Ambigous <multitype:, unknown> $projects_channels
     */
    public function setProjects_channels($projects_channels)
    {
        $this->projects_channels = $projects_channels;
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
     * @return the $enable_public
     */
    public function getEnable_public()
    {
        return $this->enable_public;
    }

	/**
     * @param field_type $enable_public
     */
    public function setEnable_public($enable_public)
    {
        $this->enable_public = $enable_public;
    }

    public function getPublicToken($idChannel=false, $idForm=false, $idAgent=false)
    {
        $ret = null;

        if ($idChannel && $idForm) {
            $ret =  md5($this->id.$idChannel.$idForm);

            if($idAgent) {
                $ret .= $idAgent;
            }
        }

        return $ret;
    }

    /**
     * @return the $public_token
     */
    public function getPublic_token()
    {
        return $this->public_token;
    }

    /**
     * @param field_type $public_token
     */
    public function setPublic_token($public_token)
    {
        $this->public_token = $public_token;
    }



	/**
     * @return the $public_description
     */
    public function getPublic_description()
    {
        return $this->public_description;
    }

	/**
     * @param field_type $public_description
     */
    public function setPublic_description($public_description)
    {
        $this->public_description = $public_description;
    }
	/**
     * @return the $require_public_names
     */
    public function getRequire_public_names()
    {
        return $this->require_public_names;
    }

	/**
     * @param Ambigous <NULL, unknown> $require_public_names
     */
    public function setRequire_public_names($require_public_names)
    {
        $this->require_public_names = $require_public_names;
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
     * @param Ambigous <NULL, unknown> $id_locale
     */
    public function setId_locale($id_locale)
    {
        $this->id_locale = $id_locale;
    }

	/**
     * @param Ambigous <NULL, unknown> $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }



}