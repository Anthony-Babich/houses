<?php

namespace House\Bundle\Controller;

use House\Bundle\Form\Search\HouseType as SearchProductsType;
use House\Bundle\Entity\Search\House as SearchProducts;
use House\Bundle\Entity\ContactUS;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        $translator = new Translator($this->get('translator')->getLocale());
        $searchProducts = new SearchProducts();
        $searchForm = $this->createForm(new SearchProductsType($em, $translator), $searchProducts);

        return $this->render('homepage/index.html.twig', array(
            'newLng' => $this->get('translator')->getLocale(),

            'search_form' => $searchForm->createView(),
            'formContactUS' => $this->getContactUS(),
            'address' => $this->getSetting('address'),
            'phones' => $this->getSettingLng('phone'),
            'email' => $this->getSetting('email'),
            'centerLat' => $this->getSetting('centerLat'),
            'centerLong' => $this->getSetting('centerLong'),
            'footerdesc' => $this->getSettingLng('footer-desc'),
            'homeDescTop' => $this->getSettingLng('home-desc-top'),
            'houses' => $this->getHouses(['Buy', 'Rent', 'Buy+Rent']),
            'priceType' => ['Buy', 'Rent', 'Buy+Rent'],
            'number' => 0,

            'month' => $this->get('translator')->trans(
                'month',
                array(),
                'messages',
                $this->get('translator')->getLocale()
            )
        ));
    }

    public function ajaxAction(Request $request){
        $lang = $request->get('lang');
        $lastType = $request->get('type');
        $type[] = $lastType;
        switch ($type[0]){
            case 'Buy':{
                $type[] = 'Buy+Rent';
                break;
            }
            case 'Rent':{
                $type[] = 'Buy+Rent';
                break;
            }
            case 'Buy+Rent':{
                $type[] = 'Buy';
                $type[] = 'Rent';
                break;
            }
        }
        return $this->render('homepage/productcontainer.html.twig', array(
            'houses' => $this->getHouses($type),
            'newLng' => $lang,
            'number' => 0,
            'priceType' => $lastType,
            'month' => $this->get('translator')->trans(
                'month',
                array(),
                'messages',
                $lang
            )
        ));
    }

    public function loadMoreAjaxAction(Request $request){
        $lastType = $request->get('type');
        $type[] = $request->get('type');
        $lang = $request->get('lang');
        switch ($type[0]){
            case 'Buy':{
                $type[] = 'Buy+Rent';
                break;
            }
            case 'Rent':{
                $type[] = 'Buy+Rent';
                break;
            }
            case 'Buy+Rent':{
                $type[] = 'Buy';
                $type[] = 'Rent';
                break;
            }
        }
        $offset = $request->get('offset');
        return $this->render('homepage/productcontainer.html.twig', array(
            'houses' => $this->getMoreHouses($type, $offset),
            'priceType' => $lastType,
            'newLng' => $lang,
            'number' => random_int(1000, 2000),
            'month' => $this->get('translator')->trans(
                'month',
                array(),
                'messages',
                $lang
            )
        ));
    }

    public function contactAction()
    {
        return $this->render('contact/index.html.twig', array(
            'formContactUS' => $this->getContactUS(),
            'address' => $this->getSetting('address'),
            'footerdesc' => $this->getSettingLng('footer-desc'),
            'phones' => $this->getSettingLng('phone'),
            'email' => $this->getSetting('email'),
            'contactusdesc' => $this->getSettingLng('contactus-desc'),
        ));
    }

    private function getContactUS()
    {
        $costProject = new ContactUS();
        $language = $this->get('translator')->getLocale();

        $formCostProject = $this->createFormBuilder($costProject)
            ->add('name', TextType::class, array(
                'attr' => [
                    'placeholder' => $this->get('translator')->trans('yourname', array(), 'messages', $language),
                    'class' => 'form-control'
                ],
                'label' => false
            ))
            ->add('email', EmailType::class, array(
                'attr' => [
                    'placeholder' => $this->get('translator')->trans('youremail', array(), 'messages', $language),
                    'class' => 'form-control',
                ],
                'required' => false,
                'label' => false,
            ))
            ->add('phone', NumberType::class, array(
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->get('translator')->trans('yournumber', array(), 'messages', $language),
                    'type' => 'tel',
                ],
                'label' => false,
            ))
            ->add('message', TextAreaType::class, array(
                'attr' => [
                    'placeholder' => $this->get('translator')->trans('typeyoumessage', array(), 'messages', $language),
                    'class' => 'form-control',
                ],
                'label' => false,
            ))
            ->getForm()->createView();

        return $formCostProject;
    }

    private function getHouses(array $type)
    {
        $qb = $this->getDoctrine()->getManager()->getRepository('HouseBundle:House')
            ->createQueryBuilder('n')
            ->select('n')
            ->innerJoin('n.idSalesRent', 't')
            ->orderBy('n.created', 'DESC')
            ->where('t.title IN (:type)')
            ->setParameters(array('type' => $type))
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();
        return $qb;
    }

    private function getMoreHouses(array $type, $offset)
    {
        return $this->getDoctrine()->getManager()->getRepository('HouseBundle:House')
            ->createQueryBuilder('n')
            ->select('n')
            ->innerJoin('n.idSalesRent', 't')
            ->orderBy('n.created', 'DESC')
            ->where('t.title IN (:type)')
            ->setParameters(array('type' => $type))
            ->setFirstResult($offset)
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();
    }

    private function getSetting($param)
    {
        $res = $this->getDoctrine()->getManager()->getRepository('HouseBundle:Settings')
            ->createQueryBuilder('n')
            ->select('n')
            ->where('n.title = :param')
            ->setParameter('param', $param)
            ->getQuery()
            ->getResult();
        if(empty($res[0])){
            return 'error';
        }else{
            return $res[0]->getSetting();
        }
    }

    private function getSettingLng($param)
    {
        $trans = $this->get('translator')->getLocale();
        if ($trans == 'ru'){
            $param .= '-ru';
        }elseif ($trans == 'en'){
            $param .= '-en';
        }elseif ($trans == 'ar'){
            $param .= '-ar';
        }
        $res = $this->getDoctrine()->getManager()->getRepository('HouseBundle:Settings')
            ->createQueryBuilder('n')
            ->select('n')
            ->where('n.title = :param')
            ->setParameter('param', $param)
            ->getQuery()
            ->getResult();
        if(empty($res)){
            return 'error';
        }else{
            return $res;
        }
    }
}
