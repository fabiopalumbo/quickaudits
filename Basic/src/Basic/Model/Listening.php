<?php
namespace Basic\Model;

use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\ObjectProperty;

class Listening
{
    public $id;
    public $id_project;
    public $project;
    public $id_channel;
    public $channel;
    public $id_qa_agent;
    public $qa_agent;
    public $id_language;
    public $language;
    public $id_form;
    public $form;
    public $date;
    public $recording_name;
    public $time_recording_minutes;
    public $time_recording_seconds;
    public $comments;
    public $score;
    public $active;
    public $created;
    public $listenings_answers;
    public $id_organization;
    public $organization;
    public $id_subject;
    public $subject;
    public $min_performance_required;
    public $is_public;
    public $qa_agent_fullname;
    public $id_agent;
    public $agent;
    public $teamlead;
    public $case;
    public $incident;
    public $pnorte_room;
    public $pnorte_arrival;
    public $pnorte_departure;
    public $pnorte_name;
    public $pnorte_company;
    public $pnorte_city;
    public $pnorte_recommend;
    public $pnorte_email;

    public $listenings_group_scores;

	public function exchangeArray($data)
    {
        $this->id = (!empty($data['id'])) ? $data['id'] : null;
        $this->id_project = (!empty($data['id_project'])) ? $data['id_project'] : null;
        $this->project = (!empty($data['project'])) ? $data['project'] : null;
        $this->id_channel = (!empty($data['id_channel'])) ? $data['id_channel'] : null;
        $this->channel = (!empty($data['channel'])) ? $data['channel'] : null;
        $this->id_qa_agent = (!empty($data['id_qa_agent'])) ? $data['id_qa_agent'] : null;
        $this->qa_agent = (!empty($data['qa_agent'])) ? $data['qa_agent'] : null;
        $this->id_language = (!empty($data['id_language'])) ? $data['id_language'] : null;
        $this->language = (!empty($data['language'])) ? $data['language'] : null;
        $this->id_form = (!empty($data['id_form'])) ? $data['id_form'] : null;
        $this->form = (!empty($data['form'])) ? $data['form'] : null;
        $this->date = (!empty($data['date'])) ? $data['date'] : null;
        $this->recording_name = (!empty($data['recording_name'])) ? $data['recording_name'] : null;
        $this->time_recording_minutes = (!empty($data['time_recording_minutes'])) ? $data['time_recording_minutes'] : null;
        $this->time_recording_seconds = (!empty($data['time_recording_seconds'])) ? $data['time_recording_seconds'] : null;
        $this->comments = (!empty($data['comments'])) ? $data['comments'] : null;
        $this->teamlead = (!empty($data['teamlead'])) ? $data['teamlead'] : null;

    	$this->incident = (!empty($data['incident'])) ? $data['incident'] : null;
    	$this->pnorte_room = (!empty($data['pnorte_room'])) ? $data['pnorte_room'] : null;
    	$this->pnorte_arrival = (!empty($data['pnorte_arrival'])) ? $data['pnorte_arrival'] : null;
    	$this->pnorte_departure = (!empty($data['pnorte_departure'])) ? $data['pnorte_departure'] : null;
    	$this->pnorte_name = (!empty($data['pnorte_name'])) ? $data['pnorte_name'] : null;
    	$this->pnorte_company = (!empty($data['pnorte_company'])) ? $data['pnorte_company'] : null;
    	$this->pnorte_city = (!empty($data['pnorte_city'])) ? $data['pnorte_city'] : null;
    	$this->pnorte_recommend = (!empty($data['pnorte_recommend'])) ? $data['pnorte_recommend'] : null;
    	$this->pnorte_country = (!empty($data['pnorte_country'])) ? $data['pnorte_country'] : null;
    	$this->pnorte_email = (!empty($data['pnorte_email'])) ? $data['pnorte_email'] : null;

        $this->case = (!empty($data['case'])) ? $data['case'] : null;
        $this->score = (!empty($data['score'])) ? $data['score'] : null;
        $this->active = (!empty($data['active'])) ? $data['active'] : null;
        $this->created = (!empty($data['created'])) ? $data['created'] : null;
        $this->id_subject = (!empty($data['id_subject'])) ? $data['id_subject'] : null;
        $this->subject = (!empty($data['subject'])) ? $data['subject'] : null;
        $this->id_agent = (!empty($data['id_agent'])) ? $data['id_agent'] : null;
        $this->agent = (!empty($data['agent'])) ? $data['agent'] : null;
        
        if (!empty($data['listenings_answers']))
        {
            if (is_array($data['listenings_answers']))
            {
                $resultSetPrototype = new HydratingResultSet();
                $resultSetPrototype->setHydrator(new ObjectProperty());
                $resultSetPrototype->setObjectPrototype(new ListeningAnswer());
                $this->listenings_answers = $resultSetPrototype->initialize($data['listenings_answers']);
            }    
            else
                $this->listenings_answers = $data['listenings_answers'];
        }
        else
            $this->listenings_answers = null;

        if(!empty($data['listenings_group_scores']))
        {
            if(is_array($data['listenings_group_scores']))
            {
                $resultSetPrototype = new HydratingResultSet();
                $resultSetPrototype->setHydrator(new ObjectProperty());
                $resultSetPrototype->setObjectPrototype(new ListeningGroupScore());
                $this->listenings_group_scores = $resultSetPrototype->initialize($data['listenings_group_scores']);
            }
            else
                $this->listenings_group_scores = null;
        }
        
        $this->id_organization = (!empty($data['id_organization'])) ? $data['id_organization'] : null;
        $this->organization = (!empty($data['organization'])) ? $data['organization'] : null;
        $this->min_performance_required = (!empty($data['min_performance_required'])) ? $data['min_performance_required'] : null;
        $this->is_public = (!empty($data['is_public'])) ? $data['is_public'] : null;
        $this->qa_agent_fullname = (!empty($data['qa_agent_fullname'])) ? $data['qa_agent_fullname'] : null;
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
     * @return the $id_channel
     */
    public function getId_channel()
    {
        return $this->id_channel;
    }

	/**
     * @return the $channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

	/**
     * @return the $id_agent
     */
//     public function getId_agent()
//     {
//         return $this->id_agent;
//     }

	/**
     * @return the $agent
     */
//     public function getAgent()
//     {
//         return $this->agent;
//     }

	/**
     * @return the $id_qa_agent
     */
    public function getId_qa_agent()
    {
        return $this->id_qa_agent;
    }

	/**
     * @return the $qa_agent
     */
    public function getQa_agent()
    {
        return $this->qa_agent;
    }

	/**
     * @return the $id_language
     */
    public function getId_language()
    {
        return $this->id_language;
    }

	/**
     * @return the $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

	/**
     * @return the $id_form
     */
    public function getId_form()
    {
        return $this->id_form;
    }

	/**
     * @return the $form
     */
    public function getForm()
    {
        return $this->form;
    }

	/**
     * @return the $date
     */
    public function getDate()
    {
        return $this->date;
    }

	/**
     * @return the $recording_name
     */
    public function getRecording_name()
    {
        return $this->recording_name;
    }

	/**
     * @return the $time_recording_minutes
     */
    public function getTime_recording_minutes()
    {
        return $this->time_recording_minutes;
    }

	/**
     * @return the $time_recording_seconds
     */
    public function getTime_recording_seconds()
    {
        return $this->time_recording_seconds;
    }

	/**
     * @return the $comments
     */
    public function getComments()
    {
        return $this->comments;
    }
	/**
     * @return the $teamlead
     */
    public function getTeamLead()
    {
        return $this->teamlead;
    }
	/**
     * @return the $case
     */
    public function getCase()
    {
        return $this->case;
    }

	/**
     * @return the $incident
     */
    public function getIncident()
    {
        return $this->incident;
    }

	/**
     * @return the $pnorte_room
     */
    public function getPnorte_room()
    {
        return $this->pnorte_room;
    }

	/**
     * @return the $pnorte_arrival
     */
    public function getPnorte_arrival()
    {
        return $this->pnorte_arrival;
    }
	/**
     * @return the $pnorte_departure
     */
    public function getPnorte_departure()
    {
        return $this->pnorte_departure;
    }

	/**
     * @return the $score
     */


    public function getScore()
    {
        return $this->score;
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
     * @param field_type $id_project
     */
    public function setId_project($id_project)
    {
        $this->id_project = $id_project;
    }

	/**
     * @param field_type $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

	/**
     * @param field_type $id_channel
     */
    public function setId_channel($id_channel)
    {
        $this->id_channel = $id_channel;
    }

	/**
     * @param field_type $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

	/**
     * @param field_type $id_agent
     */
//     public function setId_agent($id_agent)
//     {
//         $this->id_agent = $id_agent;
//     }

	/**
     * @param field_type $agent
     */
//     public function setAgent($agent)
//     {
//         $this->agent = $agent;
//     }

	/**
     * @param field_type $id_qa_agent
     */
    public function setId_qa_agent($id_qa_agent)
    {
        $this->id_qa_agent = $id_qa_agent;
    }

	/**
     * @param field_type $qa_agent
     */
    public function setQa_agent($qa_agent)
    {
        $this->qa_agent = $qa_agent;
    }

	/**
     * @param field_type $id_language
     */
    public function setId_language($id_language)
    {
        $this->id_language = $id_language;
    }

	/**
     * @param field_type $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

	/**
     * @param field_type $id_form
     */
    public function setId_form($id_form)
    {
        $this->id_form = $id_form;
    }

	/**
     * @param field_type $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

	/**
     * @param field_type $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

	/**
     * @param field_type $recording_name
     */
    public function setRecording_name($recording_name)
    {
        $this->recording_name = $recording_name;
    }

	/**
     * @param field_type $time_recording_minutes
     */
    public function setTime_recording_minutes($time_recording_minutes)
    {
        $this->time_recording_minutes = $time_recording_minutes;
    }

	/**
     * @param field_type $time_recording_seconds
     */
    public function setTime_recording_seconds($time_recording_seconds)
    {
        $this->time_recording_seconds = $time_recording_seconds;
    }

	/**
     * @param field_type $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

	/**
     * @param field_type $teamlead
     */
    public function setTeamLead($teamlead)
    {
        $this->teamlead = $teamlead;
    }


	/**
     * @param field_type $case
     */
    public function setCase($case)
    {
        $this->casse = $case;
    }

	/**
     * @param field_type $incident
     */
    public function setIncident($incident)
    {
        $this->incident = $incident;
    }

	/**
     * @param field_type $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }
	/**
     * @param field_type $pnorte_room
     */
    public function setPnorte_room($pnorte_room)
    {
        $this->pnorte_room = $pnorte_room;
    }
	/**
     * @param field_type $pnorte_arrival
     */
    public function setPnorte_arrival($pnorte_arrival)
    {
        $this->pnorte_arrival = $pnorte_arrival;
    }
	/**
     * @param field_type $pnorte_departure
     */
    public function setPnorte_departure($score)
    {
        $this->pnorte_departure = $pnorte_departure;
    }

	/**
     * @param field_type $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }
	/**
     * @return the $created
     */
    public function getCreated()
    {
        return $this->created;
    }

	/**
     * @param field_type $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }
	/**
     * @return the $listenings_answers
     */
    public function getListenings_answers()
    {
        return $this->listenings_answers;
    }

	/**
     * @param Ambigous <NULL, unknown> $listenings_answers
     */
    public function setListenings_answers($listenings_answers)
    {
        $this->listenings_answers = $listenings_answers;
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
     * @return the $id_subject
     */
    public function getId_subject()
    {
        return $this->id_subject;
    }

	/**
     * @return the $subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_subject
     */
    public function setId_subject($id_subject)
    {
        $this->id_subject = $id_subject;
    }

	/**
     * @param Ambigous <NULL, unknown> $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
	/**
     * @return the $min_performance_required
     */
    public function getMin_performance_required()
    {
        return $this->min_performance_required;
    }

	/**
     * @param field_type $min_performance_required
     */
    public function setMin_performance_required($min_performance_required)
    {
        $this->min_performance_required = $min_performance_required;
    }


    public function calculateScore()
    {
        $score = 0;

        $totNA = 0;
        $qNA = 0;
        $NAToAdd = 0;
        $qQ = 0;

        foreach ($this->listenings_answers as $item){
            $qQ++;
            if($item->answer<0){
                $totNA += $item->weight;
                $qNA++;
            }
        };

        
        $NAToAdd = $totNA / (count($this->listenings_answers)-$qNA);

        foreach ($this->listenings_answers as $item)
        {
            if ($item->is_fatal && $item->answer==1)
            {
                $score=0;
                break;    
            }

            if ($item->ml_fatal && $item->answer==1)
            {
                $score=0;
                break;    
            }

            $weight = $item->weight * $qQ / ($qQ - $qNA);
            
            if ($item->answers > 2){
                
                //$score+= ($item->answer*$item->weight) / $item->answers;
                //$score+= ($item->answer*($item->weight+$NAToAdd)) / $item->answers;
                $score +=  $item->answer * $weight / $item->answers;
            } else {
                //$score+= ($item->answer*$item->weight) / ($item->answers-1);
                //$score+= $item->answer>0?($item->answer*($item->weight+$NAToAdd)):0;
                $score += $item->answer>0?($item->answer*$weight):0;
            }

        }

        return $score;
    }


	/**
     * @return the $is_public
     */
    public function getIs_public()
    {
        return $this->is_public;
    }

	/**
     * @param Ambigous <NULL, unknown> $is_public
     */
    public function setIs_public($is_public)
    {
        $this->is_public = $is_public;
    }
	/**
     * @return the $qa_agent_fullname
     */
    public function getQa_agent_fullname()
    {
        return $this->qa_agent_fullname;
    }

	/**
     * @param field_type $qa_agent_fullname
     */
    public function setQa_agent_fullname($qa_agent_fullname)
    {
        $this->qa_agent_fullname = $qa_agent_fullname;
    }
	/**
     * @return the $id_agent
     */
    public function getId_agent()
    {
        return $this->id_agent;
    }

	/**
     * @return the $agent
     */
    public function getAgent()
    {
        return $this->agent;
    }

	/**
     * @param field_type $id_agent
     */
    public function setId_agent($id_agent)
    {
        $this->id_agent = $id_agent;
    }

	/**
     * @param field_type $agent
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;
    }


    /**
     * @param Ambigous <NULL, unknown> $listenings_group_scores
     */
    public function setListenings_group_scores($listenings_group_scores)
    {
        $this->listenings_group_scores = $listenings_group_scores;
    }

    /**
     * @return the $listenings_group_scores
     */
    public function getListenings_group_scores()
    {
        return $this->listenings_group_scores;
    }



}