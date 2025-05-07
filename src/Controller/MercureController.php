<?php

namespace App\Controller;

use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MercureController extends AbstractController
{
    #[Route('/publish', name: 'publish')]

    public function publish(HubInterface $hub , Request $request): Response
    {    

        $data = json_decode($request->getContent(), true);
        // dd($data) ;
        $topic = $data['topic'] ?? null;
        if (!$topic) {
            return new JsonResponse(['error' => 'Missing "topic" parameter'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le message depuis la requête
        $message = $data['message'] ?? 'Hello!';


        $update = new Update(
                    $topic,
                    json_encode(['status' => 'Mercure fonctionne!', 'message' => $message])
                );



        // $update = new Update(
        //     'https://example.com/books/1',
        //     json_encode(['status' => 'message reçu'])
        // );

        $hub->publish($update);

        return new Response('published!');
    }



    // public function publish(HubInterface $hub, Request $request): Response
    // {
    //     $data = json_decode($request->getContent(), true);

    //     // Récupérer le topic depuis la requête
    //     $topic = $data['topic'] ?? null;
    //     if (!$topic) {
    //         return new JsonResponse(['error' => 'Missing "topic" parameter'], Response::HTTP_BAD_REQUEST);
    //     }

    //     // Récupérer le message depuis la requête
    //     $message = $data['message'] ?? 'Hello!';

    //     // dd($message , $topic) ;

    //     // Créer une mise à jour Mercure
    //     $update = new Update(
    //         $topic,
    //         json_encode(['status' => 'Mercure fonctionne!', 'message' => $message])
    //     );
          
    //     // dd( $update) ;
    //     // Publier la mise à jour
    //     try {
    //         $hub->publish($update);
    //         return new JsonResponse(['status' => 'Message publié via Mercure']);
    //     } catch (\Exception $e) {
    //         // Ajout de logs pour capturer les détails de l'erreur
    //         return new JsonResponse([
    //             'error' => 'Failed to publish update',
    //             'details' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }

       
    // }

    #[Route('/subscribe', name: 'subscribe')]
    public function subscribe(): Response
    {
        return $this->render('mercure/index.html.twig');
    }
}