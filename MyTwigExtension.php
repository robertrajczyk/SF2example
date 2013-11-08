<?php

namespace Acme\ServiceBundle\Twig\Extension;
  
class MyTwigExtension extends \Twig_Extension {

    // the magic function that makes this easy
    public function getFilters() 
    {
		return array(
            
			'showfileldsquestion' => new \Twig_Filter_Method($this, 'showfileldsquestion'), 
			'ago' => new \Twig_Filter_Method($this, 'ago'), 
        );
    }
 
    // your custom function question
    public function showfileldsquestion($str) 
    {
		$str = ''.$str ; 
		$first = '<textarea id="field1" class="fill" rows="1" cols="20"></textarea>';
		$secound = '<textarea id="field2" class="fill" rows="1"  cols="20"></textarea>';
		$third = '<textarea id="field3" class="fill" rows="1" cols="20"  ></textarea>';
		 
		$str =  preg_replace('/_/', $first, $str, 1);
		$str = preg_replace('/_/', $secound, $str, 1);
		$str = preg_replace('/_/', $third, $str, 1);
		
		return $str ; 
    }
 
	// your custom function
    public function ago($str) 
    {
	   $d = time() - $str;
      	if ($d < 60)
			return $d." second".(($d==1)?'':'s')." ago";
		else
		{
			$d = floor($d / 60);
			if($d < 60)
				return $d." minute".(($d==1)?'':'s')." ago";
			else
			{
				$d = floor($d / 60);
				if($d < 24)
					return $d." hour".(($d==1)?'':'s')." ago";
				else
				{
					$d = floor($d / 24);
					if($d < 7)
						return $d." day".(($d==1)?'':'s')." ago";
					else
					{ 
						$d = floor($d / 7);
						if($d < 4)
							return $d." week".(($d==1)?'':'s')." ago";
						else
						{
							$d = floor($d / 4);
							if($d < 12)
								return $d." month".(($d==1)?'':'s')." ago";
							else
							{
								$d = floor($d / 12);
								return $d." year".(($d==1)?'':'s')." ago";
								
							}
						}
					}//Week
				}//Day
			}//Hour
		}//Minute
    }
	 
    // for a service we need a name
    public function getName()
    {
        return 'my_twig_extension';
    }

}