<?php

namespace project\Modules\Comments\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class CommentsForm extends AbstractType
{
    private $choices;

    public function __construct($choices=array ())
    {
        $this->choices = $choices;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('comment', 'textarea');
        $builder->add('answer', 'choice', array ('choices' => $this->choices));
    }

    public function getName()
    {
        return 'comments';
    }

}