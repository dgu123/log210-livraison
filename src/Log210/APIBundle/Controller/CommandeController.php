<?php

namespace Log210\APIBundle\Controller;
use Log210\APIBundle\Entity\Token;
use Log210\APIBundle\Message\Request\CommandeRequest;
use Log210\CommonBundle\Controller\BaseController;
use Log210\LivraisonBundle\Entity\Client;
use Log210\LivraisonBundle\Entity\Commande;
use Log210\LivraisonBundle\Entity\CommandePlat;
use Log210\LivraisonBundle\Entity\Plat;
use Log210\LivraisonBundle\Entity\Restaurant;
use Log210\LivraisonBundle\Entity\Restaurateur;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CommandeController
 * @package Log210\APIBundle\Controller
 * @Symfony\Component\Routing\Annotation\Route("/commandes")
 */
class CommandeController extends BaseController {

    /**
     * @param Request $request
     * @return Response
     *
     * @Symfony\Component\Routing\Annotation\Route("", name="commande_api_create")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\Method("POST")
     */
    public function createAction(Request $request) {
        $access_token = $request->headers->get("Authorization");

        $user = null;

        if ($access_token !== null) {
            $token = $this->findTokenById($access_token);
            if (is_null($token))
                return new Response('Invalid Token', Response::HTTP_UNAUTHORIZED);

            if ($token->isExpired())
                return new Response('Expired Token', Response::HTTP_UNAUTHORIZED);

            $user = $token->getUser();
        } else {
            $user = $this->getUser();
        }

        if ($user === null)
            return new Response('No Authorization', Response::HTTP_UNAUTHORIZED);

        if (!$user instanceof Client)
            return new Response("", Response::HTTP_FORBIDDEN);

        $commandeRequest = $this->convertCommandeRequest($request->getContent());

        $commandeRequest->setDate_heure(new \DateTime($commandeRequest->getDate_heure()));

        $commandeEntity = new Commande();
        $commandeEntity->setAdresse($commandeRequest->getAdresse());
        $commandeEntity->setDateHeure($commandeRequest->getDate_heure());
        $commandeEntity->setRestaurant($this->getRestaurantById($commandeRequest->getRestaurant_id()));
        $commandeEntity->setEtat(Commande::ETAT_COMMANDER);
        $commandeEntity->setClient($user);

        $this->getEntityManager()->persist($commandeEntity);

        $this->getEntityManager()->flush();

        foreach ($commandeRequest->getCommande_plats() as $commande_plat) {
            $commandePlatEntity = new CommandePlat();
            $commandePlatEntity->setCommande($commandeEntity);
            $commandePlatEntity->setQuantity($commande_plat['quantity']);
            $commandePlatEntity->setPlat($this->findPlatById($commande_plat['plat_id']));
            $commandeEntity->addCommandePlat($commandePlatEntity);

            $this->getEntityManager()->persist($commandePlatEntity);
        }

        $this->getEntityManager()->flush();

        $this->sendEmail('Confirmation de commande', "log210ete201501@mail.com", $user->getEmail(),
            "Votre commande avec le numero de confirmation " . $commandeEntity->getId() . " a ete creer");

        $this->sendTextMessage($user->getPhoneNumber(), "Votre commande avec le numero de confirmation " .
            $commandeEntity->getId() . " a ete creer");

        $response = new Response('', Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('commande_api_get', [
                'id' => $commandeEntity->getId()
            ], true),
            'Content-Type' => 'application/json'
        ]);
        return $this->render("Log210APIBundle:Commande:commande.json.twig", [
            "commande" => $commandeEntity
        ], $response);
    }

    /**
     * @return Response
     *
     * @Symfony\Component\Routing\Annotation\Route("", name="commande_api_get_all")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\Method("GET")
     */
    public function getAllAction(Request $request) {

        $filters = [];
        if ($request->query->has("etat"))
            $filters["etat"] = $request->query->get("etat");
        if ($request->query->has("livreur_id")) {
            $filters["livreur"] = $request->query->get("livreur_id");
            if ($filters["livreur"] === "null")
                $filters["livreur"] = null;
        }

        $commandeEntities = $this->getEntityManager()->getRepository("Log210LivraisonBundle:Commande")->findBy($filters);

        $response = new Response("", Response::HTTP_OK, [
            "Content-Type" => "application/json"
        ]);
        return $this->render("Log210APIBundle:Commande:commandes.json.twig", [
            "commandes" =>$commandeEntities
        ], $response);
    }

    /**
     * @param int $id
     * @return Response
     *
     * @Symfony\Component\Routing\Annotation\Route("/{id}", name="commande_api_get")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\Method("GET")
     */
    public function getAction($id) {
        $commandeEntity = $this->findCommandeById($id);

        $response = new Response('', Response::HTTP_OK, [
            'Content-Type' => 'application/json'
        ]);
        return $this->render("Log210APIBundle:Commande:commande.json.twig", [
            "commande" => $commandeEntity
        ], $response);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return Response
     *
     * @Symfony\Component\Routing\Annotation\Route("/{id}", name="commande_api_update")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\Method("PUT")
     */
    public function updateAction($id, Request $request) {
        $access_token = $request->headers->get("Authorization");

        $user = null;

        if (!is_null($access_token)) {
            $token = $this->findTokenById($access_token);
            if (is_null($token))
                return new Response("", Response::HTTP_UNAUTHORIZED);

            if ($token->isExpired())
                return new Response("", Response::HTTP_UNAUTHORIZED);

            $user = $token->getUser();
        } else {
            $user = $this->getUser();
        }

        if (is_null($user))
            return new Response("", Response::HTTP_UNAUTHORIZED);

        if (!$user instanceof Restaurateur)
            return new Response("", Response::HTTP_FORBIDDEN);

        $commandeEntity = $this->findCommandeById($id);
        if (is_null($commandeEntity))
            return new Response("", Response::HTTP_NOT_FOUND);

        $found = false;
        foreach ($user->getRestaurants() as $restaurant) {
            if ($restaurant->getId() === $commandeEntity->getRestaurant()->getId()) {
                $found = true;
            }
        }

        if (!$found)
            return new Response("", Response::HTTP_FORBIDDEN);


        $commandeRequest = json_decode($request->getContent(), true);
        $commandeEntity->setEtat($commandeRequest["etat"]);

        $this->getEntityManager()->flush();

        $this->sendTextMessage($commandeEntity->getClient()->getPhoneNumber(),
            "Votre commande avec le numero de confirmation " . $commandeEntity->getId() . " est maintenant "
            . $commandeEntity->getEtat());

        $response = new Response("", Response::HTTP_OK, [
            "Content-Type" => "application/json"
        ]);
        return $this->render("Log210APIBundle:Commande:commande.json.twig", [
            "commande" => $commandeEntity
        ], $response);
    }

    /**
     * @param string $json
     * @return CommandeRequest
     */
    private function convertCommandeRequest($json)
    {
        return $this->fromJson($json, 'Log210\APIBundle\Message\Request\CommandeRequest');
    }

    /**
     * @param int $id
     * @return Restaurant
     */
    private function getRestaurantById($id)
    {
        return $this->getEntityManager()->getRepository('Log210LivraisonBundle:Restaurant')->find($id);
    }

    /**
     * @param int $id
     * @return Plat
     */
    private function findPlatById($id)
    {
        return $this->getEntityManager()->getRepository('Log210LivraisonBundle:Plat')->find($id);
    }

    /**
     * @param string $id
     * @return Token
     */
    private function findTokenById($id)
    {
        return $this->getEntityManager()->getRepository('Log210APIBundle:Token')->find($id);
    }

    /**
     * @param int $id
     * @return Commande
     */
    private function findCommandeById($id)
    {
        return $this->getEntityManager()->getRepository('Log210LivraisonBundle:Commande')->find($id);
    }

    /**
     * @param string $phoneNumber
     * @param string $message
     */
    private function sendTextMessage($phoneNumber, $message)
    {
        $curlHandle = curl_init();

        curl_setopt($curlHandle, CURLOPT_URL, "http://textbelt.com/canada");
        curl_setopt($curlHandle, CURLOPT_POST, true);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, "number=" . urlencode($phoneNumber) . "&message=" .
            urlencode($message));
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        curl_exec($curlHandle);
        curl_close($curlHandle);
    }

    /**
     * @param $title
     * @param $from
     * @param $to
     * @param $body
     */
    private function sendEmail($title, $from, $to, $body)
    {
        $transport = \Swift_SmtpTransport::newInstance('smtp.mail.com', 587)->setUsername("log210ete201501@mail.com")
            ->setPassword("fuhrmanator")->setEncryption("tls");
        $mailer = \Swift_Mailer::newInstance($transport);
        $message = \Swift_Message::newInstance($title);
        $message->setFrom($from);
        $message->setTo(trim(strtoupper($to)));
        $message->setBody($body);
        $mailer->send($message);
    }

}
