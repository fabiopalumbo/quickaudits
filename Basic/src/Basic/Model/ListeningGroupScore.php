<?php
namespace Basic\Model;

class ListeningGroupScore
{
    public $id_listening;
    public $id_question_group;
    public $weight;
    public $score;
    
    public function exchangeArray($data)
    {
        print_r($data);
        $this->id_listening = (!empty($data['id_listening'])) ? $data['id_listening'] : null;
        $this->id_question_group = (!empty($data['id_question_group'])) ? $data['id_question_group'] : null;
        $this->score = (!empty($data['score'])) ? $data['score'] : 0;

        $this->weight = (!empty($data['weight'])) ? $data['weight'] : 0;
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
    public function getId_question_group()
    {
        return $this->id_question_group;
    }

    /**
     * @return the $score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @return the $weight
     */
    public function getWeight()
    {
        return $this->weight;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_listening
     */
    public function setId_listening($id_listening)
    {
        $this->id_listening = $id_listening;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_question_group
     */
    public function setId_question_group($id_question_group)
    {
        $this->id_question_group = $id_question_group;
    }
    
	/**
     * @param Ambigous <NULL, unknown> $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @param Ambigous <NULL, unknown> $score
     */
    public function setScore($score)
    {
        $this->score;
    }

}