<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 
/* 
DES带向量加密算法
*/
class DesEncry { 
	/*
	块加密就是把明文分割成一个个大小相等的块，通常是以 8 字节为倍数是 1 个单位。比如，DES 的块大小是 64 bits，也就是 8 字节。
	因为每个块都是固定的长度，所以如果要加密的数据不是块长度的整倍数的话，就需要做填充。填充可以填全零，可以填全一，也可以填随机，有专门的方案，在此不做介绍。
	如果做了填充，那么密文会比明文长几个字节，但恢复成明文时可以用一个标记把解密后的数据截断，留下数据，丢弃填充，这样数据的完整性得到了保证	
	如果刚好是8个字节的整数倍的话，那么明文和密文的长度应该相等了,但必须指定 ECB 作为操作模式
	如果是 CBC，那么还会多出一个 IV block (向量),会更安全一些。
	*/
 
    var $key;  
    var $iv; //偏移量/向量	
  
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
 
	//实现废弃的 mcrypt_cbc 函数功能
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

    //加密   
    public function encrypt($str) {         
        $size = mcrypt_get_block_size ( MCRYPT_DES, MCRYPT_MODE_CBC );  
        $str = $this->pkcs5Pad ( $str, $size );  
          
		//exit($this->key);
        //$data=mcrypt_cbc(MCRYPT_DES, $this->key, $str, MCRYPT_ENCRYPT, $this->iv);  
		$data = $this->mcrypt_cbc($this->key, $str, $this->iv);
        return base64_encode($data);  
		//return $data;
    }  
      
    //解密   
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
