<?php echo $this->fetch('pageheader_bd.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,selectzone_bd.js,validator.js')); ?>
<script type="text/javascript" src="../js/jquery.ztree.all-3.5.min.js"></script>
<script type="text/javascript" src="../js/category_selecter.js"></script>
<link href="styles/zTree/zTreeStyle.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/My97DatePicker/WdatePicker.js"></script>

<style>
.divScroll{
width:auto;
	  overflow-y:scroll;
        scrollbar-face-color: #FFFFFF;
        scrollbar-shadow-color: #D2E5F4;
        scrollbar-highlight-color: #D2E5F4;
        scrollbar-3dlight-color: #FFFFFF;
        scrollbar-darkshadow-color: #FFFFFF;
        scrollbar-track-color: #FFFFFF; 
      scrollbar-arrow-color: #D2E5F4;
        }
</style>

<?php if ($this->_var['warning']): ?>
<ul style="padding:0; margin: 0; list-style-type:none; color: #CC0000;">
  <li style="border: 1px solid #CC0000; background: #FFFFCC; padding: 10px; margin-bottom: 5px;" ><?php echo $this->_var['warning']; ?></li>
</ul>
<?php endif; ?>

<form enctype="multipart/form-data" action="" method="post" name="theForm" class="layui-form layui-form-pane">
<div class="layui-tab layui-tab-card">
    <ul class="layui-tab-title">
        <li class="layui-this"><?php echo $this->_var['lang']['tab_general']; ?></li>
        <li><?php echo $this->_var['lang']['tab_detail']; ?></li>
        <li><?php echo $this->_var['lang']['tab_mix']; ?></li>
        <?php if ($this->_var['goods_type_list']): ?><li><?php echo $this->_var['lang']['tab_properties']; ?></li><?php endif; ?>
        <li><?php echo $this->_var['lang']['tab_gallery']; ?></li>
        <li><?php echo $this->_var['lang']['tab_linkgoods']; ?></li>
        <?php if ($this->_var['code'] == ''): ?><li><?php echo $this->_var['lang']['tab_groupgoods']; ?></li><?php endif; ?>
        <li style="display:none;"><?php echo $this->_var['lang']['tab_article']; ?></li>
    </ul>

    <!-- 最大文件限制 -->
    <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
    <div class="layui-tab-content">
        <!-- 通用信息 -->
        <div class="layui-tab-item layui-show">
            <!--商品名称-->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['lab_goods_name']; ?></label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" placeholder="请输入商品名称" autocomplete="off" name="goods_name" value="<?php echo htmlspecialchars($this->_var['goods']['goods_name']); ?>" style="width:80%;">
                    <!--<div style="background-color:<?php echo $this->_var['goods_name_color']; ?>;float:left;margin-left:2px;" id="font_color" onclick="ColorSelecter.Show(this);">
                    <img src="images/color_selecter.gif" style="margin-top:-1px;" />
                    </div>
                    <input type="hidden" id="goods_name_color" name="goods_name_color" value="<?php echo $this->_var['goods_name_color']; ?>" />
                    &nbsp;
                    <select name="goods_name_style">
                        <option value=""><?php echo $this->_var['lang']['select_font']; ?></option>
                        <?php echo $this->html_options(array('options'=>$this->_var['lang']['font_styles'],'selected'=>$this->_var['goods_name_style'])); ?>
                    </select>-->
                </div>
            </div>
            <!--商品货号-->
            <div class="layui-form-item">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticeGoodsSN');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_goods_sn']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="goods_sn" value="<?php echo htmlspecialchars($this->_var['goods']['goods_sn']); ?>" size="20" onblur="checkGoodsSn(this.value,'<?php echo $this->_var['goods']['goods_id']; ?>')" />
                    <span id="goods_sn_notice"></span>
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticeGoodsSN"><?php echo $this->_var['lang']['notice_goods_sn']; ?></span>
                </div>
            </div>
            <!--商品分类-->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['lab_goods_cat']; ?></label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" id="cat_name" name="cat_name" nowvalue="<?php echo $this->_var['goods_cat_id']; ?>" value="<?php echo $this->_var['goods_cat_name']; ?>">
                    <input type="hidden" id="cat_id" name="cat_id" value="<?php echo $this->_var['goods_cat_id']; ?>">
                </div>
            </div>
            <!--店内分类-->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 店内分类</label>
                <div class="layui-input-block">
                    <div  style="float:left;border:1px solid #ddd;width:auto;height:140px;padding:5px 15px 5px 0; " class="divScroll">
                        <?php echo $this->_var['catstr']; ?>
                    </div>
                </div>
            </div>
            <!--商品品牌-->
            <div class="layui-form-item">
                <label class="layui-form-label">
                    <?php echo $this->_var['lang']['lab_goods_brand']; ?>
                </label>
                <div class="layui-input-block">
                    <input id="brand_search" name="brand_search" type="text" value="<?php echo empty($this->_var['brand_name_val']) ? '请输入……' : $this->_var['brand_name_val']; ?>" onclick="onC_search()" onblur="onB_search()" oninput="onK_search(this.value)" class="layui-input" />
                    <input id="brand_search_bf" name="brand_search_bf" type="hidden" value="<?php echo $this->_var['brand_name_val']; ?>" />
                    <input id="brand_search_jt" name="brand_search_jt" type="hidden" value="0" />
                    <script language="javascript">
                    //文本框点击事件
                    function onC_search()
                    {
                        if (document.getElementById("brand_search").value == "请输入……"){
                            document.getElementById("brand_search").value = "";
                        }
                        document.getElementById("brand_content").style.display = "block";
                        $("div[id^='@']>div").css('display','block');
                    }
                    //失去焦点事件
                    function onB_search()
                    {
                        if (document.getElementById("brand_search").value == ""){
                            document.getElementById("brand_search").value = document.getElementById("brand_search_bf").value;
                        }
                        //鼠标离开判断搜索框中名称与选中的名称是否一致，不一致则清空
                        if($("#brand_search").val() != $("#brand_name").val()){
                            if($("#brand_search_bf").val() != ''){
                                $("#brand_search").val($("#brand_search_bf").val());
                                $("#brand_id").val($("#brand_search_jt").val());
                                $("#brand_name").val($("#brand_search_bf").val());
                            }else{
                                $("#brand_search").val("");
                                $("#brand_id").val(0);
                                $("#brand_name").val("");
                            }
                        }
                    }
                    //输入事件
                    function onK_search(w)
                    {
                        if (w != "")
                        {
                            $("div[id^='@']>div").css('display','none');
                            $("div[id^='@']>div[id*='"+w+"']").css('display','block');
                        }
                        else
                            $("div[id^='@']>div").css('display','block');
                    }
                    </script>
                    

                    <div id="brand_content" style="margin-top:5px; margin-bottom:10px; display:none">
                    <div style="float:left; overflow-y:scroll; width:420px; height:120px; border:#CCC 1px solid">
                    <div id="xin_brand" style="display:none">新增品牌：</div>
                    <table width="400" border="0" cellspacing="0" cellpadding="0">
                        <!-- <?php if ($this->_var['brand_list'] != ""): ?> -->
                        <tr>
                          <td style="padding:5px 10px">A-G</td>
                          <td style="padding:5px 10px">H-K</td>
                          <td style="padding:5px 10px">L-S</td>
                          <td style="padding:5px 10px">T-Z</td>
                          <td style="padding:5px 10px">0-9</td>
                        </tr>
                        <tr>
                          <td valign="top" style="padding-left:10px">
                            <!-- <?php $_from = $this->_var['brand_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'brand_list_0_94977000_1520316567');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['brand_list_0_94977000_1520316567']):
?> -->
                            <!-- <?php if ($this->_var['brand_list_0_94977000_1520316567']['name_p'] >= "a" && $this->_var['brand_list_0_94977000_1520316567']['name_p'] <= "g"): ?> -->
                            <div id="@<?php echo $this->_var['key']; ?>"><div id="<?php echo $this->_var['brand_list_0_94977000_1520316567']['name_pinyin']; ?><?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?>"><a href="javascript:go_brand_id(<?php echo $this->_var['key']; ?>,'<?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?>')"><?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?></a></div></div>
                            <!-- <?php endif; ?> -->
                            <!-- <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> -->
                          </td>
                          <td valign="top" style="padding-left:10px"> 
                            <!-- <?php $_from = $this->_var['brand_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'brand_list_0_94977000_1520316567');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['brand_list_0_94977000_1520316567']):
?> -->
                            <!-- <?php if ($this->_var['brand_list_0_94977000_1520316567']['name_p'] >= "h" && $this->_var['brand_list_0_94977000_1520316567']['name_p'] <= "k"): ?> -->
                            <div id="@<?php echo $this->_var['key']; ?>"><div id="<?php echo $this->_var['brand_list_0_94977000_1520316567']['name_pinyin']; ?><?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?>"><a href="javascript:go_brand_id(<?php echo $this->_var['key']; ?>,'<?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?>')"><?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?></a></div></div>
                            <!-- <?php endif; ?> -->
                            <!-- <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> -->
                          </td>
                          <td valign="top" style="padding-left:10px">
                            <!-- <?php $_from = $this->_var['brand_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'brand_list_0_94977000_1520316567');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['brand_list_0_94977000_1520316567']):
?> -->
                            <!-- <?php if ($this->_var['brand_list_0_94977000_1520316567']['name_p'] >= "l" && $this->_var['brand_list_0_94977000_1520316567']['name_p'] <= "s"): ?> -->
                            <div id="@<?php echo $this->_var['key']; ?>"><div id="<?php echo $this->_var['brand_list_0_94977000_1520316567']['name_pinyin']; ?><?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?>"><a href="javascript:go_brand_id(<?php echo $this->_var['key']; ?>,'<?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?>')"><?php echo $this->_var['brand_list_0_94977000_1520316567']['name']; ?></a></div></div>
                            <!-- <?php endif; ?> -->
                            <!-- <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> -->
                          </td>
                          <td valign="top" style="padding-left:10px">
                            <!-- <?php $_from = $this->_var['brand_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'brand_list_0_95077000_1520316567');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['brand_list_0_95077000_1520316567']):
?> -->
                            <!-- <?php if ($this->_var['brand_list_0_95077000_1520316567']['name_p'] >= "t" && $this->_var['brand_list_0_95077000_1520316567']['name_p'] <= "z"): ?> -->
                            <div id="@<?php echo $this->_var['key']; ?>"><div id="<?php echo $this->_var['brand_list_0_95077000_1520316567']['name_pinyin']; ?><?php echo $this->_var['brand_list_0_95077000_1520316567']['name']; ?>"><a href="javascript:go_brand_id(<?php echo $this->_var['key']; ?>,'<?php echo $this->_var['brand_list_0_95077000_1520316567']['name']; ?>')"><?php echo $this->_var['brand_list_0_95077000_1520316567']['name']; ?></a></div></div>
                            <!-- <?php endif; ?> -->
                            <!-- <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> -->
                          </td>
                          <td valign="top" style="padding-left:10px">
                            <!-- <?php $_from = $this->_var['brand_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'brand_list_0_95077000_1520316567');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['brand_list_0_95077000_1520316567']):
?> -->
                            <!-- <?php if ($this->_var['brand_list_0_95077000_1520316567']['name_p'] >= "0" && $this->_var['brand_list_0_95077000_1520316567']['name_p'] <= "9"): ?> -->
                            <div id="@<?php echo $this->_var['key']; ?>"><div id="<?php echo $this->_var['brand_list_0_95077000_1520316567']['name_pinyin']; ?><?php echo $this->_var['brand_list_0_95077000_1520316567']['name']; ?>"><a href="javascript:go_brand_id(<?php echo $this->_var['key']; ?>,'<?php echo $this->_var['brand_list_0_95077000_1520316567']['name']; ?>')"><?php echo $this->_var['brand_list_0_95077000_1520316567']['name']; ?></a></div></div>
                            <!-- <?php endif; ?> -->
                            <!-- <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?> -->
                          </td>
                        </tr>
                        <!-- <?php else: ?> -->
                        <tr>
                          <td colspan="5" align="center">暂无数据……</td>
                        </tr>
                        <!-- <?php endif; ?> -->
                      </table>
                    </div>
                    <div style="padding:106px 0px 0px 426px"><a href="javascript:no_look_brand_content()">收起列表↑</a></div>
                    </div>
                    <!-- <?php if ($this->_var['goods']['goods_sn']): ?> -->
                    <input type="hidden" name="brand_id" id="brand_id" value="<?php echo $this->_var['goods']['brand_id']; ?>" />
                                <input type="hidden" name="brand_name" id="brand_name" value="<?php echo $this->_var['brand_name_val']; ?>" />
                    <!-- <?php else: ?> -->
                    <input type="hidden" name="brand_id" id="brand_id" value="0" />
                                <input type="hidden" name="brand_name" id="brand_name" value="<?php echo $this->_var['brand_name_val']; ?>" />
                    <!-- <?php endif; ?> -->
                    <script language="javascript">
                    function go_brand_id(id,name)
                    {
                        document.getElementById("brand_id").value = id;
                        document.getElementById("brand_search").value = name;
                        document.getElementById("brand_name").value = name;
                        document.getElementById("brand_content").style.display = "none";
                    }
                    function no_look_brand_content()
                    {
                        document.getElementById("brand_content").style.display = "none";
                    }
                    </script>
                </div>
            </div>
            <!--本店售价-->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['lab_shop_price']; ?></label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="shop_price" value="<?php echo $this->_var['goods']['shop_price']; ?>" size="20" onblur="priceSetted()" />
                    <input type="button" value="<?php echo $this->_var['lang']['compute_by_mp']; ?>" onclick="marketPriceSetted()" class="layui-btn layui-btn-normal"/>
                </div>
            </div>
            <!--手机专享价-->
            <div class="layui-form-item" >
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticeWapPrice');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    手机专享价
                </label>
                <div class="layui-input-block">
                    <td><input type="text" class="layui-input" name="exclusive" value="<?php echo $this->_var['goods']['exclusive']; ?>" size="20" />
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticeWapPrice">手机专享价格为-1的时候表示没有手机专享价格</span>
                </div>
            </div>
            <!--会员等级价格-->
            <?php if ($this->_var['user_rank_list']): ?>
            <table class="layui-table" lay-even="" lay-skin="nob">
                <colgroup>
                    <col width="150">
                    <col width="150">
                    <col width="200">
                    <col>
                </colgroup>
                <thead>
                <tr>
                    <th style="width:10%;text-align:right;">
                        <a href="javascript:showNotice2('noticeUserPrice');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                            <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                        </a>
                        <?php echo $this->_var['lang']['lab_user_price']; ?>
                    </th>
                    <th><span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticeUserPrice"><?php echo $this->_var['lang']['notice_user_price']; ?></span></th>
                </tr> 
                </thead>
                <tbody>
                    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'user_rank');if (count($_from)):
    foreach ($_from AS $this->_var['user_rank']):
?>
                    <tr>
                        <td style="width:10%;text-align:right;"><?php echo $this->_var['user_rank']['rank_name']; ?><span id="nrank_<?php echo $this->_var['user_rank']['rank_id']; ?>"></span></td>
                        <td style="padding-left:0px;">
                            <input type="text" class="layui-input" id="rank_<?php echo $this->_var['user_rank']['rank_id']; ?>" name="user_price[]" value="<?php echo empty($this->_var['member_price_list'][$this->_var['user_rank']['rank_id']]) ? '-1' : $this->_var['member_price_list'][$this->_var['user_rank']['rank_id']]; ?>" onkeyup="if(parseInt(this.value)<-1){this.value='-1';};set_price_note(<?php echo $this->_var['user_rank']['rank_id']; ?>)" size="8" />
                            <input type="hidden" name="user_rank[]" value="<?php echo $this->_var['user_rank']['rank_id']; ?>" />
                        </td>
                    </tr>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </tbody>
            </table> 
            <?php endif; ?>
            <!--商品优惠价格-->
            <style type="text/css">
                #tbody-volume td{padding-left:10%;}
            </style>
            <table class="layui-table" lay-even="" lay-skin="nob">
                <thead>
                <tr>
                    <th style="padding-left:48px;">
                        <a href="javascript:showNotice2('volumePrice');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                            <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                        </a>
                        <?php echo $this->_var['lang']['lab_volume_price']; ?><span style="padding-left:30px;" class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="volumePrice"><?php echo $this->_var['lang']['notice_volume_price']; ?></span>
                    </th>
                </tr> 
                </thead>
                <table id="tbody-volume" style="width:100%;">
                    <?php $_from = $this->_var['volume_price_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'volume_price');$this->_foreach['volume_price_tab'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['volume_price_tab']['total'] > 0):
    foreach ($_from AS $this->_var['volume_price']):
        $this->_foreach['volume_price_tab']['iteration']++;
?>
                    <tr>
                        <td>
                            <?php if ($this->_foreach['volume_price_tab']['iteration'] == 1): ?>
                            <a href="javascript:;" onclick="addVolumePrice(this)">[+]</a>
                            <?php else: ?>
                            <a href="javascript:;" onclick="removeVolumePrice(this)">[-]</a>
                            <?php endif; ?> <?php echo $this->_var['lang']['volume_number']; ?>
                            <input type="text" class="layui-input" name="volume_number[]" size="8" value="<?php echo $this->_var['volume_price']['number']; ?>" />
                            <?php echo $this->_var['lang']['volume_price']; ?>
                            <input type="text" class="layui-input" name="volume_price[]" size="8" value="<?php echo $this->_var['volume_price']['price']; ?>" />
                        </td>
                    </tr>
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </table>
            </table> 
            <!--商品优惠价格 end -->
            <!--市场售价-->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['lab_market_price']; ?></label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="market_price" value="<?php echo $this->_var['goods']['market_price']; ?>" size="20" />
                    <input type="button" value="<?php echo $this->_var['lang']['integral_market_price']; ?>" onclick="integral_market_price()" class="layui-btn layui-btn-normal"/>
                </div>
            </div>
            <!--赠送消费积分数-->
            <div class="layui-form-item" style="display:none;">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('giveIntegral');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_give_integral']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="give_integral" value="<?php echo $this->_var['goods']['give_integral']; ?>" size="20" />
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="giveIntegral"><?php echo $this->_var['lang']['notice_give_integral']; ?></span>
                </div>
            </div>
            <!--赠送等级积分数-->
            <div class="layui-form-item" style="display:none;">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('rankIntegral');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_rank_integral']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="rank_integral" value="<?php echo $this->_var['goods']['rank_integral']; ?>" size="20" />
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="rankIntegral"><?php echo $this->_var['lang']['notice_rank_integral']; ?></span>
                </div>
            </div>
            <!--积分购买金额-->
            <div class="layui-form-item" style="display:none;">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticPoints');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_integral']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="integral" value="<?php echo $this->_var['goods']['integral']; ?>" size="20" onblur="parseint_integral()" ;/>
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticPoints"><?php echo $this->_var['lang']['notice_integral']; ?></span>
                </div>
            </div>
            <!--促销价-->
            <div class="layui-form-item" id="promote_box">
                <label class="layui-form-label">
                    <?php echo $this->_var['lang']['lab_promote_price']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" id="promote_1" name="promote_price" value="<?php echo $this->_var['goods']['promote_price']; ?>" size="20" />
                    <input type="checkbox" id="is_promote" name="is_promote" value="1" <?php if ($this->_var['goods']['is_promote']): ?>checked="checked" <?php endif; ?> lay-skin="switch" lay-filter="is_promote" lay-text="开启|关闭" />
                </div>
            </div>
            <!--促销商品时间精确到时分-->
            <div class="layui-form-item" id="promote_box">
                <label class="layui-form-label">
                    <?php echo $this->_var['lang']['lab_promote_date']; ?>
                </label>
                <div class="layui-input-block">
                    <input name="promote_start_date" type="text" id="promote_start_date" class="layui-input" value='<?php echo $this->_var['goods']['promote_start_date']; ?>' readonly="readonly" onfocus="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})" /> - 
                    <input name="promote_end_date" type="text" id="promote_end_date" class="layui-input" value='<?php echo $this->_var['goods']['promote_end_date']; ?>' readonly="readonly" onfocus="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})" />
                </div>
            </div>
            <!--限购数量-->
            <div class="layui-form-item" id="promote_box">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticBuymax');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    限购数量
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" id="buymax" name="buymax" value="<?php echo $this->_var['goods']['buymax']; ?>" size="20" <?php if ($this->_var['goods']['is_buy'] == 0): ?> disabled="disabled" <?php endif; ?>/>
                    <input type="checkbox" id="is_buy" name="is_buy" value="1" <?php if ($this->_var['goods']['is_buy']): ?>checked="checked" <?php endif; ?> lay-skin="switch" lay-filter="is_buy" lay-text="开启|关闭" />
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticBuymax">表示限购日期内，每个用户最多只能购买多少件。0：表示不限购</span>
                </div>
            </div>
            <!--限购日期-->
            <div class="layui-form-item" id="promote_box">
                <label class="layui-form-label">
                    限购日期
                </label>
                <div class="layui-input-block">
                    <input name="buymax_start_date" type="text" id="buymax_start_date" class="layui-input" value='<?php echo $this->_var['goods']['buymax_start_date']; ?>' readonly="readonly" onfocus="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})" /> - 
                    <input name="buymax_end_date" type="text" id="buymax_end_date" class="layui-input" value='<?php echo $this->_var['goods']['buymax_end_date']; ?>' readonly="readonly" onfocus="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})" />
                </div>
            </div>
            <!--分成金额-->
            <div class="layui-form-item" style="display:none;">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticCostPrice');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_cost_price']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" id="cost_price" name="cost_price" value="<?php echo $this->_var['goods']['cost_price']; ?>" size="20" />
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticCostPrice"><?php echo $this->_var['lang']['notice_cost_price']; ?></span>
                </div>
            </div>
            <!--上传商品图片-->
            <div class="layui-form-item">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticPicture');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_picture']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" size="40" value="<?php echo $this->_var['lang']['lab_picture_url']; ?>" style="color:#aaa;" onfocus="if (this.value == '<?php echo $this->_var['lang']['lab_picture_url']; ?>'){this.value='http://';this.style.color='#000';}" name="goods_img_url" />
                    <input type="file" name="goods_img"/>
                    <?php if ($this->_var['goods']['goods_img']): ?>
                        <a href="goods.php?act=show_image&img_url=<?php echo $this->_var['goods']['goods_img']; ?>" target="_blank">
                            <img src="images/yes.gif" border="0" />
                        </a>
                    <?php else: ?>
                        <img src="images/no.gif" />
                    <?php endif; ?>
                    <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticPicture">图片尺寸比例为1:1（正方形），可以填写外部图片链接，也可以自己上传图片显示。</span>
                    <?php if ($this->_var['goods']['goods_img']): ?>
                        <br/><br/><a href="goods.php?act=show_image&img_url=<?php echo $this->_var['goods']['goods_img']; ?>" target="_blank">
                            <img src="../<?php echo $this->_var['goods']['goods_img']; ?>" border="0" style="max-height:100px;margin-left:10px;" />
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <!--上传商品缩略图-->
            <div class="layui-form-item">
                <label class="layui-form-label">
                    <?php echo $this->_var['lang']['lab_thumb']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" size="40" value="<?php echo $this->_var['lang']['lab_thumb_url']; ?>" style="color:#aaa;" onfocus="if (this.value == '<?php echo $this->_var['lang']['lab_thumb_url']; ?>'){this.value='http://';this.style.color='#000';}" name="goods_thumb_url" />
                    <input type="file" name="goods_thumb"/>
                    <?php if ($this->_var['goods']['goods_thumb']): ?>
                        <a href="goods.php?act=show_image&img_url=<?php echo $this->_var['goods']['goods_thumb']; ?>" target="_blank">
                            <img src="images/yes.gif" border="0" />
                        </a>
                    <?php else: ?>
                        <img src="images/no.gif" />
                    <?php endif; ?>
                    <input type="checkbox" id="auto_thumb" name="auto_thumb" checked="true" value="1" lay-skin="switch" lay-filter="auto_thumb" lay-text="自动|关闭" />
                    <span class="notice-span"><?php echo $this->_var['lang']['auto_thumb']; ?></span>
                    <?php if ($this->_var['goods']['goods_thumb']): ?>
                        <br/><br/><a href="goods.php?act=show_image&img_url=<?php echo $this->_var['goods']['goods_thumb']; ?>" target="_blank">
                            <img src="../<?php echo $this->_var['goods']['goods_img']; ?>" border="0" style="max-height:100px;margin-left:10px;" />
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <!--商家商品审核-->
            <div class="layui-form-item">
                <label class="layui-form-label">审核消息</label>
                <div class="layui-input-block">
                    <input class="layui-input" type="text" placeholder="只能查看审核消息，不能输入内容" autocomplete="off" name="supplier_status_txt" value="<?php echo $this->_var['goods']['supplier_status_txt']; ?>" style="width:80%;" disabled="disabled">
                </div>
            </div>
        </div>

        <!-- 详细描述 -->
        <div class="layui-tab-item">
            <table width="90%" id="detail-table">
              <tr>
                <td><?php echo $this->_var['FCKeditor']; ?></td>
              </tr>
            </table>
        </div>

        <!-- 其他信息 -->
        <div class="layui-tab-item">
            <!-- 商品重量 -->
            <?php if ($this->_var['code'] == ''): ?>
            <style type="text/css">
                #weight_box .layui-input{float: left;}
                #weight_box .layui-unselect{width:100px;float: left;min-width:100px !important;}
            </style>
            <div class="layui-form-item" id="weight_box">
                <label class="layui-form-label"><?php echo $this->_var['lang']['lab_goods_weight']; ?></label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="goods_weight" value="<?php echo $this->_var['goods']['goods_weight_by_unit']; ?>" size="20" />
                    <select name="weight_unit"> <?php echo $this->html_options(array('options'=>$this->_var['unit_list'],'selected'=>$this->_var['weight_unit'])); ?></select>
                </div>
            </div>
            <?php endif; ?>
            <!-- 库存 -->
            <?php if ($this->_var['cfg']['use_storage']): ?>
                <div class="layui-form-item">
                    <label class="layui-form-label">
                        <a href="javascript:showNotice2('noticeStorage');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                            <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                        </a>
                        <?php echo $this->_var['lang']['lab_goods_number']; ?>
                    </label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="goods_number" value="<?php echo $this->_var['goods']['goods_number']; ?>" size="20" <?php if ($this->_var['goods']['goods_type'] != 0): ?> style="color: #666" readonly <?php endif; ?> />
                        <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticeStorage"><?php echo $this->_var['lang']['notice_storage']; ?></span>
                    </div>
                </div>
                <div class="layui-form-item" >
                    <label class="layui-form-label"><?php echo $this->_var['lang']['lab_warn_number']; ?></label>
                    <div class="layui-input-block">
                        <input type="text" class="layui-input" name="warn_number" value="<?php echo $this->_var['goods']['warn_number']; ?>" size="20" />
                    </div>
                </div>
            <?php endif; ?>
            <!-- 加入推荐 -->
            <div class="layui-form-item">
                <label class="layui-form-label">
                    <?php echo $this->_var['lang']['lab_intro']; ?>
                </label>
                <div class="layui-input-block">
                    <input type="checkbox" name="is_best" value="1" <?php if ($this->_var['goods']['is_best']): ?>checked="checked" <?php endif; ?> title="<?php echo $this->_var['lang']['is_best']; ?>" />                   
                    <input type="checkbox" name="is_new" value="1" <?php if ($this->_var['goods']['is_new']): ?>checked="checked" <?php endif; ?> title="<?php echo $this->_var['lang']['is_new']; ?>" />                  
                    <input type="checkbox" name="is_hot" value="1" <?php if ($this->_var['goods']['is_hot']): ?>checked="checked" <?php endif; ?> title="<?php echo $this->_var['lang']['is_hot']; ?>" />
                </div>
            </div>
            <!-- 上架 -->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['lab_is_on_sale']; ?></label>
                <div class="layui-input-block">
                    <input type="checkbox" name="is_on_sale" value="1" <?php if ($this->_var['goods']['is_on_sale']): ?>checked="checked" <?php endif; ?> title="<?php echo $this->_var['lang']['on_sale_desc']; ?>" />
                </div>
            </div>
            <!-- 能作为普通商品销售 -->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['lab_is_alone_sale']; ?></label>
                <div class="layui-input-block">
                    <input type="checkbox" name="is_alone_sale" value="1" <?php if ($this->_var['goods']['is_alone_sale']): ?>checked="checked" <?php endif; ?> title="<?php echo $this->_var['lang']['alone_sale']; ?>" />
                </div>
            </div>
            <!-- 是否为免运费商品 -->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['lab_is_free_shipping']; ?></label>
                <div class="layui-input-block">
                    <input type="checkbox" name="is_shipping" value="1" <?php if ($this->_var['goods']['is_shipping']): ?>checked="checked" <?php endif; ?> title="<?php echo $this->_var['lang']['free_shipping']; ?>" />
                </div>
            </div>
            <!-- 商品关键词 -->
            <div class="layui-form-item">
                <label class="layui-form-label"><?php echo $this->_var['lang']['lab_keywords']; ?></label>
                <div class="layui-input-block">
                    <input type="text" class="layui-input" name="keywords" value="<?php echo htmlspecialchars($this->_var['goods']['keywords']); ?>" size="40" />
                    <span class="notice-span"><?php echo $this->_var['lang']['notice_keywords']; ?></span>
                </div>
            </div>
            <!-- 商品简单描述 -->
            <style type="text/css">
                #goods_textarea_box label{float: left;}
                #goods_textarea_box .layui-input-initial{width: 60%;float: left;}
            </style>
            <div class="layui-form-item layui-form-text" id="goods_textarea_box">
                <label class="layui-form-label" style="float:left;"><?php echo $this->_var['lang']['lab_goods_brief']; ?></label>
                <div class="layui-input-initial">
                  <textarea class="layui-textarea" name="goods_brief" id="goods_brief"><?php echo htmlspecialchars($this->_var['goods']['goods_brief']); ?></textarea>
                </div>
            </div>
            <!-- 商家备注 -->
            <div class="layui-form-item layui-form-text" id="goods_textarea_box">
                <label class="layui-form-label" style="float:left;">
                    <a href="javascript:showNotice2('noticeSellerNote');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_seller_note']; ?>
                </label>
                <div class="layui-input-initial">
                    <textarea class="layui-textarea" name="seller_note" id="seller_note"><?php echo htmlspecialchars($this->_var['goods']['seller_note']); ?></textarea><span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticeSellerNote"><?php echo $this->_var['lang']['notice_seller_note']; ?></span>
                </div>
            </div>
        </div>

        <!-- 属性与规格 -->
        <?php if ($this->_var['goods_type_list']): ?>
        <div class="layui-tab-item">
            <div class="layui-form-item">
                <label class="layui-form-label">
                    <a href="javascript:showNotice2('noticeGoodsType');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                        <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                    </a>
                    <?php echo $this->_var['lang']['lab_goods_type']; ?>
                </label>
                <div class="layui-input-block">
                    <select name="goods_type" lay-filter="sel_goods_type">
                        <option value="0"><?php echo $this->_var['lang']['sel_goods_type']; ?></option>
                        <?php echo $this->_var['goods_type_list']; ?>
                    </select>
                    <span style="margin-left:10px;line-height:35px;" class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="noticeGoodsType"><?php echo $this->_var['lang']['notice_goods_type']; ?></span>
                </div>
                <div class="layui-input-block" id="tbody-goodsAttr" style="margin-left:0px !important;">
                    <?php echo $this->_var['goods_attr_html']; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <script type="text/javascript">
            layui.use('form', function(){
                var form = layui.form(),layer = layui.layer,layedit = layui.layedit,laydate = layui.laydate;
                form.render(); //更新全部
                //监听指定开关
                form.on('switch(is_promote)', function(data){//促销价
                    handlePromote(this.checked);
                });
                form.on('switch(is_buy)', function(data){//限购数量
                    handleBuy(this.checked);
                });
                form.on('switch(auto_thumb)', function(data){//上传商品缩略图
                    handleAutoThumb(this.checked);
                });
                form.on('select(sel_goods_type)', function(data){
                    getAttrList(<?php echo $this->_var['goods']['goods_id']; ?>);
                });      
            });
        </script>

        <div class="layui-tab-item">
            <!--  将 商品相册 这部分代码完全修改成下面这样-->
            <table width="100%" id="gallery-table" align="center">
                <!-- 图片列表 -->
                <tr>
                    <td>
                        <style>
                            .attr-color-div {
                                width: 100%;
                                background: #BBDDE5;
                                text-indent: 10px;
                                height: 26px;
                                padding-top: 1px;
                            }

                            .attr-front {
                                background: #CCFF99;
                                line-height: 24px;
                                font-weight: bold;
                                padding: 6px 15px 6px 18px;
                            }

                            .attr-back {
                                color: #FF0000;
                                font-weight: bold;
                                line-height: 24px;
                                padding: 6px 15px 6px 18px;
                                border-right: 1px solid #FFF;
                            }
                        </style>
                        <?php
                        $sql_hunuo_com="SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value FROM ". $GLOBALS['ecs']->table('attribute') . " AS a left join ". $GLOBALS['ecs']->table('goods_attr') . "  AS ga on a.attr_id=ga.attr_id  WHERE a.is_attr_gallery=1 and ga.goods_id='" . $GLOBALS['smarty']->_var['goods']['goods_id']. "' order by ga.goods_attr_id ";
                        $color_list_hunuo_com=$GLOBALS['db']->getAll($sql_hunuo_com);
                        $color_count_df67sd6h8as5fc63xcq892jkb_hunuo_com=count($color_list_hunuo_com);
                        $color_list_hunuo_com[$color_count_df67sd6h8as5fc63xcq892jkb_hunuo_com]=array('attr_id'=>0, 'attr_value'=>'通用');
                        $GLOBALS['smarty']->assign('color_list_hunuo_com', $color_list_hunuo_com);
                        $GLOBALS['smarty']->assign('color_count_df67sd6h8as5fc63xcq892jkb_hunuo_com', $color_count_df67sd6h8as5fc63xcq892jkb_hunuo_com+1);
                        ?>
                        <script>
                            
                            function changeCurrentColor(n)
                            {
                            for(i=1;i<=<?php echo $this->_var['color_count_df67sd6h8as5fc63xcq892jkb_hunuo_com']; ?>;i++)
                            {
                                document.getElementById("color_" + i).className = "attr-back";
                            }
                            document.getElementById("color_" + n).className = "attr-front";
                            }
                            
                        </script>
                        <font color=#ff3300>请选择商品颜色</font>
                        （点击下面不同颜色切换到该颜色对应的相册）
                        <br>
                        <br>
                        <div class="attr-color-div">
                            <?php $_from = $this->_var['color_list_hunuo_com']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'color_qq');$this->_foreach['color_list'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['color_list']['total'] > 0):
    foreach ($_from AS $this->_var['color_qq']):
        $this->_foreach['color_list']['iteration']++;
?>
                            <span class="<?php if ($this->_foreach['color_list']['iteration'] == 1): ?>attr-front<?php else: ?>attr-back<?php endif; ?>" id="color_<?php echo $this->_foreach['color_list']['iteration']; ?>">
                                <a href="attr_img_upload.php?goods_id=<?php echo $this->_var['goods']['goods_id']; ?>&goods_attr_id=<?php echo $this->_var['color_qq']['goods_attr_id']; ?>" onclick="javascript:changeCurrentColor(<?php echo $this->_foreach['color_list']['iteration']; ?>)" target="attr_upload"><?php echo $this->_var['color_qq']['attr_value']; ?></a>
                            </span>
                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                        </div>
                        <?php if ($this->_var['goods']['goods_id']): ?>
                        <iframe name="attr_upload" src="attr_img_upload.php?goods_id=<?php echo $this->_var['goods']['goods_id']; ?>&goods_attr_id=<?php echo $this->_var['color_list_hunuo_com']['0']['goods_attr_id']; ?>" frameborder=1 scrolling=no width="100%" height="auto" style="min-height:630px; border:1px #eee solid; margin-top:5px;"> </iframe>
                        <?php else: ?>
                        <p align="center">请选保存新商品，再上传商品相册图片。</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </div>

        <!-- 关联商品 -->
        <style type="text/css">
            #linkgoods-table .layui-form-select{margin-right: 10px;}
            #linkgoods-table .linkgoods_box select,#linkgoods-table .linkgoods_box input{display: inline;}
        </style>
        <div class="layui-tab-item">
            <table width="81%" id="linkgoods-table" align="center">
                <!-- 商品搜索 -->
                <tr>
                    <td colspan="3">
                        <img src="images/icon_search.gif" width="26" height="22" border="0" alt="SEARCH" style="float:left;margin:8px;" />
                        <select name="cat_id1">
                            <option value="0"><?php echo $this->_var['lang']['all_category']; ?></option>
                            <?php echo $this->_var['cat_list']; ?>
                        </select>
                        <div style="display:none;">
                        <select name="brand_id1">
                            <option value="0"><?php echo $this->_var['lang']['all_brand']; ?></option>
                            <?php echo $this->html_options(array('options'=>$this->_var['brand_list'])); ?>
                        </select>
                        </div>
                        <input type="text" name="keyword1" placeholder="请输入关键字" class="layui-input"/>
                        <input type="button" value="<?php echo $this->_var['lang']['button_search']; ?>" class="layui-btn" onclick="searchGoods(sz1, 'cat_id1','brand_id1','keyword1')" />
                    </td>
                </tr>
                <!-- 商品列表 -->
                <tr>
                    <th align="center"><?php echo $this->_var['lang']['all_goods']; ?></th>
                    <th align="center"><?php echo $this->_var['lang']['handler']; ?></th>
                    <th align="center"><?php echo $this->_var['lang']['link_goods']; ?></th>
                </tr>
                <tr class="linkgoods_box">
                    <td width="42%">
                        <select name="source_select1" size="20" style="width:100%" ondblclick="sz1.addItem(false, 'add_link_goods', goodsId, this.form.elements['is_single'][0].checked)" multiple="true">
                        </select>
                    </td>
                    <td align="center">
                        <p>
                            <input name="is_single" type="radio" value="1" checked="checked" />
                            <?php echo $this->_var['lang']['single']; ?>
                            <br />
                            <input name="is_single" type="radio" value="0" />
                            <?php echo $this->_var['lang']['double']; ?>
                        </p>
                        <p>
                            <input type="button" value=">>" onclick="sz1.addItem(true, 'add_link_goods', goodsId, this.form.elements['is_single'][0].checked)" class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                        <p>
                            <input type="button" value=">" onclick="sz1.addItem(false, 'add_link_goods', goodsId, this.form.elements['is_single'][0].checked)" class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                        <p>
                            <input type="button" value="<" onclick="sz1.dropItem(false, 'drop_link_goods', goodsId, elements['is_single'][0].checked)" class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                        <p>
                            <input type="button" value="<<" onclick="sz1.dropItem(true, 'drop_link_goods', goodsId, elements['is_single'][0].checked)" class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                    </td>
                    <td width="42%">
                        <select name="target_select1" size="20" style="width:100%" multiple ondblclick="sz1.dropItem(false, 'drop_link_goods', goodsId, elements['is_single'][0].checked)">

                            <?php $_from = $this->_var['link_goods_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'link_goods');if (count($_from)):
    foreach ($_from AS $this->_var['link_goods']):
?>

                            <option value="<?php echo $this->_var['link_goods']['goods_id']; ?>"><?php echo $this->_var['link_goods']['goods_name']; ?></option>

                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>

                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 配件 -->
        <style type="text/css">
            #groupgoods-table .layui-form-select{margin-right: 10px;}
            #groupgoods-table select{display: inline;}
        </style>
        <div class="layui-tab-item">
            <table width="81%" id="groupgoods-table" align="center">
                <!-- 商品搜索 -->
                <tr>
                    <td colspan="3">
                        <img src="images/icon_search.gif" width="26" height="22" border="0" alt="SEARCH" />
                        <select name="cat_id2" class="layui-input">
                            <option value="0"><?php echo $this->_var['lang']['all_category']; ?></option>
                            <?php echo $this->_var['cat_list']; ?>
                        </select>
                        <div style="display:none;">
                        <select name="brand_id2" class="layui-input">
                            <option value="0"><?php echo $this->_var['lang']['all_brand']; ?></option>
                            <?php echo $this->html_options(array('options'=>$this->_var['brand_list'])); ?>
                        </select>
                        </div>
                        <input type="text" name="keyword2" placeholder="请输入关键字" class="layui-input" />
                        <input type="button" value="<?php echo $this->_var['lang']['button_search']; ?>" onclick="searchGoods(sz2, 'cat_id2', 'brand_id2', 'keyword2')" class="layui-btn" />
                    </td>
                </tr>
                <!-- 商品列表 -->
                <tr>
                    <th align="center"><?php echo $this->_var['lang']['all_goods']; ?></th>
                    <th align="center"><?php echo $this->_var['lang']['handler']; ?></th>
                    <th align="center"><?php echo $this->_var['lang']['group_goods']; ?></th>
                </tr>
                <tr class="groupgoods_box">
                    <td width="42%">
                        <select name="source_select2" size="20" style="width:100%" onchange="sz2.priceObj.value = this.options[this.selectedIndex].id" ondblclick="sz2.addItem(false, 'add_group_goods', goodsId, this.form.elements['price2'].value)">
                        </select>
                    </td>
                    <td align="center">
                        <p>
                            <?php echo $this->_var['lang']['price']; ?>
                            <br />
                            <input onkeyup="this.value=this.value.replace(/[^0-9.]/g,'')" name="price2" type="text" size="6" style="min-width:40px;width:40px;"/>
                        </p>
                        <p>
                            <input type="button" value=">" onclick="sz2.addItem(false, 'add_group_goods', goodsId, this.form.elements['price2'].value)"  class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                        <p>
                            <input type="button" value="<" onclick="sz2.dropItem(false, 'drop_group_goods', goodsId, elements['is_single'][0].checked)"  class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                        <p>
                            <input type="button" value="<<" onclick="sz2.dropItem(true, 'drop_group_goods', goodsId, elements['is_single'][0].checked)"  class="layui-btn layui-btn-small layui-btn-normal" />
                        </p>
                    </td>
                    <td width="42%">
                        <select name="target_select2" size="20" style="width:100%" multiple ondblclick="sz2.dropItem(false, 'drop_group_goods', goodsId, elements['is_single'][0].checked)">

                            <?php $_from = $this->_var['group_goods_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'group_goods');if (count($_from)):
    foreach ($_from AS $this->_var['group_goods']):
?>

                            <option value="<?php echo $this->_var['group_goods']['goods_id']; ?>"><?php echo $this->_var['group_goods']['goods_name']; ?></option>

                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>

                        </select>
                    </td>
                </tr>
            </table>
        </div>
        

        <div class="layui-tab-item">
            <!-- 鍏宠仈鏂囩珷 -->
            <table width="100%" id="article-table" align="center">
                <!-- 鏂囩珷鎼滅储 -->
                <tr>
                    <td colspan="3">
                        <img src="images/icon_search.gif" width="26" height="22" border="0" alt="SEARCH" />
                        <?php echo $this->_var['lang']['article_title']; ?>
                        <input type="text" name="article_title" />
                        <input type="button" value="<?php echo $this->_var['lang']['button_search']; ?>" onclick="searchArticle()" class="button" />
                    </td>
                </tr>
                <!-- 鏂囩珷鍒楄〃 -->
                <tr>
                    <th><?php echo $this->_var['lang']['all_article']; ?></th>
                    <th><?php echo $this->_var['lang']['handler']; ?></th>
                    <th><?php echo $this->_var['lang']['goods_article']; ?></th>
                </tr>
                <tr>
                    <td width="45%">
                        <select name="source_select3" size="20" style="width:100%" multiple ondblclick="sz3.addItem(false, 'add_goods_article', goodsId, this.form.elements['price2'].value)">
                        </select>
                    </td>
                    <td align="center">
                        <p>
                            <input type="button" value=">>" onclick="sz3.addItem(true, 'add_goods_article', goodsId, this.form.elements['price2'].value)" class="button" />
                        </p>
                        <p>
                            <input type="button" value=">" onclick="sz3.addItem(false, 'add_goods_article', goodsId, this.form.elements['price2'].value)" class="button" />
                        </p>
                        <p>
                            <input type="button" value="<" onclick="sz3.dropItem(false, 'drop_goods_article', goodsId, elements['is_single'][0].checked)" class="button" />
                        </p>
                        <p>
                            <input type="button" value="<<" onclick="sz3.dropItem(true, 'drop_goods_article', goodsId, elements['is_single'][0].checked)" class="button" />
                        </p>
                    </td>
                    <td width="45%">
                        <select name="target_select3" size="20" style="width:100%" multiple ondblclick="sz3.dropItem(false, 'drop_goods_article', goodsId, elements['is_single'][0].checked)">

                            <?php $_from = $this->_var['goods_article_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'goods_article');if (count($_from)):
    foreach ($_from AS $this->_var['goods_article']):
?>

                            <option value="<?php echo $this->_var['goods_article']['article_id']; ?>"><?php echo $this->_var['goods_article']['title']; ?></option>

                            <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>

                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <div class="button-div" style="margin-top:15px;">
            <input type="hidden" name="goods_id" value="<?php echo $this->_var['goods']['goods_id']; ?>" />
            <?php if ($this->_var['code'] != ''): ?>
            <input type="hidden" name="extension_code" value="<?php echo $this->_var['code']; ?>" />
            <?php endif; ?>
            <?php if ($this->_var['goods']['supplier_status'] == '-1' && ! $this->_var['is_secondadd']): ?>
            <input type="button" style="color:#ff3300;font-weight:bold;" value="审核未通过商品，不允许再次提交！" class="layui-btn"  />
            <?php elseif ($this->_var['goods']['supplier_status'] == '1' && ! $this->_var['is_editgoods']): ?>
            <input type="button" style="color:#ff3300;font-weight:bold;" value="已经审核通过的商品，不允许再次修改！" class="layui-btn"  />
            <?php else: ?>
            <input type="button" id="goods_info_submit" value="<?php echo $this->_var['lang']['button_submit']; ?>" class="layui-btn" onclick="validate('<?php echo $this->_var['goods']['goods_id']; ?>')" />
            <?php endif; ?>
            <input id="goods_info_reset" type="reset" value="<?php echo $this->_var['lang']['button_reset']; ?>" class="layui-btn" />
            </div>
            <input type="hidden" name="act" value="<?php echo $this->_var['form_act']; ?>" />
            <input type="hidden" name="supplier_status" value="<?php echo $this->_var['goods']['supplier_status']; ?>" />
        </div>
    </div>
</div>
</form>

<?php echo $this->smarty_insert_scripts(array('files'=>'validator.js,tab.js')); ?>

<script language="JavaScript">
  var goodsId = '<?php echo $this->_var['goods']['goods_id']; ?>';
  var elements = document.forms['theForm'].elements;
  var sz1 = new SelectZone(1, elements['source_select1'], elements['target_select1']);
  var sz2 = new SelectZone(2, elements['source_select2'], elements['target_select2'], elements['price2']);
  var sz3 = new SelectZone(1, elements['source_select3'], elements['target_select3']);
  var marketPriceRate = <?php echo empty($this->_var['cfg']['market_price_rate']) ? '1' : $this->_var['cfg']['market_price_rate']; ?>;
  var integralPercent = <?php echo empty($this->_var['cfg']['integral_percent']) ? '0' : $this->_var['cfg']['integral_percent']; ?>;

  
  onload = function()
  {
      handlePromote(document.forms['theForm'].elements['is_promote'].checked);
      handleBuy(document.forms['theForm'].elements['is_buy'].checked);

      if (document.forms['theForm'].elements['auto_thumb'])
      {
          handleAutoThumb(document.forms['theForm'].elements['auto_thumb'].checked);
      }

      // 妫€鏌ユ柊璁㈠崟
      startCheckOrder();
      
      <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
      set_price_note(<?php echo $this->_var['item']['rank_id']; ?>);
      <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
      
      document.forms['theForm'].reset();
  }

  /**
*获取类名相同的成员
*/
function getElementsByClassName(n)
{
   var classElements = [],allElements = document.getElementsByTagName('*');
   for (var i=0; i< allElements.length; i++ )
   {
     if (allElements[i].className == n ) {
          classElements[classElements.length] = allElements[i];
     }
    }
   return classElements;
}

  function validate(goods_id)
  {
      var validator = new Validator('theForm');
      var goods_sn  = document.forms['theForm'].elements['goods_sn'].value;
	  var cat_id = document.getElementById('cat_id').value;
		var cat_name= document.getElementById('cat_name').value;
      validator.required('goods_name', goods_name_not_null);
      if (cat_name==''||cat_id == '')
      {
          validator.addErrorMsg(goods_cat_not_null);
      }

	  var objects = getElementsByClassName('nfl');
	  validator.requiredCheckbox(objects, '店内分类不能为空！'); //验证店内分类

      checkVolumeData("1",validator);

      validator.required('shop_price', shop_price_not_null);
      validator.isNumber('shop_price', shop_price_not_number, true);
      validator.isNumber('market_price', market_price_not_number, false);
      if (document.forms['theForm'].elements['is_promote'].checked)
      {
          validator.required('promote_start_date', promote_start_not_null);
          validator.required('promote_end_date', promote_end_not_null);
          validator.islt('promote_start_date', 'promote_end_date', promote_not_lt);
      }

      if (document.forms['theForm'].elements['goods_number'] != undefined)
      {
          validator.isInt('goods_number', goods_number_not_int, false);
          validator.isInt('warn_number', warn_number_not_int, false);
      }

      var callback = function(res)
     {
      if (res.error > 0)
      {
        alert("<?php echo $this->_var['lang']['goods_sn_exists']; ?>");
      }
      else
      {
         if(validator.passed())
         {
         document.forms['theForm'].submit();
         }
      }
  }
    Ajax.call('goods.php?is_ajax=1&act=check_goods_sn', "goods_sn=" + goods_sn + "&goods_id=" + goods_id, callback, "GET", "JSON");
}

  /**
   * 鍒囨崲鍟嗗搧绫诲瀷
   */
  function getAttrList(goodsId)
  {
      var selGoodsType = document.forms['theForm'].elements['goods_type'];

      if (selGoodsType != undefined)
      {
          var goodsType = selGoodsType.options[selGoodsType.selectedIndex].value;

          Ajax.call('goods.php?is_ajax=1&act=get_attr_layui', 'goods_id=' + goodsId + "&goods_type=" + goodsType, setAttrList, "GET", "JSON");
      }
  }
function array_search_value(arrayinfo,value){
	for(i in arrayinfo){
		if(arrayinfo[i] == value){
			return false;
		}
	}
	return true;
}

  /*
   *
   *702460594
   *
   *条形码选择传值
   */

function getType(txm,id,value,good_id)
{
	
	var txm = txm;
	var cid = id;//所选属性的上级ID
	var val = value;//选中的值
	var goodid = good_id;//商品id
	var parm = new Array();
	var j = 0;
	$('.ctxm_'+txm).each(function(k,v){
	
		if(array_search_value(parm,v.value)){
			parm[j] = v.value;
			j++;
		}
	})
	
	var par_str = '';
	var parm_key = '';
	var parm_value = '';
	for(i in parm){
		parm_key = '&attr_'+parm[i]+'='; 
		parm_value = '';
		$('.attr_num_'+parm[i]).each(function(key,value){
			if(value.value !=''){
				parm_value += value.value+',';
			}
		})
		par_str += parm_key+parm_value;
	}
	
	Ajax.call('goods.php?is_ajax=1&act=get_txm', 'goods_id=' + goodid + "&id=" + id + par_str , chu, "GET", "JSON");
	
	return;
}
/*
 *
 *702460594
 *
 *
 *条形码数据返回
 */
function chu (result)
{
	var opanel = document.getElementById("input_txm");
	var zhi = result.content;
	opanel.innerHTML = zhi;
}
  function setAttrList(result, text_result)
  {
    document.getElementById('tbody-goodsAttr').innerHTML = result.content;
    var form = layui.form();form.render(); //刷新select选择框渲染
  }

  /**
   * 鎸夋瘮渚嬭?绠椾环鏍
   * @param   string  inputName   杈撳叆妗嗗悕绉
   * @param   float   rate        姣斾緥
   * @param   string  priceName   浠锋牸杈撳叆妗嗗悕绉帮紙濡傛灉娌℃湁锛屽彇shop_price锛
   */
  function computePrice(inputName, rate, priceName)
  {
      var shopPrice = priceName == undefined ? document.forms['theForm'].elements['shop_price'].value : document.forms['theForm'].elements[priceName].value;
      shopPrice = Utils.trim(shopPrice) != '' ? parseFloat(shopPrice)* rate : 0;
      if(inputName == 'integral')
      {
          shopPrice = parseInt(shopPrice);
      }
      shopPrice += "";

      n = shopPrice.lastIndexOf(".");
      if (n > -1)
      {
          shopPrice = shopPrice.substr(0, n + 3);
      }

      if (document.forms['theForm'].elements[inputName] != undefined)
      {
          document.forms['theForm'].elements[inputName].value = shopPrice;
      }
      else
      {
          document.getElementById(inputName).value = shopPrice;
      }
  }

  /**
   * 璁剧疆浜嗕竴涓?晢鍝佷环鏍硷紝鏀瑰彉甯傚満浠锋牸銆佺Н鍒嗕互鍙婁細鍛樹环鏍
   */
  function priceSetted()
  {
    computePrice('market_price', marketPriceRate);
    computePrice('integral', integralPercent / 100);
    
    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    set_price_note(<?php echo $this->_var['item']['rank_id']; ?>);
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    
  }

  /**
   * 璁剧疆浼氬憳浠锋牸娉ㄩ噴
   */
  function set_price_note(rank_id)
  {
    var shop_price = parseFloat(document.forms['theForm'].elements['shop_price'].value);

    var rank = new Array();
    
    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    rank[<?php echo $this->_var['item']['rank_id']; ?>] = <?php echo empty($this->_var['item']['discount']) ? '100' : $this->_var['item']['discount']; ?>;
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    
    if (shop_price >0 && rank[rank_id] && document.getElementById('rank_' + rank_id) && parseInt(document.getElementById('rank_' + rank_id).value) == -1)
    {
      var price = parseInt(shop_price * rank[rank_id] + 0.5) / 100;
      if (document.getElementById('nrank_' + rank_id))
      {
        document.getElementById('nrank_' + rank_id).innerHTML = '(' + price + ')';
      }
    }
    else
    {
      if (document.getElementById('nrank_' + rank_id))
      {
        document.getElementById('nrank_' + rank_id).innerHTML = '';
      }
    }
  }

  /**
   * 鏍规嵁甯傚満浠锋牸锛岃?绠楀苟鏀瑰彉鍟嗗簵浠锋牸銆佺Н鍒嗕互鍙婁細鍛樹环鏍
   */
  function marketPriceSetted()
  {
    computePrice('shop_price', 1/marketPriceRate, 'market_price');
    computePrice('integral', integralPercent / 100);
    
    <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
    set_price_note(<?php echo $this->_var['item']['rank_id']; ?>);
    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
    
  }

  /**
   * 鏂板?涓€涓??鏍
   */
  function addSpec(obj)
  {
      var src   = obj.parentNode.parentNode;
      var idx   = rowindex(src);
      var tbl   = document.getElementById('attrTable');
      var row   = tbl.insertRow(idx + 1);
      var cell1 = row.insertCell(-1);
      var cell2 = row.insertCell(-1);
      var regx  = /<a([^>]+)<\/a>/i;

      cell1.className = 'label';
      cell1.innerHTML = src.childNodes[0].innerHTML.replace(/(.*)(addSpec)(.*)(\[)(\+)/i, "$1removeSpec$3$4-");
      cell2.innerHTML = src.childNodes[1].innerHTML.replace(/readOnly([^\s|>]*)/i, '');
      var form = layui.form();form.render(); //刷新select选择框渲染
  }

  /**
   * 鍒犻櫎瑙勬牸鍊
   */
  function removeSpec(obj)
  {
      var row = rowindex(obj.parentNode.parentNode);
      var tbl = document.getElementById('attrTable');

      tbl.deleteRow(row);
  }

  /**
   * 澶勭悊瑙勬牸
   */
  function handleSpec()
  {
      var elementCount = document.forms['theForm'].elements.length;
      for (var i = 0; i < elementCount; i++)
      {
          var element = document.forms['theForm'].elements[i];
          if (element.id.substr(0, 5) == 'spec_')
          {
              var optCount = element.options.length;
              var value = new Array(optCount);
              for (var j = 0; j < optCount; j++)
              {
                  value[j] = element.options[j].value;
              }

              var hiddenSpec = document.getElementById('hidden_' + element.id);
              hiddenSpec.value = value.join(String.fromCharCode(13)); // 鐢ㄥ洖杞﹂敭闅斿紑姣忎釜瑙勬牸
          }
      }
      return true;
  }

  function handlePromote(checked)
  {
      document.forms['theForm'].elements['promote_price'].disabled = !checked;
      //  促销商品时间精确到时分 Start
//      document.forms['theForm'].elements['selbtn1'].disabled = !checked;
//      document.forms['theForm'].elements['selbtn2'].disabled = !checked;
      document.forms['theForm'].elements['promote_start_date'].disabled = !checked;
      document.forms['theForm'].elements['promote_end_date'].disabled = !checked;
      <!--  促销商品时间精确到时分 End -->
  }

   function handleBuy(checked)
  {
      document.forms['theForm'].elements['buymax'].disabled = !checked;
      document.forms['theForm'].elements['buymax_start_date'].disabled = !checked;
      document.forms['theForm'].elements['buymax_end_date'].disabled = !checked;
      // document.forms['theForm'].elements['selbtn3'].disabled = !checked;
      // document.forms['theForm'].elements['selbtn4'].disabled = !checked;
  }
  function handleAutoThumb(checked)
  {
      document.forms['theForm'].elements['goods_thumb'].disabled = checked;
      document.forms['theForm'].elements['goods_thumb_url'].disabled = checked;
  }

  /**
   * 蹇?€熸坊鍔犲搧鐗
   */
  function rapidBrandAdd(conObj)
  {
      var brand_div = document.getElementById("brand_add");

      if(brand_div.style.display != '')
      {
          var brand =document.forms['theForm'].elements['addedBrandName'];
          brand.value = '';
          brand_div.style.display = '';
      }
  }

  function hideBrandDiv()
  {
      var brand_add_div = document.getElementById("brand_add");
      if(brand_add_div.style.display != 'none')
      {
          brand_add_div.style.display = 'none';
      }
  }

  function goBrandPage()
  {
      if(confirm(go_brand_page))
      {
          window.location.href='brand.php?act=add';
      }
      else
      {
          return;
      }
  }

  function rapidCatAdd()
  {
      var cat_div = document.getElementById("category_add");

      if(cat_div.style.display != '')
      {
          var cat =document.forms['theForm'].elements['addedCategoryName'];
          cat.value = '';
          cat_div.style.display = '';
      }
  }

  function addBrand()
  {
      var brand = document.forms['theForm'].elements['addedBrandName'];
      if(brand.value.replace(/^\s+|\s+$/g, '') == '')
      {
          alert(brand_cat_not_null);
          return;
      }

      var params = 'brand=' + brand.value;
      Ajax.call('brand.php?is_ajax=1&act=add_brand', params, addBrandResponse, 'GET', 'JSON');
  }

  function addBrandResponse(result)
  {
      if (result.error == '1' && result.message != '')
      {
          alert(result.message);
          return;
      }

      var brand_div = document.getElementById("brand_add");
      brand_div.style.display = 'none';

      var response = result.content;


	  document.getElementById("brand_search").value = response.brand;
	  document.getElementById("brand_id").value = response.id;
	  document.getElementById("xin_brand").innerHTML += "&nbsp;[<a href=javascript:go_brand_id("+response.id+",'"+response.brand+"')>"+response.brand+"</a>]&nbsp;";
	  document.getElementById("xin_brand").style.display = "block";



      var selCat = document.forms['theForm'].elements['brand_id'];
      var opt = document.createElement("OPTION");
      opt.value = response.id;
      opt.selected = true;
      opt.text = response.brand;

      if (Browser.isIE)
      {
          selCat.add(opt);
      }
      else
      {
          selCat.appendChild(opt);
      }

      return;
  }

  function addCategory()
  {
      var parent_id = document.forms['theForm'].elements['cat_id'];
      var cat = document.forms['theForm'].elements['addedCategoryName'];
      if(cat.value.replace(/^\s+|\s+$/g, '') == '')
      {
          alert(category_cat_not_null);
          return;
      }

      var params = 'parent_id=' + parent_id.value;
      params += '&cat=' + cat.value;
      Ajax.call('category.php?is_ajax=1&act=add_category', params, addCatResponse, 'GET', 'JSON');
  }

  function hideCatDiv()
  {
      var category_add_div = document.getElementById("category_add");
      if(category_add_div.style.display != null)
      {
          category_add_div.style.display = 'none';
      }
  }

  function addCatResponse(result)
  {
      if (result.error == '1' && result.message != '')
      {
          alert(result.message);
          return;
      }

      var category_add_div = document.getElementById("category_add");
      category_add_div.style.display = 'none';

      var response = result.content;

      var selCat = document.forms['theForm'].elements['cat_id'];
      var opt = document.createElement("OPTION");
      opt.value = response.id;
      opt.selected = true;
      opt.innerHTML = response.cat;

      //鑾峰彇瀛愬垎绫荤殑绌烘牸鏁
      var str = selCat.options[selCat.selectedIndex].text;
      var temp = str.replace(/^\s+/g, '');
      var lengOfSpace = str.length - temp.length;
      if(response.parent_id != 0)
      {
          lengOfSpace += 4;
      }
      for (i = 0; i < lengOfSpace; i++)
      {
          opt.innerHTML = '&nbsp;' + opt.innerHTML;
      }

      for (i = 0; i < selCat.length; i++)
      {
          if(selCat.options[i].value == response.parent_id)
          {
              if(i == selCat.length)
              {
                  if (Browser.isIE)
                  {
                      selCat.add(opt);
                  }
                  else
                  {
                      selCat.appendChild(opt);
                  }
              }
              else
              {
                  selCat.insertBefore(opt, selCat.options[i + 1]);
              }
              //opt.selected = true;
              break;
          }

      }

      return;
  }

    function goCatPage()
    {
        if(confirm(go_category_page))
        {
            window.location.href='category.php?act=add';
        }
        else
        {
            return;
        }
    }


  /**
   * 鍒犻櫎蹇?€熷垎绫
   */
  function removeCat()
  {
      if(!document.forms['theForm'].elements['parent_cat'] || !document.forms['theForm'].elements['new_cat_name'])
      {
          return;
      }

      var cat_select = document.forms['theForm'].elements['parent_cat'];
      var cat = document.forms['theForm'].elements['new_cat_name'];

      cat.parentNode.removeChild(cat);
      cat_select.parentNode.removeChild(cat_select);
  }

  /**
   * 鍒犻櫎蹇?€熷搧鐗
   */
  function removeBrand()
  {
      if (!document.forms['theForm'].elements['new_brand_name'])
      {
          return;
      }

      var brand = document.theForm.new_brand_name;
      brand.parentNode.removeChild(brand);
  }

  /**
   * 娣诲姞鎵╁睍鍒嗙被
   */
  function addOtherCat(conObj)
  {
      var sel = document.createElement("SELECT");
      var selCat = document.forms['theForm'].elements['cat_id'];

      for (i = 0; i < selCat.length; i++)
      {
          var opt = document.createElement("OPTION");
          opt.text = selCat.options[i].text;
          opt.value = selCat.options[i].value;
          if (Browser.isIE)
          {
              sel.add(opt);
          }
          else
          {
              sel.appendChild(opt);
          }
      }
      conObj.appendChild(sel);
      sel.name = "other_cat[]";
      sel.onChange = function() {checkIsLeaf(this);};
      var form = layui.form();form.render(); //刷新select选择框渲染
  }

  /* 鍏宠仈鍟嗗搧鍑芥暟 */
  function searchGoods(szObject, catId, brandId, keyword)
  {
      var filters = new Object;

      filters.cat_id = elements[catId].value;
      filters.brand_id = elements[brandId].value;
      filters.keyword = Utils.trim(elements[keyword].value);
      filters.exclude = document.forms['theForm'].elements['goods_id'].value;

      szObject.loadOptions('get_goods_list', filters);
  }

  /**
   * 鍏宠仈鏂囩珷鍑芥暟
   */
  function searchArticle()
  {
    var filters = new Object;

    filters.title = Utils.trim(elements['article_title'].value);

    sz3.loadOptions('get_article_list', filters);
  }

  /**
   * 鏂板?涓€涓?浘鐗
   */
  function addImg(obj)
  {
      var src  = obj.parentNode.parentNode;
      var idx  = rowindex(src);
      var tbl  = document.getElementById('gallery-table');
      var row  = tbl.insertRow(idx + 1);
      var cell = row.insertCell(-1);
      cell.innerHTML = src.cells[0].innerHTML.replace(/(.*)(addImg)(.*)(\[)(\+)/i, "$1removeImg$3$4-");
  }

  /**
   * 鍒犻櫎鍥剧墖涓婁紶
   */
  function removeImg(obj)
  {
      var row = rowindex(obj.parentNode.parentNode);
      var tbl = document.getElementById('gallery-table');

      tbl.deleteRow(row);
  }

  /**
   * 鍒犻櫎鍥剧墖
   */
  function dropImg(imgId)
  {
    Ajax.call('goods.php?is_ajax=1&act=drop_image', "img_id="+imgId, dropImgResponse, "GET", "JSON");
  }

  function dropImgResponse(result)
  {
      if (result.error == 0)
      {
          document.getElementById('gallery_' + result.content).style.display = 'none';
      }
  }

  /**
   * 灏嗗競鍦轰环鏍煎彇鏁
   */
  function integral_market_price()
  {
    document.forms['theForm'].elements['market_price'].value = parseInt(document.forms['theForm'].elements['market_price'].value);
  }

   /**
   * 灏嗙Н鍒嗚喘涔伴?搴﹀彇鏁
   */
  function parseint_integral()
  {
    document.forms['theForm'].elements['integral'].value = parseInt(document.forms['theForm'].elements['integral'].value);
  }


  /**
  * 妫€鏌ヨ揣鍙锋槸鍚﹀瓨鍦
  */
  function checkGoodsSn(goods_sn, goods_id)
  {
    if (goods_sn == '')
    {
        document.getElementById('goods_sn_notice').innerHTML = "";
        return;
    }

    var callback = function(res)
    {
      if (res.error > 0)
      {
        document.getElementById('goods_sn_notice').innerHTML = res.message;
        document.getElementById('goods_sn_notice').style.color = "red";
      }
      else
      {
        document.getElementById('goods_sn_notice').innerHTML = "";
      }
    }
    Ajax.call('goods.php?is_ajax=1&act=check_goods_sn', "goods_sn=" + goods_sn + "&goods_id=" + goods_id, callback, "GET", "JSON");
  }

  /**
   * 鏂板?涓€涓?紭鎯犱环鏍
   */
  function addVolumePrice(obj)
  {
    var src      = obj.parentNode.parentNode;
    var tbl      = document.getElementById('tbody-volume');

    var validator  = new Validator('theForm');
    checkVolumeData("0",validator);
    if (!validator.passed())
    {
      return false;
    }

    var row  = tbl.insertRow(tbl.rows.length);
    var cell = row.insertCell(-1);
    cell.innerHTML = src.cells[0].innerHTML.replace(/(.*)(addVolumePrice)(.*)(\[)(\+)/i, "$1removeVolumePrice$3$4-");

    var number_list = document.getElementsByName("volume_number[]");
    var price_list  = document.getElementsByName("volume_price[]");

    number_list[number_list.length-1].value = "";
    price_list[price_list.length-1].value   = "";
  }

  /**
   * 鍒犻櫎浼樻儬浠锋牸
   */
  function removeVolumePrice(obj)
  {
    var row = rowindex(obj.parentNode.parentNode);
    var tbl = document.getElementById('tbody-volume');

    tbl.deleteRow(row);
  }

  /**
   * 鏍￠獙浼樻儬鏁版嵁鏄?惁姝ｇ‘
   */
  function checkVolumeData(isSubmit,validator)
  {
    var volumeNum = document.getElementsByName("volume_number[]");
    var volumePri = document.getElementsByName("volume_price[]");
    var numErrNum = 0;
    var priErrNum = 0;

    for (i = 0 ; i < volumePri.length ; i ++)
    {
      if ((isSubmit != 1 || volumeNum.length > 1) && numErrNum <= 0 && volumeNum.item(i).value == "")
      {
        validator.addErrorMsg(volume_num_not_null);
        numErrNum++;
      }

      if (numErrNum <= 0 && Utils.trim(volumeNum.item(i).value) != "" && ! Utils.isNumber(Utils.trim(volumeNum.item(i).value)))
      {
        validator.addErrorMsg(volume_num_not_number);
        numErrNum++;
      }

      if ((isSubmit != 1 || volumePri.length > 1) && priErrNum <= 0 && volumePri.item(i).value == "")
      {
        validator.addErrorMsg(volume_price_not_null);
        priErrNum++;
      }

      if (priErrNum <= 0 && Utils.trim(volumePri.item(i).value) != "" && ! Utils.isNumber(Utils.trim(volumePri.item(i).value)))
      {
        validator.addErrorMsg(volume_price_not_number);
        priErrNum++;
      }
    }
  }
  
</script>


<script type="text/javascript">
	$().ready(function(){
		// $("#cat_name")为获取分类名称的jQuery对象，可根据实际情况修改
		// $("#cat_id")为获取分类ID的jQuery对象，可根据实际情况修改
		// "<?php echo $this->_var['goods_cat_id']; ?>"为被选中的商品分类编号，无则设置为null或者不写此参数或者为空字符串
		$.ajaxCategorySelecter($("#cat_name"), $("#cat_id"), "<?php echo $this->_var['goods_cat_id']; ?>");
	});
</script>

<?php echo $this->fetch('pagefooter.htm'); ?>
