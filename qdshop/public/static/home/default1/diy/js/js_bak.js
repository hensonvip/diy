/*var canvasDraw=[
    {
        name:'s1',
        cs:{
            width:100,
            height:100,
            radius:50
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.moveTo(params.x,params.y + params.radius);
            ctx.quadraticCurveTo(
                params.x - (params.radius * 2),
                params.y - (params.radius * 2),
                params.x,
                params.y - (params.radius / 1.5)
            );
            ctx.quadraticCurveTo(
                params.x + (params.radius * 2),
                params.y - (params.radius * 2),
                params.x,
                params.y + params.radius
            );
            ctx.closePath();
        }
    },
    {
        name:'s2',
        cs:{
            width:100,
            height:100
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.moveTo(params.x-params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y + params.height/2);
            ctx.lineTo(params.x-params.width/2,params.y +params.height/2);
            ctx.lineTo(params.x-params.width/2,params.y - params.height/2);
            ctx.closePath();
        }
    },
    {
        name:'s3',
        cs:{
            width:50,
            height:50,
            radius:50
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.arc(params.x,params.y,params.radius,0,Math.PI*2,true);
            ctx.closePath();
        }
    },
    {
        name:'s4',
        cs:{
            width:100,
            height:100,
            spacing:5
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.moveTo(params.x-params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x-params.spacing/2,params.y - params.height/2);
            ctx.lineTo(params.x-params.spacing/2,params.y - params.spacing/2);
            ctx.lineTo(params.x-params.width/2,params.y - params.spacing/2);
            ctx.moveTo(params.x+params.spacing/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y - params.spacing/2);
            ctx.lineTo(params.x+params.spacing/2,params.y - params.spacing/2);
            ctx.moveTo(params.x-params.width/2,params.y + params.spacing/2);
            ctx.lineTo(params.x-params.spacing/2,params.y +params.spacing/2);
            ctx.lineTo(params.x-params.spacing/2,params.y + params.height/2);
            ctx.lineTo(params.x-params.width/2,params.y + params.height/2);
            ctx.moveTo(params.x+params.spacing/2,params.y + params.spacing/2);
            ctx.lineTo(params.x+params.width/2,params.y +params.spacing/2);
            ctx.lineTo(params.x+params.width/2,params.y + params.height/2);
            ctx.lineTo(params.x+params.spacing/2,params.y + params.height/2);
        }
    }
];*/

for(var fui=0;fui<canvasDraw.length;fui++){
    (function (fui) {
        $.jCanvas.extend({
            name: canvasDraw[fui].name,
            type: canvasDraw[fui].name,
            props: {},
            fn: function(ctx, params) {
                $.jCanvas.transformShape(this, ctx, params);
                canvasDraw[fui].drawC(ctx,params);
                $.jCanvas.detectEvents(this, ctx, params);
                $.jCanvas.closePath(this, ctx, params);
            }
        });
    })(fui)
}


var mD={
        moveb:$('.moveC'),
        key:{up:false,down:false,left:false,right:false},
        cxData:[],
        fzData:[],
        xgsetN:'none',
        clickLj:false,
        cbox:$('.cbox'),
        slP:$('.slactBox'),
        slA:$('.slact'),
        mouseOnT:false,
        strokeWidthTextMax:10,
        letterMin:-1,
        letterMax:3,
        shadowBlurMax:100,
        lhMax:4,
        lhMin:0,
        fontSmin:12,
        fontSmax:300,
        pyX:100,
        zoom:$('#zoom'),
        initH:720,
        zoomNum:1,
        body:$('body'),
        doc:$(document),
        canvas:$('#canvas_1'),
        tabs:$('#tabs'),
        menu:$('.menu'),
        isFirefox:navigator.userAgent.indexOf('Firefox')>=0?true:false,
        svgAct:$('#svgActBox'),
        svgitem:$('.svgtiem'),
        svgitemImgData:[],
        allData:[],
        actName:'none',
        num:0,
        bs:20,
        actBox:$('#imgAct'),
        actBox_x:$('#positionMsg b'),
        actBox_y:$('#positionMsg em'),
        actBox_r:$('#rotateMsg em'),
        actBox_w:$('#aspectMsg b'),
        actBox_h:$('#aspectMsg em'),
        rotateAct:$('#rotateAct'),
        rotatadown:false,
        aspectRatio:0,
        timeOutFun:null,
        timeout:300,
        timeout2:navigator.userAgent.indexOf('Firefox')>=0?300:10,
        fontsrc:!!window.ActiveXObject || "ActiveXObject" in window?'/qdshop/public/static/home/default1/diy/fontstyle/font_ie.css':'/qdshop/public/static/home/default1/diy/fontstyle/font.css',
        opacitySlide:$('#opacitySlide,#opacityTextSlide,#opacityImgSlide'),
        opacityTextSlide:$('#opacityTextSlide'),
        opacitySlideVal_svg:$('#opacitySlideVal'),
        opacitySlideVal_text:$('#opacityTextSlideVal'),
        opacitySlideVal_img:$('#opacityImgSlideVal'),
        opacityImgSlide:$('#opacityImgSlide'),
        opacitySvgSlide:$('#opacitySlide'),

        showOpenShadow:$('#showOpenSvgShadow,#showOpenTextShadow,#showOpenImgShadow'),
        showOpenImgShadow:$('.actbox-po.po13'),
        showOpenSvgShadow:$('.actbox-po.po3'),
        showOpenTextShadow:$('.actbox-po.po8'),


        valueColorSh_svg:$('#valueInput2'),
        svgColorShBtn:$('#valueInput2_btn'),
        valueColorSh_text:$('#valueInput4'),
        textColorShBtn:$('#valueInput4_btn'),
        valueColorSh_img:$('#valueInput7'),
        imgColorShBtn:$('#valueInput7_btn'),

        shadowBlur:$('#shadowSvgBlur,#shadowImgBlur,#shadowTextBlur'),

        shadowSvgBlur:$('#shadowSvgBlur'),
        shadowImgBlur:$('#shadowImgBlur'),
        shadowTextBlur:$('#shadowTextBlur'),
        valueBlurSh_svg:$('#shadowSvgBlurVal'),
        valueBlurSh_text:$('#shadowTextBlurVal'),
        valueBlurSh_img:$('#shadowImgBlurVal'),

        shadowX:$('#shadowSvgX,#shadowImgX,#shadowTextX'),
        shadowImgX:$('#shadowImgX'),
        shadowSvgX:$('#shadowSvgX'),
        shadowTextX:$('#shadowTextX'),
        valueShadowX_svg:$('#shadowSvgXVal'),
        valueShadowX_img:$('#shadowImgXVal'),
        valueShadowX_text:$('#shadowTextXVal'),

        shadowY:$('#shadowSvgY,#shadowImgY,#shadowTextY'),
         shadowImgY:$('#shadowImgY'),
         shadowSvgY:$('#shadowSvgY'),
         shadowTextY:$('#shadowTextY'),
        valueShadowY_svg:$('#shadowSvgYVal'),
        valueShadowY_img:$('#shadowImgYVal'),
        valueShadowY_text:$('#shadowTextYVal'),

        showOpenStrok:$('#showOpenStroke'),
        svgBorderIcon:$('#svgBorderIcon'),
        strokeWidthSlide_svg:$('#strokeWidthSlide'),
        strokeWidthSlideVal_svg:$('#strokeWidthSlideVal'),
        valueColorStroke_svg:$('#valueInput'),
        svgStrokeBtn:$('#valueInput_btn'),

        showOpenSvgStroke:$('.actbox-po.po2'),
        showOpenTextStrokebox:$('.actbox-po.po11'),


        strokeOpacitySlide_svg:$('#strokeOpacitySlide'),
        strokeOpacitySlideVal_svg:$('#strokeOpacitySlideVal'),
        svgColorIcon:$('#svgColorIcon'),
        svgColorBtn:$('#valueInput3_btn'),
        valueColor_svg:$('#valueInput3'),

        textColorIcon:$('#textColorIcon'),
        textColorBtn:$('#valueInput5_btn'),
        valueColor_text:$('#valueInput5'),

        valueColorStroke_text:$('#valueInput6'),
        textStrokeWidthBtn:$('#valueInput6_btn'),

        // strokeWidthTextSlide:$('#strokeWidthTextSlide'),
        // strokeWidthTextSlideVal:$('#strokeWidthTextSlideVal'),
        textBorderIcon:$('#textBorderIcon'),
        strokeOpacityTextSlide:$('#strokeOpacityTextSlide'),
        strokeOpacityTextSlideVal:$('#strokeOpacityTextSlideVal'),
        showOpenTextStroke:$('#showOpenTextStroke'),

        textInput:$('#textInput'),
        alCho:$('.alCho'),
        fontSizeSlide:$('#fontSizeSlide'),
        fontSizeVal:$('#fontSizeVal'),
        lhSlide:$('#lhSlide'),
        lhSlideVal:$('#lhSlideVal'),
        letterSlideVal:$('#letterSlideVal'),
        letterSlide:$('#letterSlide'),
        fontB:$('.actbox_B .ic'),
        fontI:$('.actbox_I .ic'),
        fontU:$('.actbox_U .ic'),

        chFont:$('#chFont'),
        enFont:$('#enFont'),
        cutImg:$('.container > img'),
        imgBox:$('.imgBox'),
        imgData:[],
        cutImgBox:$('.cutImgBox,.fixbg'),
        addImgNum:0,
        imgIntW:200,
        imgShowTypeItem:$('.imgShowTypeItem'),
        mbitem:$('.mb'),
        mouseOn:'none',
        choT:$('.tKs'),
        choTcolor:$('.tColorBox'),
        shadowTextOpVal:$('#shadowTextOpVal'),
        shadowImgOpVal:$('#shadowImgOpVal'),
        shadowSvgOpVal:$('#shadowSvgOpVal'),
        shadowOp:$('#shadowTextOp,#shadowImgOp,#shadowSvgOp'),
        shadowTextOp:$('#shadowTextOp'),
        shadowImgOp:$('#shadowImgOp'),
        shadowSvgOp:$('#shadowSvgOp')

    }



mD.opacitySlide.slider({
    min: 0,
    max: 100,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var layerName=mD.allData[getNowIndex()].typeName;
        var val=ui.value;
        if(layerName=='text'){
            mD.opacitySlideVal_text.html(val);
        }else if(layerName=='svg'){
            mD.opacitySlideVal_svg.html(val);
        }else if(layerName=='img'){
            mD.opacitySlideVal_img.html(val);
        }
        mD.timeOutFun=setTimeout(function () {
            changeCanv({opacity:val/100});
        },mD.timeout2);
    }
});//图层透明度操作
mD.showOpenShadow.on('click',function () {
    var el=$(this).parents('.actbox-po');
    if(el.hasClass('on')){
        el.removeClass('on');
        changeCanv({shadowColor:'transparent'});
    }else{
        var layerName=mD.allData[getNowIndex()].typeName;
        var val='#';
        if(layerName=='text'){
            val+=mD.valueColorSh_text.val();
        }else if(layerName=='svg'){
            val+=mD.valueColorSh_svg.val();
        }else if(layerName=='img'){
            val+=mD.valueColorSh_img.val();
        }
        el.addClass('on');
        var co=hexToRgba(val,100).rgba;
        changeCanv({shadowColor:co,shadowBlur:1,shadowX:3,shadowY:3,shOp:1});
    }
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
    el.prev('.ic').addClass('on')
});//投影开关操作
mD.shadowOp.slider({
    min: 0,
    max: 100,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var layer=mD.allData[getNowIndex()];
        var val=ui.value;
        if(layer.typeName=='text'){
            mD.shadowTextOpVal.html(val);
        }else if(layer.typeName=='svg'){
            mD.shadowSvgOpVal.html(val);
        }else if(layer.typeName=='img'){
            mD.shadowImgOpVal.html(val);
        }
        mD.timeOutFun=setTimeout(function () {
            var valc=rgbToHex(layer.shadowColor).hex;
            var co=hexToRgba(valc,val).rgba;
            changeCanv({shOp:val/100,shadowColor:co});
        },mD.timeout2);
    }
});//投影透明度操作
function updateShColor(jscolor) {
    clearTimeout(mD.timeOutFun);
    mD.timeOutFun=setTimeout(function () {
        var layer=mD.allData[getNowIndex()];
        var valc='#'+jscolor;
        var co=hexToRgba(valc,layer.shOp*100).rgba;
        changeCanv({shadowColor:co});
    },mD.timeout2)
}//投影颜色改变操作
mD.shadowBlur.slider({
    min: 0,
    max: mD.shadowBlurMax,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        var layerName=mD.allData[getNowIndex()].typeName;
        if(layerName=='text'){
            mD.valueBlurSh_text.html(val);
        }else if(layerName=='svg'){
            mD.valueBlurSh_svg.html(val);
        }
        else if(layerName=='img'){
            mD.valueBlurSh_img.html(val);
        }
        mD.timeOutFun=setTimeout(function () {
            changeCanv({shadowBlur:val});
        },mD.timeout2)
    }
});//投影模糊操作
mD.shadowX.slider({
    min: -mD.pyX,
    max: mD.pyX,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        var layerName=mD.allData[getNowIndex()].typeName;
        if(layerName=='text'){
            mD.valueShadowX_text.html(val);
        }else if(layerName=='svg'){
            mD.valueShadowX_svg.html(val);
        }else if(layerName=='img'){
            mD.valueShadowX_img.html(val);
        }

        mD.timeOutFun=setTimeout(function () {
            changeCanv({shadowX:val});
        },mD.timeout2)
    }
});//投影X偏移操作
mD.shadowY.slider({
    min: -mD.pyX,
    max: mD.pyX,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        var layerName=mD.allData[getNowIndex()].typeName;
        if(layerName=='text'){
            mD.valueShadowY_text.html(val);
        }else if(layerName=='svg'){
            mD.valueShadowY_svg.html(val);
        }else if(layerName=='img'){
            mD.valueShadowY_img.html(val);
        }

        mD.timeOutFun=setTimeout(function () {
            changeCanv({shadowY:val});
        },mD.timeout2)
    }
});//投影X偏移操作
mD.showOpenStrok.on('click',function () {
    var el=$(this).parents('.actbox-po');
    var layer=mD.allData[getNowIndex()];
    var fill=layer.fillColor;
    var w=1;
    var co='';
    if(el.hasClass('on')){
        el.removeClass('on');
        co='none';
        w=0;
        mD.svgBorderIcon.css('border-color','#333');
    }else{
        el.addClass('on');
        var val='#'+mD.valueColorStroke_svg.val();
        co=hexToRgba(val,rgbToHex(layer.stroke).alpha).rgba;
        mD.svgBorderIcon.css('border-color',co);
    }
    var svg=createTimeSvg({'stroke':co,'stroke-width':w,'fill':fill});
    changeCanv({stroke:co,strokeWidth:w,fillColor:fill,source:svgToCanvas(svg)});
    intSvt(svg);
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
    el.prev('.ic').addClass('on')
});//svg描边开关操作
mD.strokeWidthSlide_svg.slider({
    min: 0,
    max: 10,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        mD.strokeWidthSlideVal_svg.html(val);

        mD.timeOutFun=setTimeout(function () {
            var layer=mD.allData[getNowIndex()];
            var fill=layer.fillColor;
            var co=layer.stroke;
            var svg=createTimeSvg({'stroke-width':val,'stroke':co,'fill':fill});
            changeCanv({strokeWidth:val,fillColor:fill,stroke:co,source:svgToCanvas(svg)});
            intSvt(svg);
        },mD.timeout);
    }
});//svg描边粗细操作
function updateSvgStroke(jscolor) {
    clearTimeout(mD.timeOutFun);
    var val='#'+jscolor;
    mD.svgBorderIcon.css('border-color',val);
    mD.timeOutFun=setTimeout(function () {
        var layer=mD.allData[getNowIndex()];
        var fill=layer.fillColor;
        var co=hexToRgba(val,rgbToHex(layer.stroke).alpha).rgba;
        var w=layer.strokeWidth;
        var svg=createTimeSvg({stroke:co,'stroke-width':w,'fill':fill});
        changeCanv({stroke:co,strokeWidth:w,fillColor:fill,source:svgToCanvas(svg)});
        intSvt(svg)
    },mD.timeout);
}//svg描边颜色改变操作
mD.strokeOpacitySlide_svg.slider({
    min: 0,
    max: 100,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var valp=ui.value;
        mD.strokeOpacitySlideVal_svg.html(valp);

        mD.timeOutFun=setTimeout(function () {
            var layer=mD.allData[getNowIndex()];
            var fill=layer.fillColor;
            var valc=rgbToHex(layer.stroke).hex;
            var co=hexToRgba(valc,valp).rgba;
            var w=layer.strokeWidth;
            var svg=createTimeSvg({stroke:co,'stroke-width':w,'fill':fill});
            changeCanv({stroke:co,strokeWidth:w,fillColor:fill,source:svgToCanvas(svg)});
            intSvt(svg);
        },mD.timeout);
    }
});//svg描边透明度操作
function updateSvgColor(jscolor) {
    clearTimeout(mD.timeOutFun);
    var val='#'+jscolor;
    mD.svgColorIcon.css('background',val);
    mD.timeOutFun=setTimeout(function () {
        var layer=mD.allData[getNowIndex()];
        var co=layer.stroke;
        var w=layer.strokeWidth;
        var svg=createTimeSvg({'stroke-width':w,'stroke':co,'fill':val});
        changeCanv({strokeWidth:w,fillColor:val,stroke:co,source:svgToCanvas(svg)});
        intSvt(svg);
    },mD.timeout);
}//svg颜色改变操作
// mD.strokeWidthTextSlide.slider({
//     min: 0,
//     max: mD.strokeWidthTextMax,
//     step: 1,
//     range: "min",
//     value:0,
//     slide: function( event, ui ) {
//         clearTimeout(mD.timeOutFun);
//         var val=ui.value;
//         mD.strokeWidthTextSlideVal.html(val);
//
//         mD.timeOutFun=setTimeout(function () {
//             changeCanv({strokeWidth:val});
//         },mD.timeout2);
//     }
// });//text描边粗细操作
function updateTextColor(jscolor) {
    clearTimeout(mD.timeOutFun);
    var val='#'+jscolor;
    mD.textColorIcon.css('background',val);

    mD.timeOutFun=setTimeout(function () {
        changeCanv({fillStyle:val});
    },mD.timeout2)
}//text颜色改变操作
function updateTextStroke(jscolor) {
    clearTimeout(mD.timeOutFun);
    var layer=mD.allData[getNowIndex()]
    var val='#'+jscolor;
    mD.textBorderIcon.css({'border-color':val,'background':val});
    var co=hexToRgba(val,rgbToHex(layer.strokeStyle).alpha).rgba;
    mD.timeOutFun=setTimeout(function () {
        changeCanv({strokeStyle:co});
    },mD.timeout2);
}//text描边颜色改变操作
mD.strokeOpacityTextSlide.slider({
    min: 0,
    max: 100,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var valp=ui.value;
        mD.strokeOpacityTextSlideVal.html(valp);
        mD.timeOutFun=setTimeout(function () {
            var layer=mD.allData[getNowIndex()]
            var valc=rgbToHex(layer.strokeStyle).hex;
            var co=hexToRgba(valc,valp).rgba;
            changeCanv({strokeStyle:co});
            setTextAct();
        },mD.timeout2);

    }
});//text描边透明度操作
mD.showOpenTextStroke.on('click',function () {
    var layer=mD.allData[getNowIndex()]
    var el=$(this).parents('.actbox-po');
    var co='';
    if(el.hasClass('on')){
        el.removeClass('on');
        co='transparent';
        mD.textBorderIcon.css({'border-color':'#333','background':'#333'});
    }else{
        el.addClass('on');
        var val='#'+mD.valueColorStroke_text.val();
        co=hexToRgba(val,rgbToHex(layer.strokeStyle).alpha).rgba;
        mD.textBorderIcon.css({'border-color':val,'background':val});
    }
    changeCanv({strokeStyle:co});
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
    el.prev('.ic').addClass('on')
});//text描边开关操作
mD.alCho.click(function () {
    if($(this).hasClass('on')){return false;}
    mD.alCho.removeClass('on');
    $(this).addClass('on');
    var al='';
    if($(this).hasClass('l')){al='left'}
    else if($(this).hasClass('r')){al='right'}
    else if($(this).hasClass('c')){al='center'}
    changeCanv({align:al});
});//文本方向操作
mD.fontSizeSlide.slider({
    min: mD.fontSmin,
    max: mD.fontSmax,
    step: 1,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        mD.fontSizeVal.html(val);

        mD.timeOutFun=setTimeout(function () {
            changeCanv({fontSize:val});
            setTextAct()
        },mD.timeout2)

    }
});//文本字体大小操作
mD.lhSlide.slider({
    min: mD.lhMin,
    max: mD.lhMax,
    step: 0.2,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        mD.lhSlideVal.html(val);

        mD.timeOutFun=setTimeout(function () {
            changeCanv({lineHeight:val});
            setTextAct()
        },mD.timeout2)
    }
});//文本行高操作
mD.letterSlide.slider({
    min: mD.letterMin,
    max: mD.letterMax,
    step: 0.05,
    range: "min",
    value:0,
    slide: function( event, ui ) {
        clearTimeout(mD.timeOutFun);
        var val=ui.value;
        mD.letterSlideVal.html(val);

        mD.timeOutFun=setTimeout(function () {
            changeCanv({ls:val});
            setTextAct()
        },mD.timeout2)
    }
});//文本字距操作
mD.fontU.click(function () {
    if($(this).hasClass('on')){
        changeCanv({
            underLine:false
        })
        $(this).removeClass('on');
    }else{
        changeCanv({
            underLine:true
        })
        $(this).addClass('on');
    }
    setTextAct()
});//下划线
mD.fontB.click(function () {
    if($(this).hasClass('on')){
        if(mD.fontI.hasClass('on')){
            changeCanv({
                fontStyle:'italic'
            })
        }else{
            changeCanv({
                fontStyle:'normal'
            })
        }
        $(this).removeClass('on');

    }else{
        if(mD.fontI.hasClass('on')){
            changeCanv({
                fontStyle:'bold italic'
            })
        }else{
            changeCanv({
                fontStyle:'bold'
            })
        }
        $(this).addClass('on');
    }
    setTextAct()
});//文本粗体
mD.fontI.click(function () {
    if($(this).hasClass('on')){
        if(mD.fontB.hasClass('on')){
            changeCanv({
                fontStyle:'bold'
            })
        }else{
            changeCanv({
                fontStyle:'normal'
            })
        }
        $(this).removeClass('on');

    }else{
        if(mD.fontB.hasClass('on')){
            changeCanv({
                fontStyle:'bold italic'
            })
        }else{
            changeCanv({
                fontStyle:'italic'
            })
        }
        $(this).addClass('on');
    }
    setTextAct()
});//文本斜体



mD.tabs.tabs({active:0});//标签菜单调用
mD.menu.accordion({heightStyle: "content",active :false,collapsible :true});//折叠菜单

mD.svgitem.on('click',function () {
    var svg=$(this).find('svg'),
         svgName=$(this).attr('data-name'),
         first=$(this).attr('data-int'),
         obj={};
    if(first=='false'){
        var fill=svg.css('fill'),
            stroke=svg.css('stroke'),
            strokeWidth=parseInt(svg.css('stroke-width'));
            $(this).attr({'data-w':svg.width(),'data-h':svg.height(),'data-fill':fill,'data-stroke':stroke,'data-strokeWidth':strokeWidth});
        obj={
            drawWhat:'img',
            layer:true,
            fromCenter: true,
            draggable:false,
            typeName:'svg',
            svgName:svgName,
            shadowColor:'none',
            shadowBlur:0,
            shadowX:0,
            shadowY:0,
            shOp:1,
            shadowStroke:false,
            fillColor:fill,
            rotate:0,
            stroke:stroke,
            strokeWidth:strokeWidth,
            opacity:1,
            imageSmoothing:true,
            source:svgToCanvas(svg),
            mousedown:canvasSvgClick
        };
        mD.svgitemImgData.push(obj);
        $(this).attr('data-int','true')
    }else{
        obj=getObj(mD.svgitemImgData,'svgName',svgName);
    }
    var o=$.extend({}, obj);
    o.name=svgName+'$'+mD.num;
    o.x=mD.allData[1].x+ svg.width()*mD.zoomNum;
    o.y=mD.allData[1].y+svg.height()*mD.zoomNum;
    o.width=svg.width()*2*mD.zoomNum;
    o.height=svg.height()*2*mD.zoomNum;
    setCanvas(o);
    mD.canvas.triggerLayerEvent(o.name, 'mousedown');
    mD.num++;
});//svg点击事件,获取到最初始的svg图片数据到初始svg对象中,以及创建图层




function getNowIndex() {
    for(var x=0;x<mD.allData.length;x++){
        if(mD.allData[x].name==mD.actName){
            break;
        }
    }
    if(x==mD.allData.length){return false}else{
        return x;
    }
}

function getObj(obj,names,namesval,yes) {
    for(var x=0;x<obj.length;x++){
        if(obj[x][names]==namesval){
            break;
        }
    }
    if(yes){
        return {o:obj[x],x:x}
    }else{
        return obj[x]
    }

}//通过数组中对象某个属性的值获取该数组中的对象

function svgToCanvas (svg){
    var svghtml=svg.parent().html().trim();
    var canvas=document.createElement('canvas');
    canvas.width=svg.parent().attr('data-w')*mD.bs*mD.zoomNum;
    canvas.height=svg.parent().attr('data-h')*mD.bs*mD.zoomNum;
    canvg(canvas,svghtml);
    return canvas;
}//svg转成canvas

function intSvt(svg) {
    var el=svg.parent();
    svg.css({fill:el.attr('data-fill'),stroke:el.attr('data-stroke'),strokeWidth:el.attr('data-strokeWidth')});
    var x=parseFloat(svg.attr('data-x'));
    var y=parseFloat(svg.attr('data-y'));
    var w=parseFloat(svg.attr('data-w'));
    var h=parseFloat(svg.attr('data-h'));
    svg[0].viewBox.baseVal.x=x;
    svg[0].viewBox.baseVal.y=y;
    svg[0].viewBox.baseVal.width=w;
    svg[0].viewBox.baseVal.height=h;
}//让临时状态的svg回初始
function createTimeSvg(obj){
    var svg=null;
    mD.svgitem.each(function () {
        if($(this).attr('data-name')==mD.allData[getNowIndex()].svgName){
            svg=$(this).find('svg');
            return false;
        }
    });
    svg.css(obj);
    var wi=obj['stroke-width']+4;
    var x=parseFloat(svg.attr('data-x'))-wi/2;
    var y=parseFloat(svg.attr('data-y'))-wi/2;
    var w=parseFloat(svg.attr('data-w'))+wi;
    var h=parseFloat(svg.attr('data-h'))+wi;
    svg[0].viewBox.baseVal.x=x;
    svg[0].viewBox.baseVal.y=y;
    svg[0].viewBox.baseVal.width=w;
    svg[0].viewBox.baseVal.height=h;
    return svg;
}//建立临时样式的svg


mD.textInput.on('input',function () {
    var val=$(this).val();
    if(val==''){return false}
    var layer=mD.allData[getNowIndex()];
    if(mD.actName!='none'){
        if(layer.typeName=='text'){
            changeCanv({
                text:val
            });
            setTextAct();
            return false;
        }
    }
    var name='text$'+mD.num;
    setCanvas({
        drawWhat:'text',
        typeName:'text',
        layer: true,
        name:name,
        fromCenter:true,
        draggable:false,
        lineHeight:1.3,
        baseline:'middle',
        respectAlign:false,
        align:'left',
        fillStyle: '#000',
        underLine:false,
        fontC:'none',
        fontE:'none',
        fontSize:25*mD.zoomNum,
        strokeStyle: 'transparent',
        strokeWidth: 2,
        shadowStroke:true,
        shadowX:50,
        shadowY:50,
        shadowBlur:1,
        shOp:1,
        shadowColor:'none',
        opacity:1,
        scaleX:1,
        scaleY:1,
        fontStyle: 'normal',
        x: mD.allData[1].x+150*mD.zoomNum,
        y: mD.allData[1].y+150*mD.zoomNum,
        ls:0,
        text: val,
        mousedown:canvasTextClick
    })
    mD.canvas.triggerLayerEvent(name, 'mousedown');
    mD.num++;
});//文本输入


function setCanvas(o,num,bl) {
    if(o){
        cxD();
        if(num){
            if(bl==1){
                mD.allData.splice(num-1,1,o);
            }else if(bl==2){
                mD.allData.splice(num,1,o);
            }else{
                mD.allData.splice(num,0,o);
            }
        }else{
           mD.allData.push(o);
        }
    }
    console.log(mD.allData)
    mD.canvas.removeLayers();
    for(var x=0;x<mD.allData.length;x++){
        var t=mD.allData[x].drawWhat;
        if(t=='img'){
            mD.canvas.drawImage(mD.allData[x])
        }else if(t=='text'){
            mD.canvas.drawText(mD.allData[x])
        }else if(t=='rect'){
            mD.canvas.drawRect(mD.allData[x])
        }else if(t=='mb'){
            mD.canvas.draw(mD.allData[x])
        }else if(t=='line'){
            mD.canvas.drawLine(mD.allData[x])
        }
        if(x>2&&mD.allData[x-1].drawWhat=='mb'){
            mD.canvas.restoreCanvas({
                layer: true
            });
        }
    }
    mD.canvas.restoreCanvas({
        layer: true
    });
    if(mD.clickLj){
        mD.canvas.drawLayers();
        mD.clickLj=false
    }
}


(function () {
    var len=mD.svgitem.length,num=0;
    mD.body.append('<div id="embBox"></div>');
    mD.svgitem.each(function () {
        var _this=$(this);
        var svg = document.createElement("embed");
        svg.setAttribute("type", 'image/svg+xml');
        svg.onload=function () {
            var svgstr=svg.getSVGDocument().documentElement;
            _this.append(svgstr);
            _this.find('svg').attr({
                'data-x': _this.find('svg')[0].viewBox.baseVal.x,
                'data-y': _this.find('svg')[0].viewBox.baseVal.y,
                'data-w': _this.find('svg')[0].viewBox.baseVal.width,
                'data-h': _this.find('svg')[0].viewBox.baseVal.height,
                preserveAspectRatio:'xMidYMid meet'
            }).css('stroke-width',0);
            num++;
            if(num==len){
                $('#embBox').remove();
            }
        };
        svg.setAttribute("src", _this.attr('data-svg'));
        $('#embBox').append(svg);
    });
    return false;
})();//输出svg到html




function showPoMsg(l,t) {
    mD.actBox_x.html(parseInt(l-mD.allData[1].x));
    mD.actBox_y.html(parseInt(t-mD.allData[1].y));
}//改变操作框位置数据显示信息
function showKgMsg(w,h) {
    mD.actBox_w.html(parseInt(w));
    mD.actBox_h.html(parseInt(h));
}//改变操作框宽高数据显示信息
function showRotateMsg(r) {
    mD.actBox_r.html(r)
}//改变操作框宽高数据显示信息



mD.actBox.on('mouseover','.ui-resizable-sw,.ui-resizable-se,.ui-resizable-ne,.ui-resizable-nw',function(){
    mD.aspectRatio=1

});
mD.actBox.on('mouseover','.ui-resizable-n,.ui-resizable-e,.ui-resizable-s,.ui-resizable-w',function(){
    mD.aspectRatio=0
});
mD.rotateAct.on('mousedown',function () {
    cxD();
    mD.rotateAct.addClass('cur');
    mD.canvas.addClass('cur');
    mD.actBox.addClass('op cur');
    mD.rotatadown=true;
    if(mD.isFirefox){
        canfy=false;
    }
});
mD.doc.on('mouseup',function () {
    if(!mD.rotatadown){
        return;
    }
    mD.rotateAct.removeClass('cur');
    mD.canvas.removeClass('cur');
    mD.actBox.removeClass('op cur');
    mD.rotatadown=false;
    if(mD.isFirefox){
        canfy=true;
        mD.canvas.drawLayers();
    }
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
}).on('mousemove',function (event) {
    if(!mD.rotatadown){
        return;
    }
    var e = event || window.event;
    var mx=e.clientX;
    var my=e.clientY;
    var el=mD.actBox[0].getBoundingClientRect();
    var x=el.left+el.width/2;
    var y=el.top+el.height/2;
    var bc1=0;
    var bc2=0;
    var b=0;
    var c=0;
    if(mx>x&&my<y){
        bc1=y-my;
        bc2=mx-x;
    }
    if(mx>x&&my>y){
        bc1=mx-x;
        bc2=my-y;
        c=90;
    }
    if(mx<x&&my>y){
        bc1=my-y;
        bc2=x-mx;
        c=180;
    }
    if(mx<x&&my<y){
        bc1=x-mx;
        bc2=y-my;
        c=270;
    }
    if(bc1>bc2){
        b=90-Math.atan(bc1/bc2)*180/Math.PI+c
    }else{
        b=Math.atan(bc2/bc1)*180/Math.PI+c
    }
    mD.actBox.css({
        transform:'rotate('+b+'deg)'
    });
    changeCanv({rotate:parseInt(b)},1);
    showRotateMsg(parseInt(b));
});//旋转图层

mD.actBox.draggable({
    containment: "#diybox",
    scroll: false,
    start:function () {
        if(mD.rotatadown){
            return false;
        }
        if(mD.isFirefox){
            canfy=false;
        }
        cxD();
        mD.allData[2].visible=true;
        mD.allData[3].visible=true;
        var oo=[];
        oo[0]=$.extend({}, mD.allData[2]);
        oo[1]=$.extend({}, mD.allData[3]);
        mD.allData.splice(2,2);
        mD.allData.push(oo[0],oo[1]);
        mD.actBox.addClass('op')
    },
    stop:function() {
        if(mD.isFirefox){
            canfy=true;
        }
        mD.allData[mD.allData.length-2].visible=false;
        mD.allData[mD.allData.length-1].visible=false;
        var oo2=[];
        oo2[0]=$.extend({}, mD.allData[mD.allData.length-2]);
        oo2[1]=$.extend({}, mD.allData[mD.allData.length-1]);
        mD.allData.splice(2,0,oo2[0],oo2[1]);
        mD.allData.splice(mD.allData.length-2,2);
        mD.actBox.removeClass('op');
        setCanvas()
    },
    drag: function( event, ui ) {
        var l=ui.position.left;
        var t=ui.position.top;
        dr(l+2,t+2)
    }
});//操作框拖拽事件
mD.actBox.resizable({
    handles: "all",
    containment: "#diybox",
    minHeight:10,
    minWidth: 10,
    start:function(event,ui){
        if(mD.aspectRatio == 1){
            mD.actBox.resizable("option", "aspectRatio", true);
        }else{
            mD.actBox.resizable("option", "aspectRatio", false);
        }
        if(mD.isFirefox){
            canfy=false;
        }
        cxD();
        mD.allData[2].visible=true;
        mD.allData[3].visible=true;
        var oo=[];
        oo[0]=$.extend({}, mD.allData[2]);
        oo[1]=$.extend({}, mD.allData[3]);
        mD.allData.splice(2,2);
        mD.allData.push(oo[0],oo[1]);
        mD.actBox.addClass('op')
    },
    stop:function() {
        if(mD.isFirefox){
            canfy=true;
        }
        mD.allData[mD.allData.length-2].visible=false;
        mD.allData[mD.allData.length-1].visible=false;
        var oo2=[];
        oo2[0]=$.extend({}, mD.allData[mD.allData.length-2]);
        oo2[1]=$.extend({}, mD.allData[mD.allData.length-1]);
        mD.allData.splice(2,0,oo2[0],oo2[1]);
        mD.allData.splice(mD.allData.length-2,2);
        mD.actBox.removeClass('op');
        setCanvas()
    },
    resize: function( event, ui) {
        var l=ui.position.left;
        var t=ui.position.top;
        var w=ui.size.width;
        var h=ui.size.height;
        var lw=l+w/2;
        var lh=t+h/2;
        var act=mD.allData[getNowIndex()];
        if(act.typeName=='mb'){
            var xw=w/act.width;
            var xh=h/act.height;
            changeCanv({x:lw+2,y:lh+2,scaleX:xw,scaleY:xh},1)
        }else if(act.typeName=='text'){
            var lla= mD.canvas.measureText(mD.actName);
            var xw2=w/lla.width;
            var xh2=h/lla.height;
            changeCanv({x:lw+2,y:lh+2,scaleX:xw2,scaleY:xh2},1)
        }else{
            changeCanv({x:lw+2,y:lh+2,width:w,height:h},1)
        }
        showKgMsg(w,h);
        showPoMsg(l,t);
    }
});//操作框拉伸事件


function changeCanv(obj,l) {
    cxD(l);
    $.extend(mD.allData[getNowIndex()],obj);
    setCanvas();
}//改变图层属性重绘画布




mD.tabs.on('click','.hasPo .ic',function () {
    var el=$(this).next('.actbox-po');
    var el2=$('.actbox-po');
    var ic=$('.hasPo .ic');
    if(el.is(':hidden')){
        ic.removeClass('on');
        $(this).addClass('on');
        el2.hide();
        el.stop().slideDown(350);
    }else{
        $(this).removeClass('on');
        el.stop().slideUp(350);
    }
});//操作菜单点击显示隐藏面板





function hexToRgba(hex, al) {
    var hexColor = /^#/.test(hex) ? hex.slice(1) : hex,
        alp = hex === 'transparent' ? 0 : Math.ceil(al),
        r, g, b;
    hexColor = /^[0-9a-f]{3}|[0-9a-f]{6}$/i.test(hexColor) ? hexColor : 'fffff';
    if (hexColor.length === 3) {
        hexColor = hexColor.replace(/(\w)(\w)(\w)/gi, '$1$1$2$2$3$3');
    }
    r = hexColor.slice(0, 2);
    g = hexColor.slice(2, 4);
    b = hexColor.slice(4, 6);
    r = parseInt(r, 16);
    g = parseInt(g, 16);
    b = parseInt(b, 16);
    return {
        hex: '#' + hexColor,
        alpha: alp,
        rgba: 'rgba(' + r + ', ' + g + ', ' + b + ', ' + (alp / 100).toFixed(2) + ')'
    };
}

function rgbToHex(rgb,ll) {
    var rRgba = /rgba?\((\d{1,3}),(\d{1,3}),(\d{1,3})(,([.\d]+))?\)/,
        r, g, b, a,
        rsa = rgb.replace(/\s+/g, "").match(rRgba);
    if (rsa) {
        r = (+rsa[1]).toString(16);
        r = r.length == 1 ? "0" + r : r;
        g = (+rsa[2]).toString(16);
        g = g.length == 1 ? "0" + g : g;
        b = (+rsa[3]).toString(16);
        b = b.length == 1 ? "0" + b : b;
        a = (+(rsa[5] ? rsa[5] : 1)) * 100
        if(ll){
            return {hex:String(r) + g + b, alpha: Math.ceil(a)};
        }else{
            return {hex: "#" + r + g + b, alpha: Math.ceil(a)};
        }
    } else {
        return {hex: rgb, alpha: 100};
    }
}




$('#ysColor,#ysTextColor').on('click','li',function () {
    var jscolor=$(this).attr('data-color');
    var layerName=mD.allData[getNowIndex()].typeName;
    if(layerName=='text'){
        mD.textColorBtn[0].jscolor.fromString(jscolor);
        updateTextColor(jscolor)
    }else if(layerName=='svg'){
        mD.svgColorBtn[0].jscolor.fromString(jscolor);
        updateSvgColor(jscolor);
    }
})
$('#ysBorder,#ysTextBorder').on('click','li',function () {
    var jscolor=$(this).attr('data-color');
    var layerName=mD.allData[getNowIndex()].typeName;
    if(layerName=='text'){
        mD.textStrokeWidthBtn[0].jscolor.fromString(jscolor);
        updateTextStroke(jscolor);
    }else if(layerName=='svg'){
        mD.svgStrokeBtn[0].jscolor.fromString(jscolor);
        updateSvgStroke(jscolor);
    }
})
$('#ysShadow,#ysShadowText,#ysShadowImg').on('click','li',function () {
    var jscolor=$(this).attr('data-color');
    var layerName=mD.allData[getNowIndex()].typeName;
    if(layerName=='text'){
        mD.textColorShBtn[0].jscolor.fromString(jscolor);
    }else if(layerName=='svg'){
        mD.svgColorShBtn[0].jscolor.fromString(jscolor);
    }else if(layerName=='img'){
        mD.imgColorShBtn[0].jscolor.fromString(jscolor);
    }
    updateShColor(jscolor);
});



function  setTextAct() {
    var layer=mD.canvas.measureText(mD.actName);
    var act=mD.allData[getNowIndex()];
    var w=layer.width*act.scaleX;
    var x=layer.x;
    var h=layer.height*act.scaleY;
    var y=layer.y;
    showKgMsg(w,h);
    mD.actBox.css({
        width:w,
        height:h,
        top:y-h/2-2,
        left:x-w/2-2
    });
}



mD.cutImg.cropper({
    rotatable:false,
    touchDragZoom:false,
    built: function () {
        mD.cutImgBox.addClass('on');
    }
});


function getFont(_this) {
    var fontName=_this.attr('data-name');
    var el=_this.parents('.menu').find('.fontCho');
    WebFont.load({
        custom: {
            families: [fontName],
            urls : [mD.fontsrc]  //字体声明处，页面不需要引入该样式
        },
        timeout:30000,
        loading: function() {  //所有字体开始加载
        },
        active: function() {  //所有字体已渲染
        },
        inactive: function() { //字体预加载失败，无效字体或浏览器不支持加载
        },
        fontloading: function(fontFamily, fontDescription) {  //指定字体预加载
        },
        fontactive: function(fontFamily, fontDescription) { //指定字体已渲染
            if(fontFamily==fontName){
                _this.removeClass('loading').addClass('loader');
                if(_this.attr('data-cho')=='true'){
                    changeCanv(setFont(_this));
                    setTextAct();
                    el.removeClass('on');
                    _this.addClass('on');
                }
            }
        },
        fontinactive: function(fontFamily, fontDescription) { //指定字体预加载失败
            if(fontFamily==fontName){
                _this.removeClass('loading');
                alert('字体加载失败，请重新尝试');
            }
        }
    });
}

function setFont(_this) {
    var fontName=_this.attr('data-name');
    var c='';
    var e='';
    var f='';
    if(_this.attr('data-type')=='ch'){
        c=fontName;
        mD.chFont.attr('data-ch',fontName);
        e=  mD.enFont.attr('data-en');
        if(e=='none'){
            f=fontName
        }else{
            f=e+','+fontName
        }
    }
    else if(_this.attr('data-type')=='en'){
        e=fontName;
        mD.enFont.attr('data-en',fontName);
        c= mD.chFont.attr('data-ch');
        if(c=='none'){
            f=fontName
        }else{
            f=fontName+','+c;
        }
    }
    var b={
        fontFamily:f,
        fontE:e,
        fontC:c
    };
    return b;
}

mD.tabs.on('click','.fontCho',function () {
    var _this=$(this);
    if(_this.hasClass('on')){return false;}
    var el=_this.parents('.menu').find('.fontCho');
    el.attr('data-cho','false');
    if(_this.hasClass('loader')){
        changeCanv(setFont(_this));
        setTextAct();
        el.removeClass('on');
        _this.addClass('on');
    }else{
        _this.attr('data-cho','true');
        if(_this.hasClass('loading')){
            return false;
        }
        _this.addClass('loading');
        getFont(_this);
    }

});




$('.cutImgNo').click(function () {
    mD.cutImgBox.removeClass('on');
})

$(".addImgFile").change(function() {
    var _this=$(this)[0];
    var file = this.files[0];
    var reader = new FileReader();
    reader.onload = function() {
        var name='upimg'+mD.addImgNum;
        for(var x=0;x<mD.imgData.length;x++){
            if(mD.imgData[x].data==this.result){
                if($('.imgBoxItem[data-name='+mD.imgData[x].name+']').length==0){
                    mD.imgBox.append('<li data-name="'+mD.imgData[x].name+'" class="imgBoxItem"><i>X</i><em style="background-image: url('+this.result+')"></em></li>')
                }
                return false;
            }
        }
        mD.imgData.push({name:name,data:this.result});
        mD.imgBox.append('<li data-name="'+name+'" class="imgBoxItem"><i>X</i><em style="background-image: url('+this.result+')"></em></li>')
        mD.addImgNum++;
        $.ajax({
            url: upload_file_url,
            type: 'POST',
            dataType: 'json',
            data: {user_id: user_id, file: this.result},
            success: function(ret) {
                if(ret.code == '500'){
                    alert(ret.message);
                }
            },
            fail:function(){

            }
        });
    };
    reader.readAsDataURL(file);
    _this.value = "";
});


mD.imgBox.on('click','li',function (e) {
    var target=$(e.target);
    if(target.closest("i").length != 0){
        // var ob=getObj(mD.imgData,'name',names,true);
        // mD.imgData.splice(ob.x,1);
        $(this).remove();
    }else if(target.closest("em").length != 0){
        var names=$(this).attr('data-name');
        var src=getObj(mD.imgData,'name',names).data;
        var img=new Image();
        img.onload=function () {
            var name='img$'+mD.num;
            var bl=img.height/img.width;
            setCanvas({
                drawWhat:'img',
                typeName:'img',
                imgName:names,
                layer: true,
                name:name,
                fromCenter: true,
                x: mD.allData[1].x+mD.imgIntW*mD.zoomNum,
                y: mD.allData[1].y+mD.imgIntW*bl*mD.zoomNum,
                width:mD.imgIntW*mD.zoomNum,
                height:mD.imgIntW*bl*mD.zoomNum,
                shadowColor:'none',
                shadowBlur:0,
                shadowX:0,
                shadowY:0,
                shOp:1,
                shadowStroke:false,
                draggable:false,
                rotate:0,
                opacity:1,
                imageSmoothing:true,
                source:img.src,
                imgType:'none',
                imgSource:img.src,
                mousedown:canvasImgClick
            })
            mD.canvas.triggerLayerEvent(name, 'mousedown');
            mD.num++
        }
        img.src=src;
    }

    // 删除图片
    $.ajax({
        url: delete_file,
        type: 'POST',
        dataType: 'json',
        data: {file_id: file_id},
        success: function(ret) {
            if(ret.code == '500'){
                alert(ret.message);
            }
        },
        fail:function(){

        }
    });
});




$('.actbox_imgCut .ic').click(function () {
    var layerName=mD.allData[getNowIndex()].imgName;
    mD.cutImg.cropper('replace',getObj(mD.imgData,'name',layerName).data);
});
$('.cutImgYes').click(function () {
    var src=mD.cutImg.cropper('getCroppedCanvas').toDataURL("image/png");
    var img=new Image();
    img.onload=function () {
        var bl=img.height/img.width;
        changeCanv({
            imgSource:img.src,
            width:mD.imgIntW,
            height:mD.imgIntW*bl
        });
        mD.cutImgBox.removeClass('on');
        setMsg(mD.allData[getNowIndex()]);
        setImgType(img.src,mD.allData[getNowIndex()].imgType);
        canCutImg=false;
    };
    img.src=src;
});


function setImgType(imgSource,types) {
    mD.clickLj=true;
    var _this=getTheTypeLi(types);
    var elimg=$('.imgC').find('img')[0];
    elimg.loadOnce(function(){
        var aiObj = $AI(elimg);
        if(types!='none'){
            aiObj.ps(types).replace(elimg);
        }
        changeCanv({
            source: elimg.src,
            imgType:types
        });
        mD.imgShowTypeItem.removeClass('on');
        _this.addClass('on');
    });
    elimg.src=imgSource;
}




mD.imgShowTypeItem.click(function () {
    if(mD.actName=='none'){
        return false
    }
    var _this=$(this);
    if(_this.hasClass('on')){return false;}
    var types=_this.attr('data-type');
    var imgSource=mD.allData[getNowIndex()].imgSource;

    setImgType(imgSource,types);
});

function getTheTypeLi(types) {
    var _this=null;
    mD.imgShowTypeItem.each(function () {
        if($(this).attr('data-type')==types) {
            _this=$(this);
            return false;
        }
    });
    return _this;
}
function getTheMbTypeLi(name) {
    var _this=null;
    mD.mbitem.removeClass('on');
    mD.mbitem.each(function () {
        if($(this).attr('data-name')==name) {
            _this=$(this);
            _this.addClass('on');
            return false;
        }
    });
}





mD.mbitem.click(function () {
    var prev=0;
    var datan=$(this).attr('data-name');
    if(mD.actName=='none'){
        return false;
    }
    if(mD.allData[getNowIndex()-1].typeName=='mb'){
        if(mD.allData[getNowIndex()-1].type==datan){return false;}
        prev=1;
    }
    if(mD.allData[getNowIndex()].typeName=='mb'){
        if(mD.allData[getNowIndex()].type==datan){return false;}
        prev=2;
    }
    var name='mb$'+mD.num;
    var ojb={
        type: datan,
        drawWhat:'mb',
        typeName:'mb',
        fromCenter:true,
        name:name,
        layer: true,
        draggable: false,
        fillStyle: 'rgba(0,0,0,.1)',
        mask:true,
        x: mD.allData[getNowIndex()].x, y:mD.allData[getNowIndex()].y,
        rotate: 0,
        scaleX:mD.zoomNum,
        scaleY:mD.zoomNum,
        mousedown:canvasMbClick
    }
    $.extend(ojb, getObj(canvasDraw,'name',datan).cs);
    setCanvas(ojb,getNowIndex(),prev);
    mD.canvas.triggerLayerEvent(name, 'mousedown');
    mD.num++
});

mD.actBox.on('dblclick',function () {
    var index=getNowIndex()+1;
    if(mD.allData[getNowIndex()].typeName!='mb'){
        index=getNowIndex()-1;
        if(mD.allData[index].typeName!='mb'){
            return false;
        }
    }

    mD.canvas.triggerLayerEvent(mD.allData[index].name, 'mousedown');
})


function canvasMbClick(layer) {
    if(mD.xgsetN=='none'){
        setMsg(layer);
        setMbActInt()
    }

}//文本图层点击事件
function canvasSvgClick(layer){
    if(mD.xgsetN=='none') {
        setMsg(layer);
        setSvgActInt()
    }
}//svg对象点击事件
function canvasTextClick(layer){
    if(mD.xgsetN=='none'){
        setMsg(layer);
        setTextActInt()
    }

}//文本对象点击事件
function canvasImgClick(layer){
    if(mD.xgsetN=='none'){
        setMsg(layer);
        setImgActInt();
    }
}//图片对象点击事件


function closeAnyAct() {
    mD.textInput.val('');
    mD.mbitem.removeClass('on');
    mD.imgShowTypeItem.removeClass('on');
    mD.textBorderIcon.css({'border-color':'#333','background':'#333'});
    mD.textColorIcon.css('background','#333');
    mD.svgBorderIcon.css('border-color','#333');
    mD.svgColorIcon.css('background','#333');
    $('.actbox .actbox_btns .ic').removeClass('on');

    $('.actbox,#tabs-2 .menu-bd,.imgShowType').addClass('noact');
    $('.fzbox i,#sc').removeClass('none');
    var i=getNowIndex();
    if(!i){
        $('.fzbox i,#sc').addClass('none');
        return false;
    }
    if(i==4){
        $('.fzbox i.x').addClass('none');
        $('.fzbox i.b').addClass('none');
    }else if(i==5){
        if(mD.allData[4].typeName=='mb'){
            $('.fzbox i.x').addClass('none');
            $('.fzbox i.b').addClass('none');
        }
    }
    if(i==mD.allData.length-1){
        $('.fzbox i.s').addClass('none');
        $('.fzbox i.t').addClass('none');
    }else if(i==mD.allData.length-2){
        if(mD.allData[i].typeName=='mb'){
            $('.fzbox i.s').addClass('none');
            $('.fzbox i.t').addClass('none');
        }
    }
}

function fdsx() {
    $('#sx,#fd').removeClass('none');
    if(mD.zoomNum<=.4){
        $('#sx').addClass('none')
    }
    if(mD.zoomNum>=2.5){
        $('#fd').addClass('none')
    }
}


function setImgActInt() {
    var layer=mD.allData[getNowIndex()];
    closeAnyAct();
    $('#tabs-5 .actbox,.imgShowType').removeClass('noact');
    mD.tabs.tabs({active:4});

    if(mD.allData[getNowIndex()-1].typeName=='mb'){
        getTheMbTypeLi(mD.allData[getNowIndex()-1].type)
    }else{
        mD.mbitem.removeClass('on');
    }
    mD.imgShowTypeItem.removeClass('on');
    getTheTypeLi(layer.imgType).addClass('on');

    var op=parseInt(layer.opacity*100);
    mD.opacityImgSlide.slider( "value",op);
    mD.opacitySlideVal_img.html(op);


    if(layer.shadowColor=='none'||layer.shadowColor=='transparent'||!layer.shadowColor){
        mD.showOpenImgShadow.removeClass('on');
    }else{
        mD.showOpenImgShadow.addClass('on');
        mD.imgColorShBtn[0].jscolor.fromString(rgbToHex(layer.shadowColor).hex)
    }


    mD.shadowImgBlur.slider( "value",Math.round(layer.shadowBlur));
    mD.valueBlurSh_img.html(Math.round(layer.shadowBlur));

    var x=Math.round(layer.shadowX);
    mD.shadowImgX.slider( "value",x);
    mD.valueShadowX_img.html(x);
    var y=Math.round(layer.shadowY);
    mD.shadowImgY.slider( "value",y);
    mD.valueShadowY_img.html(y);



    mD.shadowImgOp.slider("value",parseInt(layer.shOp*100));
    mD.shadowImgOpVal.html(parseInt(layer.shOp*100));
}

function setMbActInt() {
    closeAnyAct();
    $('.imgShowType').removeClass('noact');
    mD.tabs.tabs({active:3});
    if(mD.allData[getNowIndex()].typeName=='mb'){
        getTheMbTypeLi(mD.allData[getNowIndex()].type)
    }
}
function setSvgActInt() {
    var layer=mD.allData[getNowIndex()];
    closeAnyAct();
    $('#tabs-3 .actbox,.imgShowType').removeClass('noact');

    if(mD.allData[getNowIndex()-1].typeName=='mb'){
        getTheMbTypeLi(mD.allData[getNowIndex()-1].type)
    }else{
        mD.mbitem.removeClass('on');
    }
    mD.tabs.tabs({active:2});
    var op=parseInt(layer.opacity*100);
    mD.opacitySvgSlide.slider( "value",op);
    mD.opacitySlideVal_svg.html(op);

    if(layer.shadowColor=='none'||layer.shadowColor=='transparent'||!layer.shadowColor){
        mD.showOpenSvgShadow.removeClass('on');
    }else{
        mD.showOpenSvgShadow.addClass('on');
        mD.svgColorShBtn[0].jscolor.fromString(rgbToHex(layer.shadowColor).hex);
    }

    mD.shadowSvgBlur.slider( "value",Math.round(layer.shadowBlur));
    mD.valueBlurSh_svg.html(Math.round(layer.shadowBlur));

    mD.shadowSvgOp.slider("value",parseInt(layer.shOp*100));
    mD.shadowSvgOpVal.html(parseInt(layer.shOp*100));

    var x=Math.round(layer.shadowX);
    mD.shadowSvgX.slider( "value",x);
    mD.valueShadowX_svg.html(x);
    var y=Math.round(layer.shadowY);
    mD.shadowSvgY.slider( "value",y);
    mD.valueShadowY_svg.html(y);


    var val=100;
    if(layer.stroke=='none'||!layer.stroke){
        mD.showOpenSvgStroke.removeClass('on');
        mD.svgBorderIcon.css('border-color','#333');
    }else{
        var ojb=rgbToHex(layer.stroke);
        val=ojb.alpha;
        mD.showOpenSvgStroke.addClass('on');
        mD.svgBorderIcon.css('border-color',ojb.hex);
        mD.svgStrokeBtn[0].jscolor.fromString(ojb.hex);
    }


    mD.strokeOpacitySlide_svg.slider( "value",val);
    mD.strokeOpacitySlideVal_svg.html(val);


    var b=Math.round(layer.strokeWidth);
    mD.strokeWidthSlide_svg.slider( "value",b);
    mD.strokeWidthSlideVal_svg.html(b);


    mD.svgColorIcon.css('background',layer.fillColor);
    mD.svgColorBtn[0].jscolor.fromString(layer.fillColor);
}

function setTextActInt() {
    var layer=mD.allData[getNowIndex()];
    closeAnyAct();
    $('#tabs-2 .actbox,#tabs-2 .menu-bd,.imgShowType').removeClass('noact');
    mD.tabs.tabs({active:1});
    if(mD.allData[getNowIndex()-1].typeName=='mb'){
        getTheMbTypeLi(mD.allData[getNowIndex()-1].type)
    }else{
        mD.mbitem.removeClass('on');
    }
    mD.chFont.attr('data-ch', layer.fontC);
    mD.enFont.attr('data-en', layer.fontE);
    $('.fontCho').removeClass('on');
    mD.chFont.find(".fontCho[data-name='"+layer.fontC+"']").addClass('on');
    mD.enFont.find(".fontCho[data-name='"+layer.fontE+"']").addClass('on');

    mD.textInput.val(layer.text);

    if(layer.fontStyle=='bold italic'){
        mD.fontB.addClass('on');
        mD.fontI.addClass('on');
    }else if(layer.fontStyle=='bold'){
        mD.fontB.addClass('on');
        mD.fontI.removeClass('on');
    }else if(layer.fontStyle=='italic'){
        mD.fontI.addClass('on');
        mD.fontB.removeClass('on');
    }else if(layer.fontStyle=='normal'){
        mD.fontI.removeClass('on');
        mD.fontB.removeClass('on');
    }


    if(layer.underLine){
        mD.fontU.addClass('on');
    }else{
        mD.fontU.removeClass('on');
    }

    mD.letterSlide.slider( "value",layer.ls);
    mD.letterSlideVal.html(layer.ls);

    mD.lhSlide.slider( "value",layer.lineHeight);
    mD.lhSlideVal.html(layer.lineHeight);

    mD.fontSizeSlide.slider( "value",Math.round(layer.fontSize));
    mD.fontSizeVal.html(Math.round(layer.fontSize));
    mD.alCho.removeClass('on');
    if(layer.align=='right'){$('.alCho.r').addClass('on')}
    else if(layer.align=='center'){$('.alCho.c').addClass('on')}
    else if(layer.align=='left'){$('.alCho.l').addClass('on')}


    if(layer.shadowColor=='none'||layer.shadowColor=='transparent'||!layer.shadowColor){
        mD.showOpenTextShadow.removeClass('on');
    }else{
        mD.showOpenTextShadow.addClass('on');
        mD.textColorShBtn[0].jscolor.fromString(rgbToHex(layer.shadowColor).hex);
    }

    mD.shadowTextBlur.slider( "value",Math.round(layer.shadowBlur));
    mD.valueBlurSh_text.html(Math.round(layer.shadowBlur));

    mD.shadowTextX.slider( "value",Math.round(layer.shadowX));
    mD.valueShadowX_text.html(Math.round(layer.shadowX));

    mD.shadowTextY.slider( "value",Math.round(layer.shadowY));
    mD.valueShadowY_text.html(Math.round(layer.shadowY));

    mD.shadowTextOp.slider("value",parseInt(layer.shOp*100));
    mD.shadowTextOpVal.html(parseInt(layer.shOp*100));

    var val=100;
    if(layer.strokeStyle=='none'||!layer.strokeStyle||layer.strokeStyle=='transparent'){

        mD.showOpenTextStrokebox.removeClass('on');
        mD.textBorderIcon.css({'border-color':'#333','background':'#333'});
    }else{
        var ojb=rgbToHex(layer.strokeStyle);
        val=ojb.alpha;
        mD.showOpenTextStrokebox.addClass('on');
        mD.textBorderIcon.css({'border-color':ojb.hex,'background':ojb.hex});
        mD.textStrokeWidthBtn[0].jscolor.fromString(ojb.hex);
    }
    mD.strokeOpacityTextSlide.slider( "value",val);
    mD.strokeOpacityTextSlideVal.html(val);
    //
    // mD.strokeWidthTextSlide.slider( "value",Math.round(layer.strokeWidth));
    // mD.strokeWidthTextSlideVal.html(Math.round(layer.strokeWidth));

    mD.opacityTextSlide.slider( "value",parseInt(layer.opacity*100));
    mD.opacitySlideVal_text.html(parseInt(layer.opacity*100));

    mD.textColorIcon.css('background',layer.fillStyle);
    mD.textColorBtn[0].jscolor.fromString(layer.fillStyle);



}

function setMsg(layer) {
    mD.actName=layer.name;
    var w=layer.width;
    var h=layer.height;
    var r=layer.rotate;
    if(layer.typeName=='mb'){
        w=layer.width*layer.scaleX;
        h=layer.height*layer.scaleY;
    }
    var l=layer.x-w/2;
    var t=layer.y-h/2;
    if(layer.typeName=='text'){
        var mm= mD.canvas.measureText(mD.actName);
        w=mm.width*mm.scaleX;
        h=mm.height*mm.scaleY;
        l=mm.x-w/2;
        t=mm.y-h/2;
        mD.actBox.addClass('noresize')
    }else{
        mD.actBox.removeClass('noresize')
    }
    mD.actBox.css({
        transform:'rotate('+r+'deg)',
        width:w,
        height:h,
        left:l-2,
        top:t-2
    }).css('display','inline-block');
    showPoMsg(l,t);
    showKgMsg(w,h);
    showRotateMsg(r);
    mD.mouseOn=1;
}//设置操作框宽高位置。



mD.doc.on("mousedown",function(e){
    var target=$(e.target);

    if(target.closest(".sl").length == 0){
        if(target.closest("#canvas_1").length != 0&&$('.xgset.on').length!=0){
           var x= e.pageX-mD.canvas.offset().left;
           var y= e.pageY+15-mD.canvas.offset().top;
           var ctx = mD.canvas[0].getContext("2d");
           var c = ctx.getImageData(x, y, 1, 1).data;
           if(c[3]!=0){
               var jscolor= rgbToHex('rgb('+c[0]+','+c[1]+','+c[2]+')',true).hex;
               document.getElementById(mD.xgsetN).jscolor.fromString(jscolor);
              if(mD.xgsetN=='valueInput_btn'){
                  updateSvgStroke(jscolor);
              }
              else if(mD.xgsetN=='valueInput2_btn'){
                  updateShColor(jscolor);
              }
              else if(mD.xgsetN=='valueInput3_btn'){
                  updateSvgColor(jscolor);
              }
              else if(mD.xgsetN=='valueInput4_btn'){
                  updateShColor(jscolor);
              }
              else if(mD.xgsetN=='valueInput5_btn'){
                  updateTextColor(jscolor)
              }
              else if(mD.xgsetN=='valueInput6_btn'){
                  updateTextStroke(jscolor);
              }
              else if(mD.xgsetN=='valueInput7_btn'){
                  updateShColor(jscolor);
              }
           }
        }else{
            if(target.closest(".actbox_btns").length == 0&&target.closest("#imgAct").length == 0&&target.closest("#jscolor").length == 0){
                if(mD.mouseOn=='none'){
                    $(".actbox-po").hide();
                    $('.hasPo .ic').removeClass('on')
                }
            }
            if(target.closest(".cutImgBox").length == 0&&target.closest(".someAct span").length == 0&&target.closest(".leftbar").length == 0&&target.closest("#imgAct").length == 0&&target.closest("#jscolor").length == 0){
                if(mD.mouseOn=='none'){
                    mD.actName='none';
                    mD.actBox.hide();
                    closeAnyAct();
                }
            }
            if(mD.mouseOn!='none'){ mD.mouseOn='none';}
        }
        if($('.xgset.on').length!=0){
            mD.actBox.removeClass('dn');
            $('.xgset').removeClass('on');
            $('.curset').removeClass('curset');
            mD.xgsetN='none';
        }

    }
});

$('.xgset').click(function () {
    if($(this).hasClass('on')) return;
    mD.xgsetN= $(this).attr('data-set');
    $(this).addClass('on');
    mD.canvas.addClass('curset');
    mD.actBox.addClass('curset');
    mD.rotateAct.addClass('curset');
    mD.actBox.addClass('dn');
    $('.ui-resizable-handle').addClass('curset');
})

mD.actBox.on('mouseout',function(){
    mD.mouseOn='none'
});


$('#fz .x').click(function () {
    if(mD.actName=='none'){
        return false;
    }
    cxD();
    var index=getNowIndex();
    if(mD.allData[index].typeName=='mb'){
        if(index==4){return false;}
        var m=$.extend({},mD.allData[index]);
        var o=$.extend({},mD.allData[index+1]);
        mD.allData.splice(index,2);
        if(mD.allData[index-2].typeName=='mb'){
            mD.allData.splice(index-2,0,m,o)
        }else{
            mD.allData.splice(index-1,0,m,o)
        }
    }else{
        var o=$.extend({},mD.allData[index]);
        if(mD.allData[index-1].typeName=='mb'){
            if(index-1==4){return false;}
            var m=$.extend({},mD.allData[index-1]);
            mD.allData.splice(index-1,2);
            if(mD.allData[index-3].typeName=='mb'){
                mD.allData.splice(index-3,0,m,o)
            }else{
                mD.allData.splice(index-2,0,m,o)
            }
        }else{
            if(index==4){return false;}
            mD.allData.splice(index,1);
            if(mD.allData[index-2].typeName=='mb'){
                mD.allData.splice(index-2,0,o);
            }else{
                mD.allData.splice(index-1,0,o);
            }
        }
    }
    setCanvas();
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
});

$('#fz .s').click(function () {

    if(mD.actName=='none'){
        return false;
    }
    cxD();
    var index=getNowIndex();
    var len=mD.allData.length-1;
    if(mD.allData[index].typeName=='mb'){
        if(index==len-1){return false;}
        var m=$.extend({},mD.allData[index]);
        var o=$.extend({},mD.allData[index+1]);
        mD.allData.splice(index,2);
        if(mD.allData[index].typeName=='mb'){
            mD.allData.splice(index+2,0,m,o)
        }else{
            mD.allData.splice(index+1,0,m,o)
        }
    }else{
        if(index==len){return false;}
        var o=$.extend({},mD.allData[index]);
        if(mD.allData[index-1].typeName=='mb'){
            var m=$.extend({},mD.allData[index-1]);
            mD.allData.splice(index-1,2);
            if(mD.allData[index-1].typeName=='mb'){
                mD.allData.splice(index+1,0,m,o)
            }else{
                mD.allData.splice(index,0,m,o)
            }
        }else{
            mD.allData.splice(index,1);
            if(mD.allData[index].typeName=='mb'){
                mD.allData.splice(index+2,0,o);
            }else{
                mD.allData.splice(index+1,0,o);
            }
        }
    }
    setCanvas();
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
});

$('#fz .t').click(function () {
    if(mD.actName=='none'){
        return false;
    }
    cxD();
    var index=getNowIndex();
    var len=mD.allData.length-1;
    if(mD.allData[index].typeName=='mb'){
        if(index==len-1){return false;}
        var m=$.extend({},mD.allData[index]);
        var o=$.extend({},mD.allData[index+1]);
        mD.allData.splice(index,2);
        mD.allData.push(m);
        mD.allData.push(o)
    }else{
        if(index==len){return false;}
        var o=$.extend({},mD.allData[index]);
        if(mD.allData[index-1].typeName=='mb'){
            var m=$.extend({},mD.allData[index-1]);
            mD.allData.splice(index-1,2);
            mD.allData.push(m);
            mD.allData.push(o)
        }else{
            mD.allData.splice(index,1);
            mD.allData.push(o)
        }
    }
    setCanvas();
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
});

$('#fz .b').click(function () {
    if(mD.actName=='none'){
        return false;
    }
    cxD();
    var index=getNowIndex();
    if(mD.allData[index].typeName=='mb'){
        if(index==4){return false;}
        var m=$.extend({},mD.allData[index]);
        var o=$.extend({},mD.allData[index+1]);
        mD.allData.splice(index,2);
        mD.allData.splice(4,0,m)
        mD.allData.splice(5,0,o)
    }else{
        var o=$.extend({},mD.allData[index]);
        if(mD.allData[index-1].typeName=='mb'){
            if(index-1==4){return false;}
            var m=$.extend({},mD.allData[index-1]);
            mD.allData.splice(index-1,2);
            mD.allData.splice(4,0,m);
            mD.allData.splice(5,0,o)
        }else{
            if(index==4){return false;}
            mD.allData.splice(index,1);
            mD.allData.splice(4,0,o)
        }
    }
    setCanvas();
    mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
});


$('#sc').click(function () {
    if(mD.actName=='none'){
        return false;
    }
    cxD();
    if(mD.allData[getNowIndex()-1].typeName=='mb'){
        mD.allData.splice(getNowIndex()-1,2);
    }else{
        mD.allData.splice(getNowIndex(),1);
    }
    setCanvas();
    mD.actName='none';
    mD.actBox.hide();
    closeAnyAct();

});

function changeT(srcObj,bH) {
    var obj={};
    var b=bH/srcObj.attr('data-th');
        obj.src=srcObj.attr('data-src');
        obj.x=srcObj.attr('data-x')*b;
        obj.y=srcObj.attr('data-y')*b;
        obj.h=srcObj.attr('data-h')*b;
        obj.w=srcObj.attr('data-w')*b;
        obj.w2=srcObj.attr('data-tw')*b;
        obj.h2=srcObj.attr('data-th')*b;

    mD.allData[0].source=$('.Timg .'+obj.src)[0];
    mD.allData[0].width=obj.w2;
    mD.allData[0].height=obj.h2;
    mD.allData[1].x=obj.x;
    mD.allData[1].y=obj.y;
    mD.allData[1].width=obj.w;
    mD.allData[1].height=obj.h;
    mD.allData[2].x1=obj.x+ obj.w/2;
    mD.allData[2].y1=obj.y;
    mD.allData[2].x2=obj.x+ obj.w/2;
    mD.allData[2].y2=obj.y+ obj.h;
    mD.allData[3].x1=obj.x;
    mD.allData[3].y1=obj.y+obj.h/2;
    mD.allData[3].x2=obj.x+ obj.w;
    mD.allData[3].y2=obj.y+obj.h/2;
    $('#diybox').css({
        width:obj.w2,
        height:obj.h2
    });
    mD.canvas[0].width=obj.w2;
    mD.canvas[0].height=obj.h2;
    setCanvas();
    if(mD.actName!='none'){
        mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
        mD.tabs.tabs({active:0});
    }
    setSl();
}

mD.choT.find('span').click(function () {
    if($(this).hasClass('on')){return false}
    var i=$(this).index();
    mD.choT.find('span').removeClass('on');
    mD.choT.find('span').each(function () {
        $(this).css({background:$(this).attr('data-img')})
    })
    $(this).addClass('on').css({background:$(this).attr('data-imgon')});
    mD.choTcolor.find('ul').hide();
    mD.choTcolor.find('ul').eq(i).show();
    mD.choTcolor.find('li').removeClass('on');
    mD.choTcolor.find('ul').eq(i).find('li').eq(0).addClass('on');
   changeT(mD.choTcolor.find('li.on'),mD.allData[0].height);
});
mD.choTcolor.find('li').click(function () {
    if($(this).hasClass('on')){return false}
    mD.choTcolor.find('li').removeClass('on');
    $(this).addClass('on');
    changeT($(this),mD.allData[0].height);
});

function initOne() {
    var typeTel=mD.choT.find('span').eq(0);
    var colorTel=mD.choTcolor.find('ul').eq(0).find('li').eq(0);
    mD.choTcolor.find('ul').eq(0).show();
    var h=parseInt(($(window).height()-$('.header').height()-$('.someAct').height()-parseInt($('.someAct').css('top'))-$('.datashow').height()-100)/(mD.initH/10))*(mD.initH/10);
    typeTel.addClass('on');
    colorTel.addClass('on');
    changeT(colorTel,h);
    setCanvasSize(h)
}

function firstOne() {
    mD.allData[0]={
        typeName:'imgT',
        drawWhat:'img',
        name:'imgT',
        fromCenter: false,
        layer: true,
        index: 0,
        draggable:false,
        x:0, y:0
    };
    mD.allData[1]={
        typeName:'actB',
        drawWhat:'rect',
        name: 'boxT',
        fromCenter: false,
        layer: true,
        mask:true,
        draggable:false,
        strokeStyle: '#ccc',
        strokeDash: [3],
        strokeDashOffset: 0,
        strokeWidth: 1
    };
    mD.allData[2]={
        typeName:'line',
        drawWhat:'line',
        name: 'lineH',
        layer: true,
        fromCenter: false,
        visible:false,
        draggable:false,
        strokeStyle: '#F52316',
        strokeWidth: 1

    };
    mD.allData[3]={
        typeName:'line',
        drawWhat:'line',
        name: 'lineW',
        layer: true,
        fromCenter: false,
        visible:false,
        draggable:false,
        strokeStyle: '#F52316',
        strokeWidth: 1

    };
}


function setZoom(h) {
    mD.zoomNum=(h/mD.initH).toFixed(1);
    mD.zoom.html(Math.round(mD.zoomNum*100)+'%');
}



function setCanvasSize(h) {
    var b= h/mD.allData[0].height;
    for(var i=0;i<mD.allData.length;i++){
        if(mD.allData[i].typeName=='line'){
            mD.allData[i].x1*=b;
            mD.allData[i].x2*=b;
            mD.allData[i].y1*=b;
            mD.allData[i].y2*=b;
        }
        else if(mD.allData[i].typeName=='imgT'){
            mD.allData[i].width*=b;
            mD.allData[i].height*=b;
        }
        else if(mD.allData[i].typeName=='actB'){
            mD.allData[i].width*=b;
            mD.allData[i].height*=b;
            mD.allData[i].x*=b;
            mD.allData[i].y*=b;
        }
        else if(mD.allData[i].typeName=='text'){
            mD.allData[i].x*=b;
            mD.allData[i].y*=b;
            mD.allData[i].fontSize=mD.allData[i].fontSize*b;
            mD.allData[i].shadowX*=b;
            mD.allData[i].shadowY*=b;
            mD.allData[i].shadowBlur*=b;
            mD.allData[i].strokeWidth*=b;
        }
        else if(mD.allData[i].typeName=='svg'||mD.allData[i].typeName=='img'){
            mD.allData[i].width*=b;
            mD.allData[i].height*=b;
            mD.allData[i].x*=b;
            mD.allData[i].y*=b;
            mD.allData[i].shadowX*=b;
            mD.allData[i].shadowY*=b;
            mD.allData[i].shadowBlur*=b;
        }
        else if(mD.allData[i].typeName=='mb'){
            mD.allData[i].x*=b;
            mD.allData[i].y*=b;
            mD.allData[i].scaleX*=b;
            mD.allData[i].scaleY*=b;
        }
    }
    $('#diybox').css({
        width:mD.allData[0].width,
        height:mD.allData[0].height
    });
    mD.canvas[0].width=mD.allData[0].width;
    mD.canvas[0].height=mD.allData[0].height;
    setZoom(h);
    mD.shadowX.slider({
        min: -1*Math.round(mD.pyX*mD.zoomNum),
        max: Math.round(mD.pyX*mD.zoomNum)
    });//投影X偏移操作
    mD.shadowY.slider({
        min: -1*Math.round(mD.pyX*mD.zoomNum),
        max: Math.round(mD.pyX*mD.zoomNum)
    });//投影Y偏移操作
    mD.fontSizeSlide.slider({
        min: Math.round(mD.fontSmin*mD.zoomNum),
        max:Math.round(mD.fontSmax*mD.zoomNum)
    });//文本字体大小操作
    mD.shadowBlur.slider({
        max:Math.round(mD.shadowBlurMax*mD.zoomNum)
    });//文本字体大小操作

    // mD.strokeWidthTextSlide.slider({
    //     max:Math.round(mD.strokeWidthTextMax*mD.zoomNum)
    // });//文本字体描边操作
    setCanvas();
    if(mD.actName!='none'){
        mD.canvas.triggerLayerEvent(mD.actName, 'mousedown');
    }
    setSl();
    fdsx()
}
function setSl() {
    var el=$('#diybox');
    var h=el.height();
    var w=el.width();
    var h2=mD.cbox.height();
    var w2=mD.cbox.width();
    var mw=150;
    var t=Math.abs(parseInt(el.css('top')))/h*(h/w*mw);
    var l=Math.abs(parseInt(el.css('left')))/w*mw;
    mD.slP.css({
        'background-image':'url('+mD.allData[0].source.src+')',
        width:mw,
        height:h/w*mw
    });
    if(h<h2){ mD.slA.css({height:'100%',top:0})}else{
        mD.slA.css({height:h2/h*(h/w*mw),top:t})
    }
    if(w<w2){ mD.slA.css({width:'100%',left:0})}else{
        mD.slA.css({width:w2/w*mw,left:l})
    }
    if(h<h2&&w<w2){
        $('.sl').removeClass('on');
    }else{
        $('.sl').addClass('on');
    }
}

mD.slA.draggable({
    containment: ".slactBox",
    scroll: false ,
    drag: function( event, ui ) {
        var el=$('#diybox');
        el.css({
            top:ui.position.top/mD.slP.height()*el.height()*-1,
            left:ui.position.left/mD.slP.width()*el.width()*-1
        })
    }
});//缩略图拖拽事件



function zj(e) {
    if(mD.zoomNum>=2.5) return false;
    if(mD.isFirefox){
        canfy=false;
    }
    var el=$('#diybox');
    var h=el.height();
    var h2=mD.cbox.height();
    var w=mD.cbox.width();
    if(h>h2){
        el.css({
            top:parseInt(el.css('top'))-(mD.initH/10)*(e.clientY-el.offset().top)/h
        })
    }
    if(el.width()>w){
        el.css({
            left:parseInt(el.css('left'))-(el.width()/(mD.zoomNum*10))*(e.clientX-el.offset().left)/el.width()
        })
    }
    setCanvasSize(mD.canvas[0].height+mD.initH/10);

}

function jx(e) {
    if(mD.zoomNum<=0.4) return false;
    if(mD.isFirefox){
        canfy=false;
    }
    var el=$('#diybox');
    var h=el.height();
    var h2=mD.cbox.height();
    var w=mD.cbox.width();
    if(h>h2){
        var vl2=parseInt(el.css('top'))+(mD.initH/10)*(e.clientY-el.offset().top)/h;
        if(vl2>0){
            vl2=0
        }else{
            if(el.offset().top+h<=$('.datashow').offset().top){
                vl2=parseInt(el.css('top'))+mD.initH/10
            }
        }
        el.css({
            top:vl2
        })
    }else{
        el.css({
            top:0
        })
    }
    if(el.width()>w){
        var vl=parseInt(el.css('left'))+(el.width()/(mD.zoomNum*10))*(e.clientX-el.offset().left)/el.width();
        if(vl>0){
            vl=0
        }else{
            if(el.offset().left+el.width()<=$('.rightbar').offset().left){
                vl=parseInt(el.css('left'))+el.width()/(mD.zoomNum*10)
            }
        }
        el.css({
            left:vl
        })
    }else{
        el.css({
            left:0
        })
    }
    setCanvasSize(mD.canvas[0].height-mD.initH/10);
}


function scrollFunc(e) {
    if(!mD.mouseOnT) return false;
            var direct=0;
            e=e || window.event;
            var z=0;
            if(e.wheelDelta){//IE/Opera/Chrome
                if(e.wheelDelta>0){
                    z=1;
                }else{
                    z=0
                }
            }else if(e.detail){//Firefox
                if(e.detail>0){
                    z=0;
                }else{
                    z=1
                }
            }
            if(z==1){

                zj(e);
            }else{

                jx(e)
            }
            if(mD.isFirefox){
                canfy=true;
                mD.canvas.drawLayers();
            }
}

if(document.addEventListener){
    document.addEventListener('DOMMouseScroll',scrollFunc,false);
}
window.onmousewheel=document.onmousewheel=scrollFunc;//IE/Opera/Chrome

mD.cbox.on('mouseover',function(){
    mD.mouseOnT=true

});
mD.cbox.on('mouseout',function(){
    mD.mouseOnT=false
});

$('#fd').click(function (e) {
    zj(e);
})
$('#sx').click(function (e) {
    jx(e)
})

mD.imgShowTypeItem.each(function () {
    var types= $(this).attr('data-type');
    if(types!='none'){
        var img=$(this).find('img')[0];
        img.loadOnce(function(){
            var aiObj = $AI(img);
            aiObj.ps(types).replace(img);
        });
        img.src=img.src;
    }
});



$('.ylbtn .b1').click(function () {
    var h=mD.allData[0].height;
    mD.allData[1].strokeStyle='transparent';
    for(var is=0;is<mD.allData.length;is++){
        if(mD.allData[is].typeName=='mb'){
            mD.allData[is].fillStyle='transparent'
        }
    }
    setCanvasSize(900);
    // var ctx=mD.canvas[0].getContext("2d");
    // var imgData=ctx.getImageData(mD.allData[1].x,mD.allData[1].y,mD.allData[1].width,mD.allData[1].height);
    // var c=document.createElement("canvas");
    // var ctx2=c.getContext("2d");
    $('.ylImgbox img').attr('src',mD.canvas.getCanvasImage('png'));
    $('.ylImg').show();
    mD.allData[1].strokeStyle='#ccc';
    for(var is2=0;is2<mD.allData.length;is2++){
        if(mD.allData[is2].typeName=='mb'){
            mD.allData[is2].fillStyle='rgba(0,0,0,0.1)'
        }
    }
    setCanvasSize(h);
});

$('.ylbtn .b2').click(function () {
    var h=mD.allData[0].height;
    mD.allData[1].strokeStyle='transparent';
    mD.allData[0].visible=false;
    for(var is=0;is<mD.allData.length;is++){
        if(mD.allData[is].typeName=='mb'){
            mD.allData[is].fillStyle='transparent'
        }
    }
    setCanvasSize(6000);
    var ctx=mD.canvas[0].getContext("2d");
    var imgData=ctx.getImageData(mD.allData[1].x,mD.allData[1].y,mD.allData[1].width,mD.allData[1].height);
    var c=document.createElement("canvas");
    c.width=mD.allData[1].width;
    c.height=mD.allData[1].height;
    var ctx2=c.getContext("2d");
    ctx2.putImageData(imgData,0,0);
    $('.ylImgbox img').attr('src',c.toDataURL("image/png")).addClass('spe');
    $('.ylImg').show();
    mD.allData[0].visible=true;
    mD.allData[1].strokeStyle='#ccc';
    for(var is2=0;is2<mD.allData.length;is2++){
        if(mD.allData[is2].typeName=='mb'){
            mD.allData[is2].fillStyle='rgba(0,0,0,0.1)'
        }
    }
    setCanvasSize(h);
});

$('.ylImg i em').click(function () {
    $('.ylImg').hide();
    $('.ylImgbox img').attr('src','').removeClass('spe');
});


function dr(l,t) {
    var act=mD.allData[getNowIndex()];
    var lw=l+act.width/2;
    var lh=t+act.height/2;
    if(act.typeName=='mb'){
        lw=l+act.width*act.scaleX/2;
        lh=t+act.height*act.scaleY/2;
    }else  if(act.typeName=='text'){
        var ll=mD.canvas.measureText(mD.actName);
        lw=l+ll.width*act.scaleX/2;
        lh=t+ll.height*act.scaleY/2;
    }
    changeCanv({x:lw,y:lh},1);
    showPoMsg(l,t);
}

function cxD(l) {
    if(mD.cxData.length>50){
        mD.cxData.splice(0,1)
    }
    if(l!=1){
        var o=$.extend(true,[],mD.allData);
        mD.cxData.push(o)
    }
}
document.onkeyup=function(event){
    var e = event || window.event || arguments.callee.caller.arguments[0];
    if(e){
        var code=e.keyCode;
        if(code==37){ // 按 left
                mD.key.left=false
        }
        if(code==38){ // 按 up
                mD.key.up=false
        }
        if(code==39){ // 按 right
                mD.key.right=false
        }
        if(code==40){ // 按 down
                mD.key.down=false
        }
        if(code==32){
            mD.moveb.hide();
            mD.moveb.mouseup();
        }
    }
}

mD.moveb.on('mousedown',function (e) {
    var el=$('#diybox');
    $(this).attr({'data-x':e.pageX,'data-y':e.pageY,'data-l':parseFloat(el.css('left')),'data-t':parseFloat(el.css('top'))}).addClass('on');
}).on('mousemove',function (e) {
    if($(this).hasClass('on')){
        var el=$('#diybox');
        var el2=$('.cbox');
        var l=  parseFloat($(this).attr('data-l'));
        var t=  parseFloat($(this).attr('data-t'));
        var xz= parseFloat($(this).attr('data-x'));
        var xy= parseFloat($(this).attr('data-y'));
        var we=el.width();
        var he=el.height();
        var we2=el2.width();
        var he2=el2.height();
        var x=l+(e.pageX-xz);
        var y=t+(e.pageY-xy);
        if(x>=0||we<we2){
            x=0;
        }else if(x<we2-we){
            x=we2-we;
        }
        if(y>=0||he<he2){
            y=0;
        }else if(y<he2-he){
            y=he2-he;
        }
        el.css({
            left:x,
            top:y
        });
        setSl()
    }
}).on('mouseup',function () {
    $(this).removeClass('on');
});

document.onkeydown=function(event){
       var e = event || window.event || arguments.callee.caller.arguments[0];
        if(e){
            var code=e.keyCode;
            var l=0;
            var t=0;
            if(!e.ctrlKey&&!e.shiftKey){
                    if(code==107&&mD.moveb.is(':hidden')){ // 按 +
                        $('#fd').click();
                    }
                    if(code==109&&mD.moveb.is(':hidden')){ // 按 -
                        $('#sx').click();
                    }
                    if(code==32&&mD.moveb.is(':hidden')&&$('.sl').hasClass('on')){
                        mD.moveb.show();
                    }
                    if(mD.actName!='none'){
                        if((code==37||code==38||code==39||code==40)&&!mD.key.down&&!mD.key.up&&!mD.key.left&&!mD.key.right){
                            cxD();
                        }
                        if(code==37){ // 按 left
                            if(!mD.key.left){
                                mD.key.left=true
                            }
                            l=parseFloat(mD.actBox.css('left'))-1;
                            t=parseFloat(mD.actBox.css('top'));
                            dr(l+2,t+2);
                            mD.actBox.css({left:l});
                        }
                        if(code==38){ // 按 up
                            if(!mD.key.up){
                                mD.key.up=true
                            }
                            l=parseFloat(mD.actBox.css('left'));
                            t=parseFloat(mD.actBox.css('top'))-1;
                            dr(l+2,t+2);
                            mD.actBox.css({top:t})
                        }
                        if(code==39){ // 按 right
                            if(!mD.key.right){
                                mD.key.right=true
                            }
                            l=parseFloat(mD.actBox.css('left'))+1;
                            t=parseFloat(mD.actBox.css('top'));
                            dr(l+2,t+2);
                            mD.actBox.css({left:l})
                        }
                        if(code==40){ // 按 down
                            if(!mD.key.down){
                                mD.key.down=true
                            }
                            l=parseFloat(mD.actBox.css('left'));
                            t=parseFloat(mD.actBox.css('top'))+1;
                            dr(l+2,t+2);
                            mD.actBox.css({top:t})
                        }

                        if(code==46){ // 按 Delete
                            $('#sc').click();
                        }
                    }
            }
            else if (e.ctrlKey&&!e.shiftKey) {  //这里只能用alt，shift，ctrl等去组合其他键event.altKey、event.ctrlKey、event.shiftKey 属性
                if(code == 67&&mD.actName!='none'){
                        mD.fzData=[];
                        var layer=mD.allData[getNowIndex()];
                        if(layer.typeName=='mb'){
                            var layer2=mD.allData[getNowIndex()+1];
                            var newObject = $.extend({}, layer);
                            var newObject2 = $.extend({}, layer2);
                            mD.fzData.push(newObject,newObject2)
                        }else{
                            var layer3=mD.allData[getNowIndex()-1];
                            if(layer3.typeName=='mb'){
                                var newObject3 = $.extend({}, layer3);
                                var newObject4 = $.extend({}, layer);
                                mD.fzData.push(newObject3,newObject4)
                            }else{
                                var newObject5 = $.extend({}, layer);
                                mD.fzData.push(newObject5)
                            }
                        }
                }else if(code==86&&mD.fzData.length!=0){
                        var newObject6=null;
                        var name='';
                        for(var x=0;x<mD.fzData.length;x++){
                            newObject6= $.extend({}, mD.fzData[x]);
                            newObject6.x+=10;
                            newObject6.y+=10;
                            name='fz'+mD.num;
                            newObject6.name=name;
                            mD.allData.push(newObject6);
                            mD.num++;
                        }
                        setCanvas();
                        mD.canvas.triggerLayerEvent(name, 'mousedown');
                  }else if(code==90&&mD.cxData.length!=0){
                            mD.actName='none';
                            mD.actBox.hide();
                            $(".actbox-po").hide();
                            if($('.ic.on').next('.actbox-po').find('.colorbox-tb').length!=0){
                                $('.ic.on').next('.actbox-po').find('.colorbox-tb')[0].jscolor.hide()
                            }
                            closeAnyAct();
                            mD.allData=$.extend(true,[],mD.cxData[mD.cxData.length-1]);
                            mD.cxData.splice(mD.cxData.length-1,1);
                            setCanvasSize(mD.canvas.height())
                    }
            }else if(e.shiftKey&&!e.ctrlKey){
                if($('.sl').hasClass('on')){
                    var el=$('#diybox');
                    var le=parseFloat(mD.slA.css('left'));
                    var to=parseFloat(mD.slA.css('top'));
                    var we=mD.slA.width();
                    var he=mD.slA.height();
                    var we2=mD.slP.width();
                    var he2=mD.slP.height();
                    var n=4;
                    if(code==37){ // 按 left
                        if(le>=n){
                            le-=n;
                        }
                    }
                    if(code==38){ // 按 up
                        if(to>=n){
                            to-=n;
                        }
                    }
                    if(code==39){ // 按 right
                        if(le<=(we2-we-n)){
                            le+=n;
                        }
                    }
                    if(code==40){ // 按 down
                        if(to<=(he2-he-n)){
                            to+=n;
                        }
                    }
                    el.css({
                        left:le/we2*el.width()*-1,
                        top:to/he2*el.height()*-1
                    });
                    mD.slA.css({
                        left:le,
                        top:to
                    })
                }
            }
        }
};

$('.pdNameText').on('blur',function () {
    if($(this).val()==''){
        $(this).val('未命名作品');
        $(this).removeClass('on')
    }
}).focus(function (){
    if($(this).val()=='未命名作品'){
        $(this).val('');
    }
    $(this).addClass('on')
});

$(window).load(function () {
    $('.loads').animate({opacity:0},400,function () {
        $('.loads').hide();
        closeAnyAct();
        firstOne();
        initOne();
        $(".sbr").mCustomScrollbar({
            autoDraggerLength:false,
            advanced:{updateOnContentResize:true}
        });
    });
});