<?php

namespace App\Form;

use App\Entity\Thread;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewThreadFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Título',
                'required' => true,
                'attr' => ['autofocus' => true],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Una conversación debe tener un título'
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'El título de la conversación no puede tener mas de {{ limit }} caracteres'
                    ])
                ]
            ])
            ->add('members', EntityType::class, [
                'label' => 'Miembros',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'title' => 'Usuarios que se unirán a la conversación'
                ],
                'class' => User::class,
                'query_builder' => static function (UserRepository $er) use ($user)
                {
                    return $er->buildFriendsQueries($user)
                        ->where('mf.id IS NOT NULL')
                        ->andWhere('fwe.id IS NOT NULL');
                },
                'choice_label' => 'username',
                'choice_value' => 'uuid'
            ])
            ->add('groups', ChoiceType::class, [
                'label' => 'Grupos',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'disabled' => true,
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => true,
                    'title' => 'Grupos que se unirán a la conversación'
                ]
            ])
            ->add('message', NewMessageFormType::class, [
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['user']);

        $resolver->setDefaults([
            'data_class' => Thread::class,
        ]);
    }
}
