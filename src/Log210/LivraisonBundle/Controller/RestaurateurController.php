<?php

namespace Log210\LivraisonBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Log210\CommonBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Log210\LivraisonBundle\Entity\Restaurateur;
use Log210\LivraisonBundle\Form\RestaurateurType;

/**
 * Restaurateur controller.
 *
 * @Route("/restaurateur")
 */
class RestaurateurController extends Controller
{
    protected function getRoutes()
    {
        return [
            'show' => 'restaurateur_show',
            'new' => 'restaurateur_new',
            'update' => 'restaurateur_update',
            'delete' => 'restaurateur_delete',
            'create' => 'restaurateur_create',
            'edit' => 'restaurateur_edit'
        ];
    }

    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('Log210LivraisonBundle:Restaurateur');
    }

    protected function getForm()
    {
        return new RestaurateurType();
    }

    /**
     * Lists all Restaurateur entities.
     *
     * @Route("/", name="restaurateur")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        return parent::indexAction($request);
    }

    /**
     * Creates a new Restaurateur entity.
     *
     * @Route("/", name="restaurateur_create")
     * @Method("POST")
     * @Template("Log210LivraisonBundle:Restaurateur:new.html.twig")
     */
    public function createAction(Request $request)
    {
        return parent::createAction($request);
    }

    /**
     * Displays a form to create a new Restaurateur entity.
     *
     * @Route("/new", name="restaurateur_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction(Request $request)
    {
        return parent::newAction($request);
    }

    /**
     * Finds and displays a Restaurateur entity.
     *
     * @Route("/{id}", name="restaurateur_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction(Request $request, $id)
    {
        return parent::showAction($request, $id);
    }

    /**
     * Displays a form to edit an existing Restaurateur entity.
     *
     * @Route("/{id}/edit", name="restaurateur_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        return parent::editAction($request, $id);
    }

    /**
     * Edits an existing Restaurateur entity.
     *
     * @Route("/{id}", name="restaurateur_update")
     * @Method("PUT")
     * @Template("Log210LivraisonBundle:Restaurateur:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        return parent::updateAction($request, $id);
    }

    /**
     * Deletes a Restaurateur entity.
     *
     * @Route("/{id}", name="restaurateur_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        return parent::deleteAction($request, $id);
    }

    /**
     * Deletes a Restaurant entity.
     *
     * @Route("/unsafe/{id}", name="restaurateur_unsafe_delete")
     * @Method("GET")
     */
    public function unsafeDeleteAction(Request $request, $id)
    {
      $entity = $this->getRepository()->find($id);

      if (!$entity) {
          throw $this->createNotFoundException('Unable to find Restaurateur entity.');
      }

      $em = $this->getEntityManager();
      $em->remove($entity);
      $em->flush();

      return $this->redirect($this->generateUrl('restaurateur'));
    }
}
