<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 
/* 
DES�����������㷨
*/
class DesEncry { 
	/*
	����ܾ��ǰ����ķָ��һ������С��ȵĿ飬ͨ������ 8 �ֽ�Ϊ������ 1 ����λ�����磬DES �Ŀ��С�� 64 bits��Ҳ���� 8 �ֽڡ�
	��Ϊÿ���鶼�ǹ̶��ĳ��ȣ��������Ҫ���ܵ����ݲ��ǿ鳤�ȵ��������Ļ�������Ҫ����䡣��������ȫ�㣬������ȫһ��Ҳ�������������ר�ŵķ������ڴ˲������ܡ�
	���������䣬��ô���Ļ�����ĳ������ֽڣ����ָ�������ʱ������һ����ǰѽ��ܺ�����ݽضϣ��������ݣ�������䣬�������ݵ������Եõ��˱�֤	
	����պ���8���ֽڵ��������Ļ�����ô���ĺ����ĵĳ���Ӧ�������,������ָ�� ECB ��Ϊ����ģʽ
	����� CBC����ô������һ�� IV block (����),�����ȫһЩ��
	*/
 
    var $key;  
    var $iv; //ƫ����/����	
  
    public function DesEncry($params/*$key, $iv=0*/)  
    {  
		$this->key = $params['key'];
		$iv = $params['iv'];

        if($iv == 0) {  
            $this->iv = $this->key;  
        }
        else {  
            $this->iv = $iv;  
        }  
    }  
 
	//ʵ�ַ����� mcrypt_cbc ��������
	public function mcrypt_cbc($key, $str, $iv){
		$td = mcrypt_module_open('rijndael-256', '', 'cbc', '');

  		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND); 

		mcrypt_generic_init($td, $key, $iv);
		$encryData = mcrypt_generic($td, $str);
		//exit($encryData);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $encryData;
	}

    //����   
    public function encrypt($str) {         
        $size = mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_CBC );  
        $str = $this->pkcs5Pad ( $str, $size );  
          
		//exit($this->key);
        //$data=mcrypt_cbc(MCRYPT_DES, $this->key, $str, MCRYPT_ENCRYPT, $this->iv);  
		$data = $this->mcrypt_cbc($this->key, $str, $this->iv);
        return base64_encode($data);  
		//return $data;
    }  
      
    //����   
    public function decrypt($str) {  
        $str = base64_decode ($str);  
        //$str = mcrypt_cbc(MCRYPT_DES, $this->key, $str, MCRYPT_DECRYPT, $this->iv );
		$str = $this->mcrypt_cbc($this->key, $str, $this->iv);
        $str = $this->pkcs5Unpad( $str );  
        return $str;  
    }  
  
    function hex2bin($hexData) {  
        $binData = "";  
        for($i = 0; $i < strlen ( $hexData ); $i += 2)  
        {  
            $binData .= chr(hexdec(substr($hexData, $i, 2)));  
        }  
        return $binData;  
    }  
  
    function pkcs5Pad($text, $blocksize) {  
        $pad = $blocksize - (strlen ( $text ) % $blocksize);  
        return $text . str_repeat ( chr ( $pad ), $pad );  
    }  
  
    function pkcs5Unpad($text) {  
        $pad = ord ( $text {strlen ( $text ) - 1} );  
        if ($pad > strlen ( $text ))  
            return false;  
        if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)  
            return false;  
        return substr ( $text, 0, - 1 * $pad );  
    }  
}  

/* End of file desencry.php */
