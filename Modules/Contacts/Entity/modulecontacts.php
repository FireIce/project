<?php

namespace example\Modules\Contacts\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="module_contacts")
 */
class modulecontacts
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")    
     * @Assert\Type("numeric")                                              
     */
    protected $id;
    /**
     * @ORM\Column(type="integer", nullable="TRUE")    
     * @Assert\Type("numeric")     
     */
    protected $idd;
    /**
     * @ORM\Column(type="integer", nullable="TRUE")          
     */
    protected $cid;
    /**
     * @ORM\Column(type="integer", nullable="TRUE")          
     */
    protected $eid;
    /**
     * @ORM\Column(type="string", length=1)   
     */
    protected $final;
    /**
     * @ORM\Column(type="integer")       
     */
    protected $plugin_id;
    /**
     * @ORM\Column(type="string", length=45)      
     */
    protected $plugin_type;
    /**
     * @ORM\Column(type="string", length=45)      
     */
    protected $plugin_name;
    /**
     * @ORM\Column(type="string", length=45)      
     */
    protected $status;
    /**
     * @ORM\Column(type="datetime")         
     */
    protected $date_create;

    public function getConfig()
    {
        return array (
            0 => array ('type' => 'text', 'name' => 'phones', 'title' => 'Телефон'),
            1 => array ('type' => 'textarea', 'name' => 'address', 'title' => 'Адрес'),
            2 => array ('type' => 'textarea', 'name' => 'test', 'title' => 'Тест'),
            3 => array ('type' => 'textarea', 'name' => 'metka', 'title' => 'Метка'),
            4 => array ('type' => 'selectbox', 'name' => 'select', 'title' => 'Тестовый селектбокс'),
            5 => array ('type' => 'radiobutton', 'name' => 'radio', 'title' => 'Тестовый радиобаттон'),
            6 => array ('type' => 'text', 'name' => 'test2', 'title' => 'Добавочный'),
        );
    }

    public function configSelect()
    {
        // Источник - массив данных
        /*
          return array(
          'type' => 'array',
          'data' => array(
          '0' => '--------',
          '1' => 'Селект_1',
          '2' => 'Селект_2',
          '3' => 'Селект_3'
          )
          );
         */

        // Источник - другой узел
        return array (
            'type' => 'node',
            'data' => array (
                'id_node' => 150,
                'id_module' => 4,
                'plugin_id_for_title' => 1,
            )
        );
    }

    public function configRadio()
    {
        return array (
            'type' => 'array',
            'data' => array (
                '1' => 'Радиобаттон_1',
                '2' => 'Радиобаттон_2',
                '3' => 'Радиобаттон_3'
            )
        );
    }

    public function __construct()
    {
        $this->date_create = new \DateTime();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIdd($idd)
    {
        $this->idd = $idd;
    }

    public function getIdd()
    {
        return $this->idd;
    }

    public function setCid($cid)
    {
        $this->cid = $cid;
    }

    public function getCid()
    {
        return $this->cid;
    }

    public function setEid($eid)
    {
        $this->eid = $eid;
    }

    public function getEid()
    {
        return $this->eid;
    }

    public function setFinal($final)
    {
        $this->final = $final;
    }

    public function getFinal()
    {
        return $this->final;
    }

    public function setPluginId($plugin_id)
    {
        $this->plugin_id = $plugin_id;
    }

    public function getPluginId()
    {
        return $this->plugin_id;
    }

    public function setPluginType($plugin_type)
    {
        $this->plugin_type = $plugin_type;
    }

    public function getPluginType()
    {
        return $this->plugin_type;
    }

    public function setPluginName($plugin_name)
    {
        $this->plugin_name = $plugin_name;
    }

    public function getPluginName()
    {
        return $this->plugin_name;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setDateCreate($date_create)
    {
        $this->date_create = $date_create;
    }

    public function getDateCreate()
    {
        return $this->date_create;
    }

}
