<?php

namespace jonasarts\Bundle\RegistryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use jonasarts\Bundle\RegistryBundle\Entity\System;
use jonasarts\Bundle\RegistryBundle\Form\Type\SystemType;

/**
 * System controller.
 *
 * @Route("/system")
 */
class SystemController extends Controller
{

    /**
     * Lists all System entities.
     *
     * @Route("/", name="system")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        //$entities = $em->getRepository('jaRegistryBundle:System')->findAll();
        $entities = $em->getRepository('jaRegistryBundle:System')->findAllOrderedBySystemKey();
        //$entities = $em->getRepository('jaRegistryBundle:System')->findAllWhere('type', '<>', 'bln');
        
        return array(
            'entities' => $entities,
            );
    }
    
    /** Displays a form to create a new System entity.
     *
     * @Route("/new", name="system_new")
     * @Template("jaRegistryBundle:System:edit.html.twig")
     */
    public function newAction()
    {
        $entity = new System();
        $form = $this->createForm(new SystemType(), $entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'create',
            );
    }
    
    /**
     * Creates a new System entity. 
     * 
     * @Route("/create", name="system_create")
     * @Method("post")
     * @Template("jaRegistryBundle:System:edit.html.twig")
     */
    public function createAction()
    {
        $entity = new System();
        
        $request = $this->getRequest();
        
        $form = $this->createForm(new SystemType(), $entity);
        
        $form->bindRequest($request);
        
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            
            $em->persist($entity);
            $em->flush();
            
            return $this->redirect($this->generateUrl('system'));
        }
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'create',
            );
    }
    
    /**
     * Displays a form to edit a System entity.
     * 
     * @Route("/edit/{id}", name="system_edit")
     * @Template("jaRegistryBundle:System:edit.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('jaRegistryBundle:System')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find System entity.');
        }
        
        $form = $this->createForm(new SystemType(), $entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'update',
            );
    }
    
    /**
     * Update a System entity.
     * 
     * @Route("/update/{id}", name="system_update")
     * @Method("post")
     * @Template("jaRegistryBundle:System:edit.html.twig") 
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        $entity = $em->getRepository('jaRegistryBundle:System')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find System entity.');
        }
        
        $form = $this->createForm(new SystemType(), $entity);
        
        $request = $this->getRequest();
        
        $form->bindRequest($request);
        
        if ($form->isValid()) {
            $em->persist($entity);
            $em->flush();
            
            return $this->redirect($this->generateUrl('system'));
        }
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'update',
            );
    }
    
    /**
     * Delete a System entity.
     * 
     * @Route("/delete/{id}", name="system_delete")
     * @Method({"get","post"}) 
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();
        
        $em = $this->getDoctrine()->getEntityManager();
        
        $entity = $em->getRepository('jaRegistryBundle:System')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find System entity.');
        }
        
        $em->remove($entity);
        $em->flush();
        
        return $this->redirect($this->generateUrl('system'));
    }
    
    /*
     * Delete system key from database.
     */
    private function delete($systemkey, $name)
    {
        $rm = $this->get('registry_manager');
        $rm->SystemDelete($systemkey, $name);
    }
    
    /*
     * Read system key from database.
     */
    public function Read($systemkey, $name, $type)
    {
        $rm = $this->get('registry_manager');
        return $rm->SystemRead($systemkey, $name, $type);
    }
    
    /*
     * Write system key to database.
     */
    public function Write($systemkey, $name, $type, $value)
    {
        $rm = $this->get('registry_manager');
        $rm->SystemWrite($systemkey, $name, $type, $value);
    }

}
