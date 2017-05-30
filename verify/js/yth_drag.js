//+++++++++++++++++++++++++++++++++++++++++++++++++++++
//++  滑动验证码 Link: http://hlzblog.top/
//+++++++++++++++++++++++++++++++++++++++++++++++++++++
/**
* 拖动图片验证码，中、英
* 传参说明
    "verify_url": false, // 这里设置默认 滑块验证地址
    "source_url": false, // 资源地址
    "form_id": false, // 表单id
    "submit_url": false, // 表单提交地址
    "auto_submit": false, // 是否自动提交表单
    "crypt_func": false, // 是否启用加密函数，一般用RSA，若启用https，请忽略此项
    "post_field":"x_value", // 用于验证的字段名
    "layer_time": 1000 // 错误信息[除验证码] 弹出窗口显示时间，单位 ms
*/
;
(function($) {
    $.fn.yth_drag = function(options) {
        var x, drag = $('#drag'),
            moving = false,
            getnow_xy_img = $("#xy_img"),
            //  外部传参
            initial = $.extend({
                "if_hide": false, // 是否将验证码图片先隐藏，响应事件时才显示[验证成功继续隐藏]
                "verify_url": false, // 这里设置默认 滑块验证地址
                "source_url": false, // 资源地址
                "form_id": false, // 表单id
                "submit_url": false, // 表单提交地址
                "auto_submit": false, // 是否自动提交表单
                "crypt_func": false, // 是否启用加密函数，一般用RSA
                "post_field": "x_value", // 用于验证的字段名
                "layer_time": 1000 // 错误信息[除验证码] 弹出窗口显示时间，单位 ms
            }, options);
        //添加背景，文字，滑块
        if (initial.if_hide) {}
        //放验证码的容器
        var now_container = drag.parent().eq(0).parent().eq(0),
            handler = drag.find('.handler'),
            drag_bg = drag.find('.drag_bg'),
            text = drag.find('.drag_text'),
            maxWidth = drag.width() - handler.width(),
            //touch事件判断
            hasTouch = 'ontouchstart' in window,
            startEvent = hasTouch ? 'touchstart' : 'mousedown',
            moveEvent = hasTouch ? 'touchmove' : 'mousemove',
            endEvent = hasTouch ? 'touchend' : 'mouseup',
            cancelEvent = hasTouch ? 'touchcancel' : 'mouseup';
        //滑动事件监听
        handler.bind(startEvent, function(event) { // 开始
            mouse_start(event);
        });
        $("#drag").bind(moveEvent, function(event) { // 进行中
            mouse_move(event);
        }).bind(endEvent, function(event) { //结束
            mouse_end(event);
        });

        function mouse_start(e) {
            getnow_xy_img.show();
            moving = true;
            if (hasTouch) {
                x = e.originalEvent.touches[0].pageX - parseInt(handler.css('left'), 10)
            } else {
                x = e.pageX - parseInt(handler.css('left'), 10)
            }
        }

        function mouse_move(e) {
            var now_x = e.pageX - x;
            if (hasTouch) {
                e.preventDefault();
                now_x = e.originalEvent.changedTouches[0].pageX - x
            } else {
                now_x = e.pageX - x
            } if (moving) {
                if (now_x > 218) {
                    now_x = 218
                } else if (now_x < 0) {
                    now_x = 0
                }
                getnow_xy_img.css({
                    'left': now_x
                });
                if (now_x > 0 && now_x <= maxWidth) {
                    handler.css({
                        'left': now_x
                    });
                    drag_bg.css({
                        'width': now_x
                    })
                } else if (now_x > maxWidth) {}
            }
        }

        function mouse_end(e) {
            moving = false;
            var now_x;
            if (hasTouch) {
                now_x = e.originalEvent.changedTouches[0].pageX - x
            } else {
                now_x = e.pageX - x
            }
            submit_verify(now_x)
        }
        // 验证码校验

        function submit_verify(now_x) {
            var post_data = initial.post_field + "=" + now_x;
            $.ajax({
                type: "post",
                url: initial.verify_url,
                dataType: "json",
                async: false,
                data: post_data,
                success: function(result) {
                    if (2000 === result.Err) {
                        for (var i = 1; 4 >= i; i++) {
                            getnow_xy_img.animate({
                                left: now_x - (30 - 10 * i)
                            }, 50);
                            getnow_xy_img.animate({
                                left: now_x + 2 * (30 - 10 * i)
                            }, 50, function() {
                                getnow_xy_img.css({
                                    'left': now_x
                                })
                            })
                        }
                        handler.css({
                            'left': maxWidth
                        });
                        drag_bg.css({
                            'width': maxWidth
                        });
                        getnow_xy_img.removeClass('xy_img_bord');
                        remove_listener();
                        if (initial.auto_submit && initial.submit_url && initial.form_id) {
                            sub_form()
                        }
                    } else {
                        layer.msg(result.out, {
                            icon: 5,
                            time: 1000
                        });
                        if (result.status) {
                            get_src()
                        } else {
                            getnow_xy_img.css({
                                'left': 0
                            });
                            handler.css({
                                'left': 0
                            });
                            drag_bg.css({
                                'width': 0
                            })
                        }
                    }
                }
            })
        }
        // 自动提交表单

        function sub_form() {
            var sub_form_data = $("#" + initial.form_id).serialize();
            if (false != initial.crypt_func) {
                sub_form_data = crypt_data(sub_form_data)
            }
            var page_load_index = layer.load(0, {shade: false}); // 加载层 开启
            $.ajax({
                type: "post",
                url: initial.submit_url,
                data: sub_form_data,
                dataType: 'json',
                success: function(msg) {
                    layer.close(page_load_index);// 加载层 关闭
                    if (!msg.status) {
                        layer.msg(msg.out, {
                            time: initial.layer_time
                        });
                        get_src();
                    }else {
                        layer.msg('正在跳转...', {
                            icon: 6,
                            time: 1000
                        }, function() {
                            window.location.href = msg.url;
                       })
                    }
                }
            })
        }
        // 加密数据，返回json数据

        function crypt_data() {
            var params = $("#" + initial.form_id).serialize(),
                arr = params.split("&"),    // 分割为单个数据
                bulid_data = {},    // 待组装的数据
                temp,
                temp_encode_data;
            for (var i = 0; i < arr.length; i++) {
                temp = arr[i].split('=');
                // 获取加密数据
                temp_encode_data = eval( initial.crypt_func + "('" + temp[1]  + "');" );
                // 组装为json，【利用jquery中json解析机制，防止传送过程中，个别字符因直接序列化发送导致的丢失】
                eval( "bulid_data." + temp[0] + "='" + temp_encode_data+"';"); 
            }
            return bulid_data;
        }
        // GET 新资源

        function get_src() {
            $.ajax({
                type: "get",
                url: initial.source_url,
                dataType: 'html',
                async: false,
                success: function(get_html) {
                    remove_listener();
                    now_container.eq(0).html(get_html);
                    $(this).yth_drag(initial)
                }
            })
        }
        // 清空监听事件

        function remove_listener() {
            handler.removeClass('handler_bg').addClass('handler_ok_bg');
            if (client_lang()) {
                text.text("验证码正确!")
            } else {
                text.text("Verification code correct!")
            }
            drag.css({
                'color': '#EE82EE'
            });
            handler.unbind(startEvent);
            $("#drag").unbind(moveEvent);
            $("#drag").unbind(endEvent)
        }
        // 浏览器语言判断，中英

        function client_lang() {
            var type = navigator.appName;
            if (type == "Netscape") {
                var lang = navigator.language
            } else {
                var lang = navigator.userLanguage
            }
            var lang = lang.substr(0, 2);
            if (lang == "zh") {
                return true
            } else {
                return false
            }
        }
    }
})(jQuery);