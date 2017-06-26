<?php
// 简单对称加密
namespace Crypt;
class yth_crypt implements \Crypt\Basic {
    protected static $_config_crypt_1 = [
        '+', '-', '*' , '/'
    ];
    protected static $_config_crypt_2 = [
        'tcv', 'qwrj', 'psd'
    ];
    /**
     * 加密
     * @param String : str 数据 [纯字母至少8位，汉字一位即可]
     * @param String : my_key 加密密钥
     * @return String 密文
     */
    public static function encrypt($str = '', $my_key = 'https://github.com/HaleyLeoZhang/slide-verify') {
        $strArr = str_split(base64_encode(  urlencode($str)  ));
        $strCount = count($strArr);
        foreach (str_split($my_key) as $key => $value)
            $key < $strCount && $strArr[$key].=$value;
        return str_replace( self::$_config_crypt_1 , self::$_config_crypt_2, join('', $strArr));
    }

    /**
     * 解密
     * @param String : str 数据
     * @param String : my_key 解密密钥
     * @return String 明文
     */
    public static function decrypt($str = '', $my_key = 'https://github.com/HaleyLeoZhang/slide-verify') {
        $strArr = str_split(str_replace(  self::$_config_crypt_2 , self::$_config_crypt_1 , $str), 2);
        $strCount = count($strArr);
        foreach (str_split($my_key) as $key => $value)
            $key <= $strCount && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return urldecode(   base64_decode(join('', $strArr))   );
    }

}