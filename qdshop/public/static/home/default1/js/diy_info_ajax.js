function getDiy(diy,imgs,cat_id){
    //getInfo(diy);
    var imgsrc = "/"+imgs;
    $('.mep_tleft_img').attr('src',imgsrc);
    if($('.lt_bg .mep_tright').find('#diy').length){
        $('.lt_bg .mep_tright').find('#diy').text(diy);
    }else{
        var html ="<div id='diy' style='display: none'>"+diy+"</div>";
        $('.lt_bg .mep_tright').append(html);
    }
/*    $.ajax({
        type: 'GET',
        url: cat_url,
        data: {is_cat_ajax: 1, cat_id:cat_id},
        dataType: 'json',
        success: function (result) {
            //alert(result);
            //console.log(result.length);
            var html='';
            for(var i=0;i<result.length;i++){
                html +="<option value="+result[i]['cat_id']+">"+result[i]['cat_name']+"</option>";
            }
            $('.lt_bg .select').append(html);

        }
    });*/


    return;
}

function getInfo(){
    //alert($('#goods_id').val());return false;
    if($('.upload_box li.uploading').length != 0){
        layer.msg("还有图片在上传中，请稍等！")
        return false;
    }

    var goods_id = $('#goods_id').val();

    var diy_id =$('#diy').text();
    //alert(diy_id);
    var title = $('#title').val();
    var type  = $('#type').find("option:selected").val();
    var describe  = $('#describe').val();


    var img_txu = $('.mep_tleft .mep_tleft_img').attr('src');//带T恤合成图
    //alert(img_txu);return false;

    var img_tu = $('.mep_tleft .diy_design_img').attr('src');//设计图

    var file_id = $('.imgBox').find("li").attr('data-name');//图案id
    //alert(file_id);return false;

    var tags  = $('#tags dd');

    var img = $('.clearfix .uploaded');

    var tagsArray = new Array();
    var imgArray = new Array();

    for(var i = 0;tags.length > i;i++ ){
        tagsArray[i]=tags.eq(i).text();
    }
    //console.log(tagsArray);return false;
    for(var i = 0;img.length > i;i++ ){
        imgArray[i]=img.eq(i).css("background-image").replace('url("','').replace('")');
    }


    /*if(diy_id == ""){
        layer.msg("网络请求失败，请稍后重试!",{time:1500});
        return false;
    }else*/ if(title == ""){
        layer.msg("请填写标题",{time:1500});
        return false;
    }else if(describe == ""){
        layer.msg("请填写作品描述",{time:1500});
        return false;
    }else if(tagsArray == ""){
        layer.msg("请添加标签",{time:1500});
        return false;
    }else if(imgArray == ""){
        layer.msg("请添加产品图片",{time:1500});
        return false;
    }else if(img_txu == ""){
        layer.msg("网络请求失败，请稍后重试!!",{time:1500});
        return false;
    }
    var load = layer.load(0, {
        shade: [0.1, '#fff']
    });
    //alert(imgArray);
    //console.log(imgArray);
    //return false;
    $.ajax({
        url:from_ajax,
        type:"POST",
        dataType: "json",
        data:{diy_id:diy_id,title:title,type:type,describe:describe,tags:tagsArray,imgs:imgArray,img_t:img_txu,design_img:img_tu,file_id:file_id,goods_id:goods_id},
        success: function(e){
            console.log(e);
            if(e){
                layer.close(load);
                layer.msg("提交成功");
                $('.cbg_bg').fadeOut();
                $('.lt_bg').fadeOut();
                $('.uploaded').remove();
                $('#cansaizuopin_form')[0].reset();
                if($('.cbg_cmain .clearfix').length > 0){
                    $('#list_diy_'+ diy_id).remove();
                }
                //$("input[type='checkbox']").attr("checked",false);
            }else{
                layer.close(load);
                layer.msg("网络请求失败，请稍后重试");
            }
        },
        error: function(){
            layer.close(load);
            layer.msg('网络请求失败，请稍后重试');
        }
    })
}
