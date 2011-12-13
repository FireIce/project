<?php

namespace pit\Modules\Contacts\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class FeedbackForm extends AbstractType
{

    public function buildForm(FormBuilder $builder, array $options)
    {
        //$options = array ();
        $options = array ('required' => false);

        $builder->add('name', 'text', $options);
        $builder->add('email', 'text', $options);
        $builder->add('comment', 'textarea', $options);
    }

    public function getName()
    {
        return 'feedback';
    }

}