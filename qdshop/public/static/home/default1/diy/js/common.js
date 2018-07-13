/**
 * 公共js库
 */

//添加商品到购物车
function addToCart(goods_id, quick, event){
    var index = layer.load(0, {
        shade: [0.1,'#fff']
    });
    
    var quick = (typeof (quick) == "undefined") ? 0 : parseInt(quick);
    var is_design = 1;  //diy设计商品

    var rec_id = new Array();

    // 一件定制
    if ($('.box1').hasClass('on')) {
        var number = 1;//购买数量

        //获取属性值
        var chk_value = new Array();
        $('.box1').find('.r_box input:checked').each(function(){
            chk_value.push($(this).val());
        });
        $.ajax({
            type: "post",
            url: "/index.php/cart/addToCart.html",
            dataType: 'json',
            data:{goods_id:goods_id,quick:quick,attr_id:chk_value,number:number,is_design:is_design},
            success: function(result){
                layer.close(index);
                if(result.code == 401){
                    layer.open({
                        content: result.message,
                        btn: ['确定', '取消'],
                        shadeClose: false,
                        yes: function(index){
                            window.location.href = '/index.php/user/login.html';
                        }
                    });
                }else{
                    if (result.code == 200) {
                        /*var cart_is = true;
                        if(cart_is == false){ return false; }
                        var scrollTop = $(window).scrollTop();
                        cart_is = false;
                        $('.cart_abtn i').stop(true,true).css({
                            "left": event.pageX,
                            "top": event.pageY - scrollTop,
                            "opacity": "1",
                            "z-index": "9999"
                        }).animate({
                            "left": $('.cart').offset().left,
                            "top": $('.cart').offset().top - scrollTop,
                            "margin": "0",
                            "width": "50px",
                            "height": "50px",
                            "opacity": "0",
                            "z-index": "-1"
                        }, 1500, 'easeOutQuint',function(){
                            $('.header .state .cart').text(parseInt($('.header .state .cart').text()) + number);
                            layer.msg(result.message, {
                                time: 4000,
                            },function(){
                                cart_is = true;
                            });
                        });*/
                        location.href = '/goods/checkout';
                    } else {
                        layer.msg(result.message);
                    }
                }

            }
        });
    } else if ($('.box2').hasClass('on')) {//多件定制
        var len = $('.box2 .cart_box').length;
        $('.box2 .cart_box').each(function(i) {
            var number = parseInt($(this).find("input[name='number']").val());//购买数量

            //获取属性值
            var chk_value = new Array();
            $(this).find('.r_box input:checked').each(function(){
                chk_value.push($(this).val());
            });
            $.ajax({
                type: "post",
                url: "/index.php/cart/addToCart.html",
                async: false,
                dataType: 'json',
                data:{goods_id:goods_id,quick:quick,attr_id:chk_value,number:number,is_design:is_design},
                success: function(result){
                    if (result.code == 200) {
                        rec_id.push(result.data.supplier_list[0].goods_list[0].rec_id);
                    }
                    if (i == len - 1) { //最后一个
                        layer.close(index);
                        if(result.code == 401){
                            layer.open({
                                content: result.message,
                                btn: ['确定', '取消'],
                                shadeClose: false,
                                yes: function(index){
                                    window.location.href = '/index.php/user/login.html';
                                }
                            });
                        }else{
                            if (result.code == 200) {
                                /*var cart_is = true;
                                if(cart_is == false){ return false; }
                                var scrollTop = $(window).scrollTop();
                                cart_is = false;
                                $('.cart_abtn i').stop(true,true).css({
                                    "left": event.pageX,
                                    "top": event.pageY - scrollTop,
                                    "opacity": "1",
                                    "z-index": "9999"
                                }).animate({
                                    "left": $('.cart').offset().left,
                                    "top": $('.cart').offset().top - scrollTop,
                                    "margin": "0",
                                    "width": "50px",
                                    "height": "50px",
                                    "opacity": "0",
                                    "z-index": "-1"
                                }, 1500, 'easeOutQuint',function(){
                                    $('.header .state .cart').text(parseInt($('.header .state .cart').text()) + number);
                                    layer.msg(result.message, {
                                        time: 4000,
                                    },function(){
                                        cart_is = true;
                                    });
                                });*/
                                // 去重复
                                rec_id = unique(rec_id).join(',');
                                location.href = '/goods/checkout?sel_goods='+rec_id;
                            } else {
                                layer.msg(result.message);
                                return false;
                            }
                        }
                    }
                }
            });
        });
    }

    //禁止页面刷新
    return false;
}

/**
 * 数组元素去重复
 */
function unique(arr) {
    var result = [], hash = {};
    for (var i = 0, elem; (elem = arr[i]) != null; i++) {
        if (!hash[elem]) {
            result.push(elem);
            hash[elem] = true;
        }
    }
    return result;
}