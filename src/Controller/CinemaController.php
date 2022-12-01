<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Reservation;
use App\Form\PaymentType;
use App\Form\ReservationType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Lock\Key;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class CinemaController extends AbstractController
{
    private SemaphoreStore $store;
    private LockFactory $factory;
    public function __construct()
    {
        $this->store = new SemaphoreStore();
        $this->factory = new LockFactory($this->store);
    }

    #[Route('/reservationForm', name: 'app_reservation')]
    public function reservation(ManagerRegistry $doctrine, Request $request,Session $session): Response
    {
//        $error= [];
        $em = $doctrine->getManager();
        $cinemas = $em->getRepository(Cinema::class)->findAll();
        $form = $this->createForm(ReservationType::class,null,[
            'seats'=>$cinemas
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted()&&$form->isValid()) {
                $seats = $form['seats']->getData();
                $keyArray = [];

                foreach ($seats as $seat){
                    $keySource = 'reservation.1.'.$seat;
                    $key = new Key($keySource);
                    $lock = $this->factory->createLockFromKey($key,1200);
                    $keyArray[]=$keySource;
                    $lock->acquire();
                }
                    $session->set('seatArray',$seats);
                    $session->set('keyArray', $keyArray);

                return $this->redirectToRoute('app_payment');
            }

        return $this->renderForm('cinema/index.html.twig', [
            'form' => $form,
            'cinemas' => $cinemas
        ]);
    }

    #[Route('/reservationPayment', name: 'app_payment')]
    public function payment(ManagerRegistry $doctrine, Request $request,Session $session): Response
    {
        $em = $doctrine->getManager();
        /** @var array $seats */
        $seats = $session->get('seatArray');

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
            foreach ($session->get('keyArray') as $keySession){
                $key = new Key($keySession);
                if($this->factory->createLockFromKey($key,1200)->acquire()){
                    $this->factory->createLockFromKey($key,1200)->release();
                }
            }
            return $this->redirectToRoute('app_reservation');
        }

        return $this->renderForm('cinema/payment.html.twig', [
            'form' => $form,
        ]);
    }
}
