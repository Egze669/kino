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


class CinemaController extends AbstractController
{

    #[Route('/reservationForm', name: 'app_reservation')]
    public function reservation(ManagerRegistry $doctrine,Request $request): Response
    {
        $store = new SemaphoreStore();
        $factory = new LockFactory($store);
        $em = $doctrine->getManager();
        $cinemas = $em->getRepository(Cinema::class)->findAll();
        $form = $this->createForm(ReservationType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $seats = $request->get('seats');
            return $this->redirectToRoute('app_payment', parameters: array(
                'seats' => $seats));
        }

        return $this->renderForm('cinema/index.html.twig', [
            'form'=>$form,
            'cinemas' => $cinemas,
        ]);
    }
    #[Route('/reservationPayment', name: 'app_payment')]
    public function payment(ManagerRegistry $doctrine,Request $request): Response
    {
        $em = $doctrine->getManager();
        $query = $request->query->all();
        $form = $this->createForm(PaymentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $seats = $query['seats'];
            $reserve = new Reservation();
            $reserve->setDate(new \DateTime());
            for ($i = 0;$i<count($seats);$i++){
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
            'form'=>$form,
        ]);
    }
}
