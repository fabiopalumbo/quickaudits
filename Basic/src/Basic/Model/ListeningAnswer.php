<?php
namespace Basic\Model;

class ListeningAnswer
{
    public $id_listening;
    public $id_question;
    public $answer;
    public $answers;
    public $weight;
    public $weight_percentage;
    public $order;
    public $question;
    public $id_group;
    public $question_group;
    public $is_fatal;  
    public $ml_fatal;  
    public $free_answer;
    public $free_answers;

    // KHB - Agregado allow_na en tarea NA
    public $allow_na; 
    
    public function exchangeArray($data)
    {
        $this->id_listening = (!empty($data['id_listening'])) ? $data['id_listening'] : null;
        $this->id_question = (!empty($data['id_question'])) ? $data['id_question'] : null;
        $this->answer = (!empty($data['answer'])) ? $data['answer'] : null;
        // KHB - Agregado allow_na en tarea NA
        $this->allow_na = (!empty($data['allow_na'])) ? $data['allow_na'] : 0;

        $this->free_answer (!empty($data['free_answer'])) ? $data['free_answer'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
	/**
     * @return the $id_listening
     */
    public function getId_listening()
    {
        return $this->id_listening;
    }

	/**
     * @return the $id_question
     */
    public function getId_question()
    {
        return $this->id_question;
    }

    /**
     * @return the $answer
     */
    public function getAnswer()
    {
        return $this->answer;
    }

	/**
     * @return the $free_answer
     */
    public function getFreeAnswer()
    {
        return $this->free_answer;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_listening
     */
    public function setId_listening($id_listening)
    {
        $this->id_listening = $id_listening;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_question
     */
    public function setId_question($id_question)
    {
        $this->id_question = $id_question;
    }

    /**
     * @param Ambigous <NULL, unknown> $free_answer
     */
    public function setFreeAnswer($free_answer)
    {
        $this->free_answer = $free_answer;
    }
    
	/**
     * @param Ambigous <NULL, unknown> $answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return the $answers
     */
    public function getAnswers()
    {
        return $this->answers;
    }

	/**
     * @return the $answers
     */
    public function getFreeAnswers()
    {
        return $this->free_answers;
    }

	/**
     * @return the $weight
     */
    public function getWeight()
    {
        return $this->weight;
    }

	/**
     * @return the $weight_percentage
     */
    public function getWeight_percentage()
    {
        return $this->weight_percentage;
    }

	/**
     * @return the $order
     */
    public function getOrder()
    {
        return $this->order;
    }

	/**
     * @param field_type $answers
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
    }

	/**
     * @param field_type $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

	/**
     * @param field_type $weight_percentage
     */
    public function setWeight_percentage($weight_percentage)
    {
        $this->weight_percentage = $weight_percentage;
    }

	/**
     * @param field_type $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
	/**
     * @return the $question
     */
    public function getQuestion()
    {
        return $this->question;
    }

	/**
     * @return the $id_group
     */
    public function getId_group()
    {
        return $this->id_group;
    }

	/**
     * @return the $question_group
     */
    public function getQuestion_group()
    {
        return $this->question_group;
    }

	/**
     * @param field_type $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

	/**
     * @param field_type $id_group
     */
    public function setId_group($id_group)
    {
        $this->id_group = $id_group;
    }

	/**
     * @param field_type $question_group
     */
    public function setQuestion_group($question_group)
    {
        $this->question_group = $question_group;
    }
	/**
     * @return the $is_fatal
     */
    public function getIs_fatal()
    {
        return $this->is_fatal;
    }

	/**
     * @param field_type $is_fatal
     */
    public function setIs_fatal($is_fatal)
    {
        $this->is_fatal = $is_fatal;
    }

    /**
     * @return the $ml_fatal
     */
    public function getMl_fatal()
    {
        return $this->Ml_fatal;
    }

    /**
     * @param field_type $ml_fatal
     */
    public function setMl_fatal($ml_fatal)
    {
        $this->ml_fatal = $ml_fatal;
    }

    // KHB - Agregado en tarea NA
    /**
     * @return the $allow_na
     */
    public function getAllow_na()
    {
        return $this->allow_na;
    }

    // KHB - Agregado allow_na en tarea NA
    /**
     * @param field_type $allow_na
     */
    public function setAllow_na($allow_na)
    {
        $this->allow_na = $allow_na;
    }


}