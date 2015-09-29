<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 




class Simple_Encry { 
	public function encode($string, $key = '') {
		if('' == $string) return '';
		if('' == $key) $key = 'rocking';
		$len1 = strlen($string);
		$len2 = strlen($key);
		if($len1 > $len2) $key = str_repeat($key, ceil($len1 / $len2));
		return base64_encode($string ^ $key);
	}


	public	function decode($string, $key = '') {
		if('' == $string) return '';
		if('' == $key) $key = 'rocking';

		$string = base64_decode($string);
		$len1 = strlen($string);
		$len2 = strlen($key);
		if($len1 > $len2) $key = str_repeat($key, ceil($len1 / $len2));
		return $string ^ $key;
	}

}  

/* End of file simple_encry.php */
