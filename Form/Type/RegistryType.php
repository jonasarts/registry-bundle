<?php

namespace jonasarts\Bundle\RegistryBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\MinLength;
use Symfony\Component\Validator\Constraints\MaxLength;

class RegistryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('userid', 'integer', array(
            'required' => true,
            'label' => 'UserID',
        ))
        ->add('registrykey', 'text', array(
            'constraints' => array(
                new NotBlank(),
                new MaxLength(255),
            ),
            'required' => true,
            'label' => 'Registry Key',
        ))
        ->add('name', 'text', array(
            'constraints' => array(
                new NotBlank(),
                new MaxLength(255),
            ),
            'required' => true,
        ))
        ->add('type', 'choice', array(
            'choices' => array('int' => 'Integer', 'bln' => 'Boolean', 'str' => 'String', 'flt' => 'Float'),
            'required' => true,
        ))
        ->add('value');
    }

    public function getName()
    {
        return 'ja_registrybundle_registrytype';
    }
}
