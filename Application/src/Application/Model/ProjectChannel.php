<?php
namespace Application\Model;

class ProjectChannel
{
//     /**
//      * 
//      * @var Channel
//      */
//     public $channel;
//     /**
//      * 
//      * @var Form
//      */
//     public $form;
    
//     public function exchangeArray($data)
//     {
//         $this->channel = new Channel();
//         $this->form = new Form();
        
//         $this->channel->exchangeArray(array('id'=>$data['id_channel'],'name'=>$data['channel']));
//         $this->form->exchangeArray(array('id'=>$data['id_form'],'name'=>$data['form']));
//     }

    
    public $id_channel;
    public $channel;
    public $id_form;
    public $form;
    
    public function exchangeArray($data)
    {
        $this->id_channel = (!empty($data['id_channel'])) ? $data['id_channel'] : null;
        $this->channel = (!empty($data['channel'])) ? $data['channel'] : null;
        $this->id_form = (!empty($data['id_form'])) ? $data['id_form'] : null;
        $this->form = (!empty($data['form'])) ? $data['form'] : null;
    }
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
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
     * @param Ambigous <NULL, unknown> $id_channel
     */
    public function setId_channel($id_channel)
    {
        $this->id_channel = $id_channel;
    }

	/**
     * @param Ambigous <NULL, unknown> $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

	/**
     * @param Ambigous <NULL, unknown> $id_form
     */
    public function setId_form($id_form)
    {
        $this->id_form = $id_form;
    }

	/**
     * @param Ambigous <NULL, unknown> $form
     */
    public function setForm($form)
    {
        $this->form = $form;
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

}