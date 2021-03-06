<?php

namespace Log210\LivraisonBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Log210\CommonBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Log210\LivraisonBundle\Entity\Menu;
use Log210\LivraisonBundle\Entity\Restaurant;
use Log210\LivraisonBundle\Form\MenuType;

/**
 * Menu controller.
 *
 * @Route("/menu")
 * @Security("has_role('ROLE_RESTAURATEUR')")
 */
class MenuController extends Controller
{
    protected function getRoutes()
    {
        return [
            'update' => 'menu_update',
            'delete' => 'menu_delete',
            'create' => 'menu_create'
        ];
    }

    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('Log210LivraisonBundle:Menu');
    }

    protected function getForm()
    {
        return new MenuType();
    }

    /**
     * Creates a new Menu entity.
     *
     * @Route("/", name="menu_create")
     * @Method("POST")
     * @Template("Log210LivraisonBundle:Menu:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $service = $this->getRepository();
        $entity = $this->getRepository()->makeEntity();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getEntityManager();
            $em->persist($entity);
            $em->flush();
        }

        return $this->jsonResponse(new Response(json_encode(['success' => $form->isValid()])));
    }


    /**
     * Edits an existing Menu entity.
     *
     * @Route("/{id}", name="menu_update")
     * @Method("PUT")
     * @Template("Log210LivraisonBundle:Menu:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $entity = $this->getRepository()->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em = $this->getEntityManager();
            $em->flush();
        }

        return $this->jsonResponse(new Response(json_encode(['success' => $editForm->isValid()])));
    }
    /**
     * Deletes a Menu entity.
     *
     * @Route("/{id}", name="menu_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity = $this->getRepository()->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find entity.');
            }

            $em = $this->getEntityManager();
            $em->remove($entity);
            $em->flush();
        }

        return $this->jsonResponse(new Response(json_encode(['success' => $form->isValid()])));
    }

    /**
     * Deletes a Restaurant entity.
     *
     * @Route("/delete/{id}", name="menu_delete_form", options={"expose"=true})
     * @Method("GET")
     * @Template("Log210CommonBundle::modalForm.html.twig")
     */
    public function deleteFormAction(Request $request, $id)
    {
        return parent::deleteFormAction($request, $id);
    }

    /**
     * Displays a form to create a new Plat entity.
     *
     * @Route("/new_modal/{restaurant}", name="menu_new_modal")
     * @Method("GET")
     * @Template("Log210CommonBundle::modalForm.html.twig")
     */
    public function newModalAction(Request $request, Restaurant $restaurant)
    {
        $entity = $this->getRepository()->makeEntity();
        $entity->setRestaurant($restaurant);

        $form = $this->createCreateForm($entity);

        return [
            'title' => 'create',
            'form'   => $form->createView(),
        ];
    }

    /**
     * Displays a form to edit an existing Plat entity.
     *
     * @Route("/{id}/edit_modal", name="menu_edit_modal", options={"expose"=true})
     * @Method("GET")
     * @Template("Log210CommonBundle::modalForm.html.twig")
     */
    public function editModalAction(Request $request, $id)
    {
        return array_merge(['title' => 'menu'], $this->editAction($request, $id));
    }
}
