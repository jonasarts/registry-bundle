<?php

namespace jonasarts\Bundle\RegistryBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use jonasarts\Bundle\RegistryBundle\Entity\Registry;
use jonasarts\Bundle\RegistryBundle\Form\Type\RegistryType;

/**
 * Registry controller.
 *
 * @Route("/registry")
 */
class RegistryController extends Controller
{

    /**
     * Lists all Registry entities.
     *
     * @Route("/", name="registry")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        //$entities = $em->getRepository('RegistryBundle:Registry')->findAll();
        $entities = $em->getRepository('RegistryBundle:Registry')->findAllOrderedByRegistryKey();
        //$entities = $em->getRepository('RegistryBundle:Registry')->findAllWhere('type', '=', 'bln');
        
        return array(
            'entities' => $entities,
            );
    }
    
    /** Displays a form to create a new Registry entity.
     *
     * @Route("/new", name="registry_new")
     * @Template("RegistryBundle:Registry:edit.html.twig")
     */
    public function newAction()
    {
        $entity = new Registry();
        $form = $this->createForm(new RegistryType(), $entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'create',
            );
    }
    
    /**
     * Creates a new Registry entity. 
     * 
     * @Route("/create", name="registry_create")
     * @Method("post")
     * @Template("RegistryBundle:Registry:edit.html.twig")
     */
    public function createAction()
    {
        $entity = new Registry();
        
        $request = $this->getRequest();
        
        $form = $this->createForm(new RegistryType(), $entity);
        
        $form->bindRequest($request);
        
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getEntityManager();
            
            $em->persist($entity);
            $em->flush();
            
            return $this->redirect($this->generateUrl('registry'));
        }
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'create',
            );
    }
    
    /**
     * Displays a form to edit a Registry entity.
     * 
     * @Route("/edit/{id}", name="registry_edit")
     * @Template("RegistryBundle:Registry:edit.html.twig")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('RegistryBundle:Registry')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Registry entity.');
        }
        
        $form = $this->createForm(new RegistryType(), $entity);
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'update',
            );
    }
    
    /**
     * Update a Registry entity.
     * 
     * @Route("/update/{id}", name="registry_update")
     * @Method("post")
     * @Template("RegistryBundle:Registry:edit.html.twig") 
     */
    public function updateAction($id)
    {
        $em = $this->getDoctrine()->getEntityManager();
        
        $entity = $em->getRepository('RegistryBundle:Registry')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Registry entity.');
        }
        
        $form = $this->createForm(new RegistryType(), $entity);
        
        $request = $this->getRequest();
        
        $form->bindRequest($request);
        
        if ($form->isValid()) {
            $em->persist($entity);
            $em->flush();
            
            return $this->redirect($this->generateUrl('registry'));
        }
        
        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
            'action' => 'update',
            );
    }
    
    /**
     * Delete a Registry entity.
     * 
     * @Route("/{id}/delete", name="registry_delete")
     * @Method({"get","post"}) 
     */
    public function deleteAction($id)
    {
        $request = $this->getRequest();
        
        $em = $this->getDoctrine()->getEntityManager();
        
        $entity = $em->getRepository('RegistryBundle:Registry')->find($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Registry entity.');
        }
        
        $em->remove($entity);
        $em->flush();
        
        return $this->redirect($this->generateUrl('registry'));
    }
    
    /*
     * Delete registry key from database.
     */
    private function delete($userid, $registrykey, $name)
    {
        $rm = $this->get('registry_manager');
        $rm->RegistryDelete($userid, $registrykey, $name);
    }

    /*
     * Read registry key from database.
     */
    public function read($userid, $registrykey, $name, $type)
    {
        $rm = $this->get('registry_manager');
        return $rm->ReadRegistry($userid, $registrykey, $name, $type);
    }
    
    /*
     * Write registry key to database.
     */
    public function write($userid, $registrykey, $name, $type, $value)
    {
        $rm = $this->get('registry_manager');
        $rm->RegistryWrite($userid, $registrykey, $name, $type, $value);
    }

}
