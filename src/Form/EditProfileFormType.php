<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nombre de usuario',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'El nombre de usuario es necesario para identificarte dentro de la aplicación'
                    ]),
                    new Length([
                        'max' => 18,
                        'maxMessage' => 'Tu nombre de usuario no puede tener mas de {{ limit }} caracteres'
                    ])
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Correo electrónico',
                'disabled' => true,
                'required' => false
            ])
            ->add('avatar', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Avatar',
                'attr' => ['placeholder' => 'Selecciona el avatar con el que el resto de usuarios te identificarán en la aplicación'],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'El avatar seleccionado supera el tamaño máximo ({{ size }} {{ suffix }}). El tamaño máximo es {{ limit }} {{ suffix }}',
                        'maxHeight' => 450,
                        'maxHeightMessage' => 'El avatar seleccionado supera la altura máxima ({{ height }} píxeles). La altura máxima es de {{ max_height }} píxeles',
                        'maxWidth' => 450,
                        'maxWidthMessage' => 'El avatar seleccionado supera el ancho máximo ({{ width }} píxeles). El ancho máximo es de {{ max_width }} píxeles'
                    ])
                ]
            ])
            ->add('public', CheckboxType::class, [
                'label' => 'Perfil público',
                'label_attr' => ['class' => 'switch-custom'],
                'help' => 'Un perfil público puede ser visto y recibir correos de usuarios que no pertenecen a la lista de amigos',
                'required' => false
            ])
            ->add('password', RepeatedType::class, [
                'mapped' => false,
                'type' => PasswordType::class,
                'invalid_message' => 'Las contraseñas introducidas deben coincidir',
                'required' => false,
                'first_options' => ['label' => 'Contraseña'],
                'second_options' => ['label' => 'Repite la contraseña'],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Tu contraseña debe tener al menos {{ limit }} caracteres',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                        'maxMessage' => 'Tu contraseña no puede tener mas de {{ limit }} caracteres',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
