/**
 * 公共js库
 */

//添加商品到购物车
/*
 *chk_value     砍价传属性用
 *group_log_id  拼团传拼单记录ID用
*/
function addToCart(goods_id,quick,parent,flow_type,chk_value,group_log_id){
    var index = layer.open({type: 2,content: '加载中'});
    var number = $('#number').val();
    var quick = (typeof (quick) == "undefined") ? 0 : parseInt(quick);
    var parent = (typeof (parent) == "undefined") ? 0 : parseInt(parent);//大于0作为配件
    var flow_type = (typeof (flow_type) == "undefined") ? 0 :  parseInt(flow_type);//扩展属性，如：0（普通商品）、1（团购商品）、6（预售商品）、7（虚拟团购）、101（砍价）、102（拼团）
    var group_log_id = (typeof (group_log_id) == "undefined") ? 0 :  parseInt(group_log_id);//拼团活动，去拼单的group_lod表的ID

    if(typeof (chk_value) == "undefined" || chk_value == 0){
        //获取属性值
        var chk_value = new Array();
        $('.arrt_box input:checked').each(function(){
            chk_value.push($(this).val());
        });
    }

    //alert(chk_value);return false;//获取的值
    $.ajax({
        type: "post",
        url: "/mobile.php/cart/addToCart.html",
        dataType: 'json',
        data:{goods_id:goods_id,quick:quick,attr_id:chk_value,number:number,parent:parent,flow_type:flow_type,group_log_id:group_log_id},
        success: function(result){//console.log(result);
            layer.close(index);
            if(result.code == 401){
                layer.open({
                    content: result.message
                    ,btn: ['确定', '取消']
                    ,yes: function(index){
                        window.location.href = '/mobile.php/user/login.html';
                    }
                  });
            }else{
                if(result.code == 200){
                    if(quick){
                        //一步购物
                        window.location.href = '/mobile.php/goods/checkout.html';
                        return false;
                    }
                }
                layer.open({
                    content: result.message
                    ,skin: 'msg'
                    ,time: 2 //2秒后自动关闭
                });

            }

        }
    });
    //禁止页面刷新
    return false;
}

//添加商品到收藏
function collectGoods(goods_id){
    var index = layer.open({type: 2,content: '加载中'});
    $.ajax({
        type: "get",
        url: "/mobile.php/Goods/collectGoods.html",
        dataType: 'json',
        data:{goods_id:goods_id},
        success: function(result){
            layer.open({
                content: result.message
                ,skin: 'msg'
                ,time: 2 //2秒后自动关闭
            });
            layer.close(index);
        }
    });
    //禁止页面刷新
    return false;
}

//添加商品到收藏
function guanzhu(supplier_id){
    var index = layer.open({type: 2,content: '加载中'});
    $.ajax({
        type: "get",
        url: "/mobile.php/Supplier/guanzhu.html",
        dataType: 'json',
        data:{supplier_id:supplier_id},
        success: function(result){
            layer.open({
                content: result.message
                ,skin: 'msg'
                ,time: 2 //2秒后自动关闭
            });
            layer.close(index);
        }
    });
    //禁止页面刷新
    return false;
}

//验证手机格式
function checkMobile(mobile){
    if(mobile.length==0){
        layer.open({
            content: '请输入手机号码！'
            ,skin: 'msg'
            ,time: 2 //2秒后自动关闭
        });
        return false;
    }
    if(mobile.length!=11){
        layer.open({
            content: '请输入有效的手机号码！'
            ,skin: 'msg'
            ,time: 2 //2秒后自动关闭
        });
        return false;
    }
    var cMobile=/(^(13[0-9]|14[57]|15[012356789]|17[03678]|18[0-9])\d{8}$)|(^170[059]\d{7}$)/;
    if(!cMobile.test(mobile)){
        layer.open({
            content: '请输入有效的手机号码！'
            ,skin: 'msg'
            ,time: 2 //2秒后自动关闭
        });
        return false;
    }
    return true;
}

/**
 * [getCode 获取手机验证码]
 * @param  {[int]} send_type  [发送类型 1为注册 2为找回密码]
 * @param  {[string]} sendButton [点击发送短信证码的按钮对象，用于显示倒计时信息]
 */
function getCode(send_type,sendButton){
    var mobile = $("#mobile_phone").val();
    if(!checkMobile(mobile)){
        return false;
    }

    var index = layer.open({type: 2,content: '加载中'});
    $.ajax({
        type: "get",
        url: "/mobile.php/User/getCode.html",
        dataType: 'json',
        data:{send_type:send_type,mobile:mobile},
        success: function(result){
            if(result.code == 200){
                countdown(sendButton);
            }
            layer.open({
                content: result.message
                ,skin: 'msg'
                ,time: 2 //2秒后自动关闭
            });
            layer.close(index);
        }
    });
    //禁止页面刷新
    return false;
}

//发短信倒计时
var wait = 60;
function countdown(obj, msg) {
    obj = $(obj);

    if (wait == 0) {
        obj.removeAttr("disabled");
        obj.val(msg);
        wait = 60;
    } else {
        if (msg == undefined || msg == null) {
            msg = obj.val();
        }
        obj.attr("disabled", "disabled");
        obj.val(wait + "秒后重新获取");
        wait--;
        setTimeout(function() {
            countdown(obj, msg)
        }, 1000)
    }
}