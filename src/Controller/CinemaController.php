<?php

namespace App\Controller;

use App\Entity\Cinema;
use App\Entity\Payment;
use App\Entity\Reservation;
use App\Form\PaymentType;
use App\Form\ReservationType;
use Doctrine\ORM\Cache\Lock;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\DocBlock\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Lock\Key;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function PHPUnit\Framework\isNull;

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
    public function reservation(ManagerRegistry $doctrine, Request $request, ValidatorInterface $validator,Session $session): Response
    {
        $error= [];
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
            $error = $validator->validate(
                $seats,
                new Assert\Count(min: 1,minMessage: 'Prosze zaznaczyc przynajmniej 1 miejsce')
            );
            if ($form->isValid()&&count($error)==0) {
                $key = new Key('reservation.'.count($seats).'.'.implode(".",$seats));
                dump($key);
                $lock = $this->factory->createLockFromKey($key,1200);
                $this->store->save($key);
                $lock->acquire();
                return $this->redirectToRoute('app_payment', parameters: array(
                    'seats' => $seats,
                    'key' => $key));
            }
        }

        return $this->renderForm('cinema/index.html.twig', [
            'form' => $form,
            'cinemas' => $cinemas,
            'errors'=>$error
        ]);
    }

    #[Route('/reservationPayment', name: 'app_payment')]
    public function payment(ManagerRegistry $doctrine, Request $request,Session $session): Response
    {
        dump($request->query->all());
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
            $key = new Key($request->query->all()['key']);
//
//            dump($this->store->exists($key));
            if($this->factory->createLockFromKey($key,1200)->acquire()){
                $this->factory->createLockFromKey($key,1200)->release();
            }
            return $this->redirectToRoute('app_reservation');
        }

        return $this->renderForm('cinema/payment.html.twig', [
            'form' => $form,
        ]);
    }
}
