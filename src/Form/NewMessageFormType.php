<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewMessageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenido',
                'required' => true,
                'attr' => ['autofocus' => true],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Un mensaje debe tener '
                    ]),
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'El contenido del mensaje no puede tener mas de {{ limit }} caracteres'
                    ])
                ]
            ])
            ->add('attachments', FileType::class, [
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'label' => 'Adjuntos',
                'attr' => ['placeholder' => 'Seleccionar los adjuntos que se enviarán junto al mensaje'],
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '10M',
                            'maxSizeMessage' => 'El adjunto seleccionado supera el tamaño máximo ({{ size }} {{ suffix }}). El tamaño máximo es {{ limit }} {{ suffix }}'
                        ])
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
