<?php

namespace project\Modules\News\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="module_news")
 */
class modulenews
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
    protected $row_id;
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
            0 => array ('type' => 'datatime', 'name' => 'date', 'title' => 'Дата'),
            1 => array ('type' => 'text', 'name' => 'title', 'title' => 'Название'),
            2 => array ('type' => 'textarea', 'name' => 'new', 'title' => 'Новость'),
            3 => array ('type' => 'text', 'name' => 'test', 'title' => 'Тест'),
            4 => array ('type' => 'text', 'name' => 'test2', 'title' => 'Тест2'),
            5 => array ('type' => 'text', 'name' => 'fireice_order', 'title' => 'Порядок'),
        );
    }

    /*
      public function getConfigSort()
      {
      return array(
      'sortBy' => 2,            // Индекс плагина (по какому сортировать) в массиве getConfig()
      'desc'   => true         // В обратном порядке или нет
      );
      }
     */

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

    public function setRowId($row_id)
    {
        $this->row_id = $row_id;
    }

    public function getRowId()
    {
        return $this->row_id;
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
