<?php

namespace App\Controller;

use App\Entity\Alert;
use App\Form\AlertType;
use App\Message\MailAlert;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $em, MailerInterface $mailer , MessageBusInterface $bus): Response
    {
        $task = new Alert();
        $task->setUser($this->getUser())
            ->setCreatedAt(createdAt: new \DateTimeImmutable(datetime: 'now'));

        $form = $this->createForm(AlertType::class, $task);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $task = $form->getData();

            $em->persist($task);
            $em->flush();

            // $email = (new Email())
            //     ->from($task->getUser()->getEmail()) 
            //     ->to('you@gmail.com')
            //     ->subject('new Alert #'. $task->getId() . '____'. $task->getUser()->getEmail())
            //     ->html('<p> '. $task->getDescreption() . '<p>') ;

            //     sleep(10);
            //     $mailer->send($email) ;

            $bus->dispatch(new MailAlert($task->getDescreption(), $task->getId(), $task->getUser()->getEmail()));

            return $this->redirectToRoute('app_home');
        }
        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'controller_name' => 'HomeController',
        ]);
    }
}
