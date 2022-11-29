<?php

namespace App\Form;

use App\Entity\Cinema;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Persistence\ManagerRegistry;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        $choicesAttr = [];
        $enable = '';
        $color = '';
        foreach ($options['seats'] as $seat) {

            if ($seat->getRoomNumber() == $options['room_number']) {
                if(is_null($seat->getReservation())){
                    $enable = 'enabled';
                    $color = 'blue';
                }else {
                    $enable = 'disabled';
                    $color = 'red';
                }
                $choices += [$seat->getLineNumber().'-'.$seat->getSeatNumber() => $seat->getId()];
                $choicesAttr += [$seat->getLineNumber().'-'.$seat->getSeatNumber() => [$enable=>true,'color'=>$color]];

            }
        }
        $builder
//        ->add('date',DateType::class);
            ->add('seats', ChoiceType::class, [
                'label_html'=>true,
                'choices' => [
                    $choices
                ],
                'choice_attr' => $choicesAttr,
                'expanded' => true,
                'multiple' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('seats');
        $resolver->setDefaults([
            'room_number' => 1,
            'data_class' => Reservation::class,
        ]);
    }
}
