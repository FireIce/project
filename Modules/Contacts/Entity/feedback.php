<?php

namespace project\Modules\Contacts\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class feedback
{
    protected $date;
    
    /**
     * @Assert\NotBlank()                                             
     */
    protected $name;
    /**
     * @Assert\NotBlank()
     * @Assert\Email
     */
    protected $email;
    /**
     * @Assert\NotBlank()          
     */
    protected $comment;
    
    public function __construct()
    {
        $date = new \DateTime();
        //$date->format( 'd-m-Y, H:i' );
        
        $this->date = $date;
    }
    
    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getDate()
    {
        return $this->date->format( 'd-m-Y, H:i' );
    }    

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

}
