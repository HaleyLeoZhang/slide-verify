<?php
//++++++++++++++++++++++++++++++++++++++++++++++++++
// [中|英] 云天河——滑动验证码 
// Link: http://www.hlzblog.top/
//++++++++++++++++++++++++++++++++++++++++++++++++++
/** 示例
\Mine\Slide::instance(1); // String 获取验证码的html
\Mine\Slide::instance(2); // JsonString  验证失败|成功   => {["Err"=>"状态码","out"=>"对应的错误信息"]}
\Mine\Slide::instance(3); // Array  表单提交时验证  => ["status"=> true|false,,"Err"=>"状态码","out"=>"对应的错误信息"]
*/
namespace Mine;
class Slide{
    /**
    *  验证码配置
    */
    private static $session_name = '__Slide_Verify_x';  // 存，待验证的横坐标值的session
    private static $session_data = '__Slide_Verify__';  // 存，各种状态信息的session
    private static $post_x_name  = 'x_value'         ;  // POST变量：滑动的横坐标
    private static $times_max = 3   ; // 单个验证图片，最多可验证次数
    private static $timer     = 60  ; // 验证码的时效，单位s
    private $source_pic_count = 5   ; // 原始图片库有多少张png[要求规格260x116]，从编号1开始
    /**
    * 自动删除临时资源
    */
    private $del_count      = 5     ;   // 每次删除几个目录或内容，
    private $auto_del_tmp   = true  ;   // 开关：(Boolean)false关闭，true打开
    /**
    * 系统配置
    */
    private $pic_info;
    private $dir;
    private $tailoring_w;     	// 截图宽
    private $tailoring_h;     	// 截图高
    private $ResourcesPath;    	// 资源图片路径
    private $smallPicName;		// 小图片名称
    private $bigPicName;   		// 大图图片名称
    private $srcPic;     		// 参照图片
    private $location_x; 		// 随机X位置
    private $location_y; 		// 随机Y位置
    private $get_png_info;      // 随机图片的相关信息
    private $pic_suffix;        // 生成的图片，后缀
    private $tailoring_big_save_path;   // 拼图保存路径
    private $tailoring_small_save_path; // 小截图保存路径
    
    /**
    * 配置的路径  这里相对于入口文件
    * @return Void
    */
    private function init_core(){
        //  背景图片存放目录 （Resources）
        $this->ResourcesPath = './static/plugins/verify/img/'  ;
        //  被处理图片 存放目录，相对于入口文件（裁剪小图，错位背景图）
        $this->dir = $this->ResourcesPath. 'tmp/' .time(); 
    }

    /**
    * 程序入口
    * @param Int  : type  1=>初始化，2=>验证失败|成功，3=>表单提交时验证
    * @param Int  : more  配置参数 more(2) => more(3) => 是否关闭接口验证方式
    */
    public static function instance($option = 1, $more=8 ){
        $init = [
            "need_new"  =>  false ,     // 重置验证码的标识, false=>不需要
            "count"     =>  0 ,         // 验证最大次数，默认6次
            "timer"     =>  time(),     // 验证码的时效，默认一分钟
            "success"   =>  false,      // 是否已通过验证，默认否
            "status"    =>  false       // 是否需要重新请求验证码，默认否
        ];
        $back_data = [
            "status"=> false,// 是否需要换验证图片
            "Err"   => 2007, // 编码值，值为2000表示验证成功
            "out"   => self::get_lan_pack()['code']['c_2007'] // 输出错误信息
        ];
        if( isset($_SESSION[self::$session_data]) ){
            $this_session = json_decode($_SESSION[self::$session_data],true);
            $config = array_merge($init,$this_session) ;
        }else{
            $config = $init ;
        }
        switch( $option ){
            case 1:
                $_SESSION[self::$session_data] = json_encode($init) ;
                $obj = new self;
                return $obj->render();
            case 2:
                if(  !isset( $_POST[self::$post_x_name] )  ){
                    $msg = $back_data;
                }else{
                    $msg = self::validate($config, $_POST[self::$post_x_name], $more);
                }
                echo json_encode($msg);
                exit();
            case 3:
                $msg = self::if_success($config); 
                if( true==$msg['status'] ){
                    echo json_encode($msg);
                    exit();
                }
                return $msg;
            default:
                exit(self::get_lan_pack()['options']);
        }
    }

    /**
    * 输出经过渲染的 DIV与CSS
    * @return String  输出经过html渲染后的div+css，js需要自己写
    */
    private function render(){
        $data = $this->get_png_info;
        // 分割为左边图片与右边图片
        $pic_temp = array_chunk($data['data'],20);
        $pg_bg    = $data['bg_pic'] ;
        $ico_pic  = $data['ico_pic'];
        $y_point  = $data['y_point'];
        // 渲染 上
        $out_div = '<style>#drag{ position: relative; background-color: #e8e8e8; width: 260px; height: 34px; line-height: 34px; text-align: center;}#drag .handler{ position: absolute; top: 0px; left: 0px; width: 40px; height: 32px; border: 1px solid #ccc; cursor: pointer;}.handler_bg{ background: #fff url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA3hpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo0ZDhlNWY5My05NmI0LTRlNWQtOGFjYi03ZTY4OGYyMTU2ZTYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NTEyNTVEMURGMkVFMTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NTEyNTVEMUNGMkVFMTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo2MTc5NzNmZS02OTQxLTQyOTYtYTIwNi02NDI2YTNkOWU5YmUiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NGQ4ZTVmOTMtOTZiNC00ZTVkLThhY2ItN2U2ODhmMjE1NmU2Ii8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+YiRG4AAAALFJREFUeNpi/P//PwMlgImBQkA9A+bOnfsIiBOxKcInh+yCaCDuByoswaIOpxwjciACFegBqZ1AvBSIS5OTk/8TkmNEjwWgQiUgtQuIjwAxUF3yX3xyGIEIFLwHpKyAWB+I1xGSwxULIGf9A7mQkBwTlhBXAFLHgPgqEAcTkmNCU6AL9d8WII4HOvk3ITkWJAXWUMlOoGQHmsE45ViQ2KuBuASoYC4Wf+OUYxz6mQkgwAAN9mIrUReCXgAAAABJRU5ErkJggg==") no-repeat center;}.handler_ok_bg{ background: #fff url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA3hpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo0ZDhlNWY5My05NmI0LTRlNWQtOGFjYi03ZTY4OGYyMTU2ZTYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NDlBRDI3NjVGMkQ2MTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NDlBRDI3NjRGMkQ2MTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDphNWEzMWNhMC1hYmViLTQxNWEtYTEwZS04Y2U5NzRlN2Q4YTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NGQ4ZTVmOTMtOTZiNC00ZTVkLThhY2ItN2U2ODhmMjE1NmU2Ii8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+k+sHwwAAASZJREFUeNpi/P//PwMyKD8uZw+kUoDYEYgloMIvgHg/EM/ptHx0EFk9I8wAoEZ+IDUPiIMY8IN1QJwENOgj3ACo5gNAbMBAHLgAxA4gQ5igAnNJ0MwAVTsX7IKyY7L2UNuJAf+AmAmJ78AEDTBiwGYg5gbifCSxFCZoaBMCy4A4GOjnH0D6DpK4IxNSVIHAfSDOAeLraJrjgJp/AwPbHMhejiQnwYRmUzNQ4VQgDQqXK0ia/0I17wJiPmQNTNBEAgMlQIWiQA2vgWw7QppBekGxsAjIiEUSBNnsBDWEAY9mEFgMMgBk00E0iZtA7AHEctDQ58MRuA6wlLgGFMoMpIG1QFeGwAIxGZo8GUhIysmwQGSAZgwHaEZhICIzOaBkJkqyM0CAAQDGx279Jf50AAAAAABJRU5ErkJggg==") no-repeat center;}#drag .drag_bg{ background-color: #FFE7BA; height: 34px; width: 0px;}#drag .drag_text{color:black;    font-family: 微软雅黑;font-weight: 600;letter-spacing: 1px;position: absolute;top: 0px;width: 260px;font-size: 14px;-moz-user-select: none;-webkit-user-select: none;user-select: none;-o-user-select: none;-ms-user-select: none;}.gt_cut_fullbg_slice { float: left; width: 13px; height: 58px; margin: 0 !important; border: 0px; padding: 0 !important; background-image: url('.$pg_bg.'); } .xy_img_bord{ -webkit-box-shadow:0 0 15px #0CC; -moz-box-shadow:0 0 15px #0CC; box-shadow:0 0 15px #0CC; } #xy_img{ background-image: url('.$ico_pic['url'].');z-index: 999;width:'.$ico_pic['w'].'px;height:'.$ico_pic['h'].'px;position: relative; }#yth_captchar{background-color: #EEB4B4;border-radius:10px;width:270px;height: 150px;padding-left: 10px;}</style><div><div style="width:260px;height:116px;" id="Verification" >';
        // 渲染 中
        foreach ($pic_temp as $temp ) {
            foreach($temp as  $vo){
                $left_symbol  = $vo[0] == 0 ?'':'-';
                $right_symbol = $vo[1] == 0 ?'':'-';
                $out_div .= '<div class="gt_cut_fullbg_slice" style="background-position:'.$left_symbol.$vo[0].'px '.$right_symbol.$vo[1].'px;"></div>';
            }
        }
         // 渲染 下
        $out_div .= '<div style="top: '.$y_point.'px;left: 0px;display: none;border: 1px solid white;box-shadow: 5px 3px black;" id="xy_img" class="xy_img_bord"></div></div><div id="drag" style="width: 260px;"><div class="drag_bg"></div><div class="drag_text" id ="" onselectstart="return false;" unselectable="on">'.self::get_lan_pack()['slide_info'].'</div><div class="handler handler_bg"></div></div></div>';
        return $out_div;
    }
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //                       分割线，下面的不用管
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    /**
    * 构造函数
    * @return void
    */
    private function __construct(){
        $this->init_core();
        $this->init_variable();
        // 获取原图的 宽高
        $this->pic_info = $this->get_pic_wide_height($this->srcPic);
        // 剪裁操作
        $this->set_rand_loc( $this->pic_info);
        $this->tailoring($this->srcPic,$this->smallPicName,$this->location_x, $this->location_y,$this->tailoring_w,$this->tailoring_h);
        $this->merge_pic($this->srcPic,$this->smallPicName,$this->bigPicName,$this->location_x, $this->location_y); 
        $this->restruct_cut();
    }

    /**
    * 初始化相关数据
    * @return void
    */
    protected function init_variable(){
        $this->tailoring_w = mt_rand(40,40);
        $this->tailoring_h = mt_rand(40,40);
        $this->tailoring_big_save_path   = $this->dir.'/big/';
        $this->tailoring_small_save_path = $this->dir.'/small/';
        $this->pic_suffix = '.png' ;
        $this->get_rand_png();
        $this->smallPicName  =  $this->tailoring_small_save_path.md5(uniqid()).$this->pic_suffix;
        $this->bigPicName    =  $this->tailoring_big_save_path.md5(uniqid().time()).$this->pic_suffix;
        // 检查目录是否存在
        if( !file_exists( $this->tailoring_big_save_path) ){
            mkdir($this->tailoring_big_save_path,0777,true);
        }
        if(!file_exists( $this->tailoring_small_save_path) ) {
            mkdir($this->tailoring_small_save_path,0777,true);
        }
        // 自动删除图片资源，默认关闭
        if( $this->auto_del_tmp ){
            $get_tmp_dir = dirname($this->dir);
            $this->rec_del_dir( $get_tmp_dir );
        }
    }

    /**
    * 随机 X与Y位置 用于验证滑动值
    * @param  Array : pic_info
    * @return Float
    */
    protected function set_rand_loc($pic_info){
        $this->location_x =  mt_rand(30,$pic_info['w']-$this->tailoring_w);
        $this->location_y =  mt_rand(5,$pic_info['h']-$this->tailoring_h);
    }

    /**
    * 随机获取背景图片
    * @return String 返回图片资源地址
    */
    private function get_rand_png(){
        $this->srcPic  = $this->ResourcesPath.$this->bigPicName.mt_rand(1,$this->source_pic_count).'.png';
        if( !file_exists($this->srcPic ) ) {
            exit('{"Err":2006}');
        }else{
            return  $this->srcPic;
        }
    }

    /**
    * 获取图片宽、高
    * @param  String : pic_path
    * @return Array  : 结果集
    */
    protected function get_pic_wide_height( $pic_path ){
        $lim_size = getimagesize($pic_path);
        return array('w'=> $lim_size[0], 'h'=> $lim_size[1]);
    }

    /**
    * 裁剪小图
    * @param  String : srcFile
    * @param  String : picName
    * @param  Int    : tailoring_x
    * @param  Int    : tailoring_y
    * @param  Int    : PicW
    * @param  Int    : PicH
    * @return Void
    */
    protected function tailoring($srcFile,$picName,$tailoring_x,$tailoring_y,$PicW,$PicH){
        if( $this->pic_suffix == '.webp' ) {
            $imgStream = file_get_contents($srcFile);
            $srcIm = imagecreatefromstring($imgStream);
        } else {
            $srcIm = @imagecreatefrompng($srcFile);
        }
        $dstIm   = @imagecreatetruecolor($PicW,$PicH) or die('{"Err":2005}');
        $dstImBg = @imagecolorallocate($dstIm,255,255,255);
        imagefill($dstIm,0,0,$dstImBg); //创建背景为白色的图片
        imagecopy($dstIm,$srcIm,0,0,$tailoring_x,$tailoring_y,$PicW,$PicH);
        //imagewebp($dstIm,$picName,100);
        imagepng($dstIm,$picName);
        imagedestroy($dstIm);
        imagedestroy($srcIm);
    }

    /**
    * 去色合并块状,指定位置
    * @param  String: srcFile
    * @param  String: smallPicName
    * @param  String: picName
    * @param  Int   : tailoring_x
    * @param  Int   : tailoring_y
    * @return Void
    */
    protected function merge_pic($srcFile,$smallPicName,$picName,$tailoring_x,$tailoring_y){
        $src_lim  = imagecreatefrompng($srcFile);
        $lim_size = getimagesize($smallPicName); //取得水印图像尺寸，信息
        $border   = imagecolorat ($src_lim,5, 5);
        $red      = imagecolorallocate($src_lim, 0, 0, 0);
        imagefilltoborder($src_lim, 0, 0, $border, $red);

        $im_size = getimagesize($srcFile);
        $src_w   = $im_size[0];
        $src_h   = $im_size[1];
        $src_im  = imagecreatefrompng($srcFile);
        $dst_im  = imagecreatetruecolor($src_w,$src_h);
        //根据原图尺寸创建一个相同大小的真彩色位图
        $white   = imagecolorallocate($dst_im,255,255,255);//白
        //给新图填充背景色
        $black   = imagecolorallocate($dst_im,0,0,0);//黑
        $red     = imagecolorallocate($dst_im,255,0,0);//红
        $orange  = imagecolorallocate($dst_im,255,85,0);//橙
        imagefill($dst_im,0,0,$black);
        imagecopymerge($dst_im,$src_im,0,0,0,0,$src_w,$src_h,100);//原图图像写入新建真彩位图中
        $src_lw = $tailoring_x; //水印位于背景图正中央width定位
        $src_lh = $tailoring_y; //height定位
        imagecopymerge($dst_im,$src_lim,$src_lw,$src_lh,0,0,$lim_size[0],$lim_size[1],66);// 合并两个图像，设置水印透明度$waterclearly
        imagecopymerge($dst_im,$src_lim,$src_lw+2,$src_lh+2,0,0,$lim_size[0]-4,$lim_size[1]-4,33);
        imagepng($dst_im,$picName); //生成图片 定义命名规则
        imagedestroy($src_lim);
        imagedestroy($src_im);
        imagedestroy($dst_im);
    }

    /**
    * 图片切割、打乱、重组 ，并把结果信息赋给内部变量
    * @return Array: 结果集
    */
    protected function restruct_cut(){
        // 配置信息
        $srcFile        = $this->bigPicName;
        $bigPicName     = $this->bigPicName;
        $thumbnailWide  = $this->pic_info['w'];
        $thumbnailHeight= $this->pic_info['h'];
        // 开始剪裁工作
        $num_w = 20;
        $num_h = 2;
        //每张小图宽度，高度
        $number_wide   = $thumbnailWide/$num_w;
        $number_height = $thumbnailHeight/$num_h;
        $p_x =0;
        $p_y =0;
        for($y=0;$y<$num_h;$y++) {
            for($x=0;$x<$num_w;$x++) {
                if( $p_x >= $thumbnailWide ) {
                    $p_x = 0;
                }
                $data_source[] = array('x'=>$p_x,'y'=>$p_y);
                $p_x +=$number_wide;
            }
            $p_y +=$number_height;
        }

        if( empty($data_source) ) {
            return false;
        }
        shuffle($data_source);

        $target_imgA = imagecreatetruecolor($thumbnailWide, $thumbnailHeight);
        $dstImBg = @imagecolorallocate($target_imgA,255,255,255);
        imagefill($target_imgA,0,0,$dstImBg); //创建背景为白色的图片
        $srcIm   = @imagecreatefrompng($srcFile); //截取指定区域

        $p_x =0;
        $p_y =0;
        $dataV = array();
        foreach($data_source as $key=>$val)  {
            imagecopy($target_imgA,$srcIm,$p_x,$p_y,$val['x'],$val['y'],$number_wide,$number_height);
             $dataV[$val['x'].'_'.$val['y']] = array('X'=>$p_x.'_'.$p_y , 'Y'=>$val['x'].'_'.$val['y']);
            $p_x +=$number_wide;
            if( $p_x >= $thumbnailWide ) {
                $p_x = 0;
                $p_y = $number_height;
            }
        }
        imagepng($target_imgA,$bigPicName);
        imagedestroy($target_imgA);
        imagedestroy($srcIm);
        $_temp_xy_data = array();

        foreach( $dataV as $key=>$val) {
            if( $val['X'] != $val['Y'] ) {
                $vv=explode('_', $dataV[$val['X']]['X'] );
                $_temp_xy_data[] = $vv;
            } else {
                $vv=explode('_',  $val['X'] );
                $_temp_xy_data[] = $vv;
            }
        }
        $_SESSION[self::$session_name]=$this->location_x;
        $this->get_png_info = array(
            'data'    => $_temp_xy_data,
            'bg_pic'  => ltrim($bigPicName,'.'),
            'ico_pic' => array('url'=> ltrim($this->smallPicName,'.'),'w'=> $this->tailoring_w ,'h'=>$this->tailoring_h),
            'x_point' => $this->location_x,
            'y_point' => $this->location_y
        );
    }

    /**
    * 递归删除目录
    * @return Void
    */
    protected function rec_del_dir($dir){
        $count  = $this->del_count ;
        $expire = self::$timer;
        // 目录句柄
        $handler= dir($dir);
        // 执行
        for( $i = 0 ;  $i < $count + 2  ; $i++ ){
            // 指针方式，该目录下所有的名称
            $name = $handler->read();
            $path = $handler->path.'/'.$name; //当前目录真实路径
            switch ($name) {
                // 结束当前层，递归
                case false:
                    return false; 
                // 退出当前层，循环
                case '.'  :
                case '..' :
                    break ;
                default   :
                    if (is_dir($path) && @rmdir($path) == false ) {
                        if( !is_numeric($name) ){
                            $this->rec_del_dir($path);
                        }// 计算时差，并查看该目录，是否过期
                        else if( time() - $name > $expire ){
                            $this->rec_del_dir($path);
                        }
                    }
                    else {
                        @unlink($path);
                    }
            }
        }
    }

    /**
    * 校验数据合法性
    * @param  Int    : val
    * @param  Int    : deviation 验证码,允许误差值
    * @return Array
    */
    private static function check($val, $deviation){
        if( !is_numeric($val) || !is_numeric($deviation) ) {
            $msg['Err'] = 2002;
            $msg['out'] = self::get_lan_pack()['code']['c_'.$msg['Err']] ;
        }else{
            $max = $_SESSION[self::$session_name] + $deviation;
            $min = $_SESSION[self::$session_name] - $deviation;
            // 是否在误差允许范围内
            if( $val <= $max &&  $val >= $min  ) {
                $msg['Err'] = 2000;
                $msg['out'] = self::get_lan_pack()['code']['c_'.$msg['Err']];
            } else {
                $msg['Err'] = 2001;
                $msg['out'] = self::get_lan_pack()['code']['c_'.$msg['Err']];
            }
        }
        return $msg;
    }

    /**
    * 验证，验证码
    * @param  Array : session session的配置
    * @param  Int   : x_value 横坐标的值
    * @param  Int   : fault_tolerant 容错值
    * @return Array : 结果集
    */
    private static function validate($session, $x_value, $fault_tolerant){
        // 计数器
        $session['count'] ++ ;
        // 是否在有效期内
        $dis_time = time() - $session['timer'] ;
        if( $dis_time  >  self::$timer  ){
            $msg['status']= true;
            $msg['Err']   = 2003;
            $msg['out']   = self::get_lan_pack()['code']['c_'.$msg['Err']];
            return $msg;
        }
        // 是否已需重新验证
        if( true == $session['need_new'] ){
            $msg['status']= true;
            $msg['Err']   = 2004;
            $msg['out']   = self::get_lan_pack()['code']['c_'.$msg['Err']];
            return $msg;
        }
        // 数值正确性，验证
        $result = self::check( $x_value, $fault_tolerant );
        if( $result['Err'] == 2000 ){
            // 校验成功标志
            $session['success'] = true;
            // 还原配置
            $session['need_new'] = false;
            $session['count']    = 0;
            $_SESSION[self::$session_data] = json_encode($session);
            $msg['status']= false;
            $msg['Err']   = 2000;
            $msg['out']   = self::get_lan_pack()['code']['c_'.$msg['Err']];
            return $msg;
        }else{
            $msg['status']= false;
            $msg['Err']   = 2001;
            // 如果以达到最大次数
            if( $session['count'] >= self::$times_max ){
                $session['need_new'] = true;
                $msg['status']= true;
                $msg['Err']   = 2004;
            }
            $msg['out']   = self::get_lan_pack()['code']['c_'.$msg['Err']];
            $_SESSION[self::$session_data] = json_encode($session);
            return $msg;
        }
    }

    /**
    * 判断是否验证成功
    * @param  Array : session 当前session的信息
    * @return Array : 结果集
    */
    private static function if_success($session){
        // 超时？
        if( time() - $session['timer'] > self::$timer ){
            $session['success'] = false;
            $_SESSION[self::$session_data] = json_encode($session);
        }
        // 已通过？
        if( $session['success'] ){
            // 重置验证码
            $session['success'] = false; // 置为之前的状态
            $_SESSION[self::$session_name]= mt_rand(1,100); // 设置临时验证码，防止验证码重复提交
            $_SESSION[self::$session_data] = json_encode($session);
            return [
                "status"=> false,// 是否需要换验证图片
                "Err"   => 2000, // 编码值，值为2000表示验证成功
                "out"   => self::get_lan_pack()['code']['c_2000'] // 输出错误信息
            ];
        }else{
            // 获取信息的资源
            return [
                "status"=> true,// 是否需要换验证图片
                "Err"   => 2003, 
                "out"   => self::get_lan_pack()['code']['c_2003'] // 输出错误信息
            ];
        }
    }

    /**
    * 用户语言判断
    * @param  Array : session session的配置
    * @param  Int   : x_value 横坐标的值
    * @return String 中文或者英文包
    */
    private static function get_lan_pack(){
        $language = getallheaders()['Accept-Language'];
        if( preg_match('/zh/i', $language) ){
            return self::$inner_info['zh'];
        }else{
            return self::$inner_info['en'];
        }
    }

    /**
    * 语言包，中、英
    */
    private static $inner_info = [
        'zh' => [
            'slide_info' => '>>请拖动滑块完成验证>>',
            'options'    => '滑动验证码： &nbsp; 请确认你的选择，属于下方列表 <br /><ul><li>选项 1 :<br /> 返回一个已渲染好的页面</li><li>选项 2 :<br /> 返回Json格式的字符串 , 示例： <br /> {"status"=> false,"Err"   => 2007, "out"   => "这里会输出编码对应信息!"}</li><li>选项 3 ,（整型）容差值【默认 8】: <br /> 返回Json格式的字符串 , 示例同上 </li></ul>',
            'code'       => [
                "c_2000" => "验证码通过",
                "c_2001" => "验证码错误",
                "c_2002" => "验证参数 或者 容差值 不为整数",
                "c_2003" => "验证码超时了，请重新验证",
                "c_2004" => "您操作失误太多次了，请重新验证",
                "c_2005" => "验证码生成失败，服务器未安装GD库",
                "c_2006" => "没有找到生成验证码的初始图片",
                "c_2007" => "传入的参数有误"
            ]
        ],
        'en' => [
            'slide_info' => '>>Drag the slider,please>>',
            'options'  => 'Slide Captchar: &nbsp; Confirm your option is of the following list ,please !<br /><ul><li>Option 1 :<br /> Return rendered html</li><li>Option 2 :<br /> Return Json String , e.g.: <br /> {"status"=> false,"Err"   => 2007, "out"   => "Here is some info about code meaning to output!"}</li><li>Option 3,(Int)fault-tolerant [default 8] :<br /> Return Json String , the instance like the pre one</li></ul>',
            'code'     => [
                "c_2000" => "Verify code is right",
                "c_2001" => "Verify code is error!",
                "c_2002" => "Post data or fault-tolerant is not an int value",
                "c_2003" => "Verify timeout",
                "c_2004" => "You have made too many mistakes, please verify again",
                "c_2005" => "Cannot Initialize new GD image stream",
                "c_2006" => "Failed to find the initial image of the generated verification code ",
                "c_2007" => "Incoming parameters are incorrect "
            ]
        ],
    ];

}
