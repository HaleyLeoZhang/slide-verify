<?php
/*
$msg['status'] = false; // 是否需要重新请求验证码，默认否
$msg['Err'] = 1004;     // 错误编码
$msg['out'] ;           // 编码对应输出
*/
namespace app\controller;
use Mine\Slide;     // 引入 Slide 类
class Verify{

    /**
    * 初始获取 验证码
    */
    public function init(){
        echo \Mine\Slide::instance();
    }
    /**
    * 获取验证码的html
    * @param GET   : (source)
    * @echo Html | String
    */
    public function captchar(){
        echo Slide::instance(1);
    }

    /**
    * 验证码，校验
    * @param POST  : (int)x_value 横坐标，用户滑动结果
    * @echo String : {"Err":"","out":""}
    */
    public function check(){
        // 参数过滤
        Slide::instance(2);
    }

    // 示例验证 [普通版]，示例：
    public function demo(){
        // 先验证，如 Readme.md 里面所示过程
        Slide::instance(3); 
    }

    // 示例验证 [RSA版]，示例：
    public function demo_rsa(){
        // 先验证
            // 若不通过，程序会结束，并输出对应返回信息，告诉前端，需要重新获取验证码
        Slide::instance(3); 
            // 通过，程序则继续执行
                // RSA 解密 、 [使用了加密函数的，都需要 urldecode 再次解码]
        $name = urldecode(   \Crypt\Rsa::decrypt($_POST['name'])    );
        $pwd  = urldecode(   \Crypt\Rsa::decrypt($_POST['pwd'])     );
            // 待验证的 帐号与密码
        $user_account = 'admin';
        $password = '123123';
        if( $name==$user_account &&  $pwd==$password ){
            // 验证成功时，一定要返回  {"status":true} 
            $msg['status']  = true;
                // 若验证成功后需要跳转到某个地址 {"status":true,"url":"/"} 形式输出
            $msg['url']     = '/';
            exit( json_encode($msg)  );
        }else{
            // 需要用户重新输入的时候，一定要返回  {"status":false} 
            $msg['status']  = false; 
            // 提示用户的错误信息，请以 {"status":false,"out":"这里是错误信息"} 形式输出
            $msg['out'] = '帐号或者密码不正确哟';
            exit( json_encode($msg)  );
        }
    }

}
