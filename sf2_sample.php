<?php

/*********************ServiceController.php************************/	

//INDEX, CACHE, CULTURE CHANGE
	
	/**
     * @Route("/", name="_index")
     * @Template()
     */	
	public function indexAction($lang = 'xx')
    {
		if( $lang != 'xx')
			$this->get('session')->setLocale($lang);
		  
		$lang = $this->get('session')->getLocale(); 
  
		$page_content =$this->getDoctrine()->getRepository('AcmeServiceBundle:Pages')->findOneBy( array('pTitle' => 'Index', 'pLang' => $lang));
		
		$question_day =$this->getDoctrine()->getRepository('AcmeServiceBundle:QuestionDay')->findOneBy( array('lang' => $lang, 'day'=>date('Y-m-d')));
		$question_id = $question_day->getQuestion()->getId();
 
		$question = $this->getDoctrine()->getRepository('AcmeServiceBundle:Questions')->findOneBy( array('id' => $question_id));
		$responses = $this->getDoctrine()->getRepository('AcmeServiceBundle:Responses')->findBy( array('question' => $question_id, 'language' => $lang, 'visibility' => 1)  );
		
		//RANDOM RESPONSES WITH HIGH RANKING
		$random_hight = $this->getDoctrine()->getRepository('AcmeServiceBundle:Responses')->hightRanking( 3, 2, $lang);
		
		//URL STRUCTURE
		$lang_link = $this->checklang($lang);
		
		//Check users ratings
		$ratings = array();
		if( $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY') )
			$ratings = $this->getRatings(); 
		  
		  
		$response_a  = array('ratings'=>$ratings, 'page_content'=>$page_content, 'lang'=>$lang_link,'question'=>$question, 'random_hight'=>$random_hight, 'responses'=>$responses, 'no_margin'=>1 ) ;
 
	 	$response = $this->render('AcmeServiceBundle:Service:index.html.twig',  $response_a);
		 
		$response->setPublic();
		$response->setSharedMaxAge(60);
		
		return $response; 
		 
	}

/*********************WidgetController.php************************/	
 
use Acme\ServiceBundle\Entity\Rankings;
use Acme\ServiceBundle\Entity\Users;

//Add vote by ajax - response JSON

	/**
     * @Route("/addvote_s1_ajax", name="_addvote_s1_ajax")
     * @Template()
     */
    public function addvote_s1_ajaxAction()
    {
	    $fill = $_GET['fill']; 
		$u_id = $this->get('security.context')->getToken()->getUser()->getId();
		  
		//Check if the user has written his name
		if($fill=="")
		{
			$return=array("responseCode"=>400, "greeting"=>$this->get('translator')->trans("You have to choose fill!")); 
		}  
		else 
		{
			$em = $this->getDoctrine()->getEntityManager();
			
			$users_responses = $this->getDoctrine()->getRepository('AcmeServiceBundle:Users')->findOneById( $u_id );
			$username = $users_responses->getUsername();
			
			//Add ranking	 	 
			$ranking = new Rankings();
			$ranking->setResponce($fill_responses);   
			$ranking->setUser($users_responses);
			$ranking->setCreated( new \DateTime('now') );   
			$ranking->setValue(1);  
				
			$em->persist($ranking);
			$em->flush();
		
			$greeting=$this->get('translator')->trans("Vote added");
				
			$return=array("responseCode"=>200, "greeting"=>$greeting,  "fill"=>$fill,  "username"=>$username  );
		}
 
		$callback = $_GET['callback'];
		$response = json_encode($return);

		echo $callback . "(" . $response . ")";

		die();
    }
	
// Get Question of the day - ajax

	/**
	 * @Route("/qotd_s1_ajax", name="_reload_qotd_s1_ajax")
     * @Template()
     */
	public function qotd_s1_ajaxAction()
    {  
		$lang = $_GET['lang'];

		$question_day =$this->getDoctrine()->getRepository('AcmeServiceBundle:QuestionDay')->findOneBy( array('lang' => $lang, 'day'=>date('Y-m-d')));
		$question_id = $question_day->getQuestion()->getId();
		
		$question = $this->getDoctrine()->getRepository('AcmeServiceBundle:Questions')->findOneBy( array('id' => $question_id));
		
		$content = $this->render('AcmeServiceBundle:Widget:qotd_s1.html.twig', array('lang'=>$lang , 'question' => $question ));
		$content = $content->getContent(); 

		$return = array( "responseCode"=>$content ) ;

		$callback = $_GET['callback'];
		$response = json_encode($return);

		echo $callback . "(" . $response . ")";

		die();
	}

/*********************ResponsesRepository.php************************/	
	
namespace Acme\ServiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ResponsesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ResponsesRepository extends EntityRepository
{
	public function  hightRanking($number = 2, $ranking = 1, $lang)
    { 
		$em = $this->getEntityManager();
  
 		return $em->createQuery('
		SELECT   r, u,  q  FROM AcmeServiceBundle:Responses r
			JOIN r.user u 
			JOIN r.question q  
			
			WHERE r.ranking >= :ranking and r.language = :lang  and r.visibility = 1
		 
			ORDER BY r.ranking DESC, q.id ASC
		') 
			->setParameter('ranking',$ranking) 
			->setParameter('lang',$lang)			
			->setMaxResults($number) 
            ->getResult();

    } 
	
		/**
     * @Route("/startquestion", name="_start_question")
     * @Template()
     */
    public function startquestionAction()
    {
		$form = $this->get('form.factory')->create(new QuestionType());
		$lang = $this->get('session')->getLocale(); 
		$request = $this->get('request');
		
		$page_content =$this->getDoctrine()->getRepository('AcmeServiceBundle:Pages')->findOneBy( array('pTitle' => 'Start a Fill', 'pLang' => $lang));
	  
		if ($request->getMethod() == 'POST') {
		
			$form->bindRequest($request); 
			$data = $form->getData();
			
			if ($form->isValid()) { 

				$em = $this->getDoctrine()->getEntityManager();
			 
			 	//Questions
			 	$question = new Questions();
					
				$text = $data['content'];
				$text = trim(implode(' ', preg_split('/\s+/', $text)));
				$text = strip_tags($text);
						
				//Get user
				$user = $this->getDoctrine()->getRepository('AcmeServiceBundle:Users')->findOneById( $this->get('security.context')->getToken()->getUser()->getId() );
						
				//Set Question
				$question->setContent($text);
				$question->setUser($user); 
				$question->setCreated( new \DateTime('now') );
				$question->setActive(1);
				$question->setLanguage($lang);
				$question->setVisits(0);
				$question->setVisibility(1);
				$question->setIndexme(1);
						 
				$em->persist($question);
				$em->flush(); 
						
				$this->get('session')->setFlash('notice', $this->get('translator')->trans("New Blank added!")); 
				return $this->redirect('/'.$question->getId());
			}
			else{
				$this->get('session')->setFlash('notice', $this->get('translator')->trans("This form has errors"));
			}
			
		}//end if request method == POST	
	    	
    return  array("form"=>$form->createView(), 'page_content'=>$page_content);
    }	
}
	
/*********************QuestionType.php************************/	

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add("content","textarea",array('required' => true,'attr' => array( 'style'=>'width:100%', 'rows' => 3, 'onkeyup'=>"doPreview();",'autocomplete'=>"off")));
        $builder->add('isPreviewPhoto', 'hidden',array('required' => false,'attr' => array('value'=>0)));
    }
 
	public function getDefaultOptions(array $options)
    {
        $collectionConstraint = new Collection(array(
            'content' => array(new MaxLength(255) , new MinLength(1), new Underscore() ),
			'isPreviewPhoto' => new MinLength(1) 
        ));
        
       return array('validation_constraint' => $collectionConstraint, 'csrf_protection' => false);
    }
  
    public function getName()
    {
        return 'question';
    }
}
?>