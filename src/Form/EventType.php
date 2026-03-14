<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr'  => ['placeholder' => 'Titre de l\'événement'],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => ['rows' => 5, 'placeholder' => 'Description détaillée...'],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire.']),
                ],
            ])
            ->add('date', DateTimeType::class, [
                'label'  => 'Date et heure',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'La date est obligatoire.']),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'attr'  => ['placeholder' => 'Ex: Salle A, ISSAT Sousse'],
                'constraints' => [
                    new NotBlank(['message' => 'Le lieu est obligatoire.']),
                ],
            ])
            ->add('seats', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr'  => ['min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de places est obligatoire.']),
                    new GreaterThan(['value' => 0, 'message' => 'Doit être supérieur à 0.']),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Image (optionnel)',
                'required' => false,
                'mapped'   => false,
                'attr'     => ['accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Format accepté : JPG, PNG, WEBP.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Event::class]);
    }
}