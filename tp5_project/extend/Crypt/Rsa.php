<?php
// RSA相关
namespace Crypt;
class Rsa implements \Crypt\Basic {
	// 生成 hlz_rsa.js
	public static function bulid_rsa_js(){
		require  __DIR__.'/rsa/bulid_js.php' ;
	}

	/**
	* 公钥 加密
	* @param String : data 需要加密的数据
	* @return String: 密文
	*/
	public static function encrypt( $data='' ){
		$public_key = self::get_key('public');
		// 公钥加密
		openssl_public_encrypt(
			$data,
			$encode_result,
			$public_key
		);
		// 有的字符不兼容，所以经过了base64编码才返回
		$encode_result = base64_encode( $encode_result );
		return $encode_result ;
	}

	/**
	* 私钥 解密
	* @param String : data 需要解密的数据
	* @return String: 明文
	*/
	public static function decrypt( $data='' ){
		$private_key =  self::get_key('private');
	    openssl_private_decrypt(
	    	base64_decode( $data ), // 所以这里要先base64解码
	    	$decode_result,
	    	$private_key
	    ); 
	    return $decode_result;
	}

	/**
	* 获取 公钥|私钥
	* @param String : key_type 获取钥匙类型  public|private
	* @param Boolean: method   读出方式，true=>按行读取【为了生成前端公钥方便】,false=>直接读取文件内容
	* @return String 
	*/
	public static function get_key( $key_type , $method = false ){
		switch( $key_type ) {
			case 'public':
				if( $method ){
					return file( __DIR__.'/rsa/rsa_public_key.pem' );
				}else{
					return file_get_contents( __DIR__.'/rsa/rsa_public_key.pem' );
				}
			case 'private':
				if( $method ){
					return file( __DIR__.'/rsa/rsa_private_key.pem' );
				}else{
					return file_get_contents( __DIR__.'/rsa/rsa_private_key.pem' );
				}
			default:
				# code...
				exit('{"Err":1010}');
		}
	}


	/**
	* RSA签名
	* @param String : data 待签名数据
	* @return 签名结果
	*/
	public static function sign( $data ) {
	    $key = self::get_key('private');
	    $res = openssl_get_privatekey( $key );
	    openssl_sign(
	    	$data, 
	    	$sign, 
	    	$res
	    );
	    openssl_free_key($res);
	    //base64编码
	    $sign = base64_encode($sign);
	    return $sign;
	}

	/**
	* RSA验签
	* @param $data待签名数据
	* $sign需要验签的签名
	* 验签用支付宝公钥
	* return 验签是否通过 bool值
	*/
	public static function verify($data, $sign)  {
	    //读取支付宝公钥文件
	    $pubKey = file_get_contents('key/alipay_public_key.pem');
	 
	    //转换为openssl格式密钥
	    $res = openssl_get_publickey($pubKey);
	 
	    //调用openssl内置方法验签，返回bool值
	    $result = (bool)openssl_verify($data, base64_decode($sign), $res);
	     
	    //释放资源
	    openssl_free_key($res);
	 
	    //返回资源是否成功
	    return $result;
	}


}