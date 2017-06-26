## 云天河 - 滑动验证码

目前只兼容webkit内核浏览器

很遗憾，移动端目前兼容性不好！

演示地址 [http://mall.hlzblog.top/test/slide_verify](http://mall.hlzblog.top/test/slide_verify)

QQ交流群 [399073936](http://shang.qq.com/wpa/qunwpa?idkey=c09cd4c9fbdf5909136208cc93ae2e26b22ec48dbd2583cfdc48d82dde07186b)

本次的完整案例 基于thinkphp5 [ 但并未包含thinkphp5框架本身 请自行引入测试]

目录位于 /tp5_project

当你使用时，无需依赖任何框架


##### RSA方式的登陆验证

示例如下

	<body>
	    <h1><a href="https://github.com/HaleyLeoZhang/slide-verify"  target="_blank">滑动验证码 - RSA版</a></h1>
	    <form id="form_check" >
	        <input type="text"     class="input-text" name='name' size="30" placeholder="用户名，测试帐号：admin"  />
	        <input type="password" class="input-text" name='pwd'  size="30" placeholder="密码，测试密码：123123"   />
	    </form>
	    <!-- 请不要改此处的id，因为滑动验证码的css已经基于此id，在初始化时定义 -->
	    <div id="yth_captchar"></div>
	    <script type="text/javascript" src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script>
	    <script type="text/javascript" src="http://cdn.bootcss.com/loadjs/3.5.0/loadjs.min.js"></script>
	    <script type="text/javascript" src="http://cdn.bootcss.com/layer/3.0.1/layer.min.js"></script>
	    <script type="text/javascript" src="/static/js/hlz_rsa.js"></script>
	    <script>
	    loadjs(["/static/plugins/verify/js/min_drag.js"], {
	        success: function() {
	            // 异步初始化验证码
	            $.ajax({
	                "url": "/Verify/init", // 获取初始的验证码 `css + 验证码图片` 的地址
	                "success": function(html) {
	                    $("#yth_captchar").html(html);
	                    $(this).yth_drag({
	                        "verify_url": "/Verify/check",
	                        "source_url": "/Verify/captchar",
	                        "auto_submit": true,
	                        "submit_url": "/Verify/demo_rsa",
	                        "form_id": "form_check",
	                        "crypt_func": "rsa_encode"
	                    });
	                }
	            });
	            // 适应当前样式
	            $("#yth_captchar").css({
	                "margin-left": "10px",
	                "width": "280px",
	                "margin-top": "20px"
	            });
	        }
	    });
	    </script>
	</body>



##### 用户拖动滑动验证码后的示例验证，如下

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
