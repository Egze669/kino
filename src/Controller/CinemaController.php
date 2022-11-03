<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Payment;
use App\Entity\Reservation;
use App\Form\PaymentType;
use App\Form\ReservationType;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function PHPUnit\Framework\isNull;


class CinemaController extends AbstractController
{
    #[Route('/reservationForm', name: 'app_reservation')]
    public function reservation(LockFactory $factory, ManagerRegistry $doctrine, Request $request, ValidatorInterface $validator): Response
    {
        $em = $doctrine->getManager();
        $cinemas = $em->getRepository(Cinema::class)->findAll();
        $form = $this->createForm(ReservationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if(array_key_exists('seats',$request->request->all())){
                /** @var array $seats */
                $seats = $request->request->all()['seats'];
            }else{
                $seats=[];
            }
            $seatsConstraint = new Assert\Count(min: 1);
            $errors = $validator->validate(
                $seats,
                $seatsConstraint
            );
            if ($form->isValid()&&count($errors)==0) {
                $factory->createLock('reservation', 1200);

                return $this->redirectToRoute('app_payment', parameters: array(
                    'seats' => $seats));
            }
            else{
                $this->addFlash('error','Prosze zaznaczyc przynajmniej 1 miejsce');
            }
        }

        return $this->renderForm('cinema/index.html.twig', [
            'form' => $form,
            'cinemas' => $cinemas,
        ]);
    }

    #[Route('/reservationPayment', name: 'app_payment')]
    public function payment(ManagerRegistry $doctrine, Request $request): Response
    {

        $em = $doctrine->getManager();
        /** @var array $seats */
        $seats = $request->query->all()['seats'];

        $form = $this->createForm(PaymentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reserve = new Reservation();
            $reserve->setDate(new \DateTime());
            for ($i = 0; $i < count($seats); $i++) {
                $cinema = $em->getRepository(Cinema::class)->find($seats[$i]);
                if (!empty($cinema)) {
                    $reserve->addCinema($cinema);
                }
            }
            $em->persist($reserve);
            $em->flush();

            return $this->redirectToRoute('app_reservation');
        }

        return $this->renderForm('cinema/payment.html.twig', [
            'form' => $form,
        ]);
    }
}
