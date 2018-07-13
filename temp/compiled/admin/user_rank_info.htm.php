<?php echo $this->fetch('pageheader.htm'); ?>
<style>
    .layui-form-label{width:204px !important;}
</style>

<form action="user_rank.php" method="post" name="theForm" onsubmit="return validate()" class="layui-form layui-form-pane">
<div class="layui-tab-content">
    <div class="layui-tab-item layui-show">
        <!--会员等级名称-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['rank_name']; ?></label>
            <div class="layui-input-block">
                <input type="text" name="rank_name" value="<?php echo $this->_var['rank']['rank_name']; ?>" class="layui-input" />
            </div>
        </div>
        <!--销量下限-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 销量下限</label>
            <div class="layui-input-block">
                <input type="text" name="min_sale_number" value="<?php echo $this->_var['rank']['min_sale_number']; ?>" class="layui-input" />
            </div>
        </div>
        <!--销量上限-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> 销量上限</label>
            <div class="layui-input-block">
                <input type="text" name="max_sale_number" value="<?php echo $this->_var['rank']['max_sale_number']; ?>" class="layui-input" />
            </div>
        </div>
        <!--第1阶段佣金比例-->
        <div class="layui-form-item">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('notice_commision_scale1');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
                <?php echo $this->_var['lang']['require_field']; ?> 第1阶段佣金比例(%)
            </label>
            <div class="layui-input-block">
                <input type="text" name="commision_scale1" value="<?php echo $this->_var['rank']['commision_scale1']; ?>" class="layui-input" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_commision_scale1">请填写为0-100的整数,如填入80，表示80%</span>
            </div>
        </div>
        <!--第2阶段佣金比例-->
       <div class="layui-form-item">
           <label class="layui-form-label">
               <a href="javascript:showNotice2('notice_commision_scale2');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
               <?php echo $this->_var['lang']['require_field']; ?> 第2阶段佣金比例(%)
           </label>
           <div class="layui-input-block">
               <input type="text" name="commision_scale2" value="<?php echo $this->_var['rank']['commision_scale2']; ?>" class="layui-input" />
               <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_commision_scale2">请填写为0-100的整数,如填入80，表示80%</span>
           </div>
       </div>
        <!--可销售数量-->
        <div class="layui-form-item">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('notice_sale_number');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
                <?php echo $this->_var['lang']['require_field']; ?> 可销售数量
            </label>
            <div class="layui-input-block">
                <input type="text" name="sale_number" value="<?php echo $this->_var['rank']['sale_number']; ?>" class="layui-input" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_sale_number">用户达到这个等级设计出售的商品库存量</span>
            </div>
        </div>
        <!--售价-->
        <div class="layui-form-item">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('notice_sale_price');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
                <?php echo $this->_var['lang']['require_field']; ?> 售价
            </label>
            <div class="layui-input-block">
                <input type="text" name="sale_price" value="<?php echo $this->_var['rank']['sale_price']; ?>" class="layui-input" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_sale_price">用户达到这个等级设计出售的商品售价</span>
            </div>
        </div>
        <!--积分下限-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label"><?php echo $this->_var['lang']['integral_min']; ?></label>
            <div class="layui-input-block">
                <input type="text" name="min_points" value="<?php echo $this->_var['rank']['min_points']; ?>" class="layui-input" />
            </div>
        </div>
        <!--积分上限-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label"><?php echo $this->_var['lang']['integral_max']; ?></label>
            <div class="layui-input-block">
                <input type="text" name="max_points" value="<?php echo $this->_var['rank']['max_points']; ?>" class="layui-input" />
            </div>
        </div>
        <!--在商品详情页显示该会员等级的商品价格-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label">是否显示等级价格</label>
            <div class="layui-input-block">
                <input type="checkbox" name="show_price" value="1" <?php if ($this->_var['rank']['show_price'] == 1): ?> checked="true"<?php endif; ?> title="<?php echo $this->_var['lang']['show_price']; ?>" />
            </div>
        </div>
        <!--在商品详情页显示该会员等级的商品价格-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('notice_special');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
                特殊会员组
            </label>
            <div class="layui-input-block">
                <input type="checkbox" name="special_rank" value="1" checked="true" lay-filter="sel_special_rank" title="<?php echo $this->_var['lang']['special_rank']; ?>" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_special"><?php echo $this->_var['lang']['notice_special']; ?></span>
            </div>
        </div>
        <!--是否参与分成-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('notice_recomm');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
                是否参与分成
            </label>
            <div class="layui-input-block">
                <select id="recomm" name="recomm">
                    <option value="0">否</option><option value="1" >是</option>
                </select>
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_recomm"><?php echo $this->_var['lang']['notice_fencheng']; ?></span>
            </div>
        </div>
        <!--初始折扣率-->
        <div class="layui-form-item" style="display:none;">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('notice_discount');" title="<?php echo $this->_var['lang']['form_notice']; ?>"><img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>"></a>
                <?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['discount']; ?>
            </label>
            <div class="layui-input-block">
                <input type="text" name="discount" value="<?php echo $this->_var['rank']['discount']; ?>" class="layui-input" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="notice_discount"><?php echo $this->_var['lang']['notice_discount']; ?></span>
            </div>
        </div>
        <p class="btn_padding_left">
            <input type="hidden" name="act" value="<?php echo $this->_var['form_action']; ?>" />
            <input type="hidden" name="id" value="<?php echo $this->_var['rank']['rank_id']; ?>" />
            <input type="submit" value="<?php echo $this->_var['lang']['button_submit']; ?>" class="layui-btn" />
            <input type="reset" value="<?php echo $this->_var['lang']['button_reset']; ?>" class="layui-btn layui-btn-primary" />
        </p>
    </div>
</div>
</form>

<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,validator.js')); ?>

<script language="JavaScript">
<!--
document.forms['theForm'].elements['rank_name'].focus();

onload = function()
{
  // 开始检查订单
  startCheckOrder();
}

/**
 * 检查表单输入的数据
 */
function validate()
{
    if (!document.forms['theForm'].elements['special_rank'].checked)
    {
        if (Utils.trim(document.forms['theForm'].elements['min_points'].value) == '' ||
            !Utils.isInt(document.forms['theForm'].elements['min_points'].value))
        {
            alert(integral_min_invalid);
            return false;
        }

        if (Utils.trim(document.forms['theForm'].elements['max_points'].value) == '' ||
            !Utils.isInt(document.forms['theForm'].elements['max_points'].value))
        {
            alert(integral_max_invalid);
            return false;
        }

        if (!document.forms['theForm'].elements['special_rank'].checked &&
            (parseInt(document.forms['theForm'].elements['max_points'].value) <=
            parseInt(document.forms['theForm'].elements['min_points'].value)))
        {
            alert(integral_max_small);
            return false;
        }
        if (parseInt(document.forms['theForm'].elements['discount'].value) < 1 ||
            parseInt(document.forms['theForm'].elements['discount'].value) > 100)
        {
            alert(discount_invalid);
            return false;
        }
    }

    if (Utils.trim(document.forms['theForm'].elements['min_sale_number'].value) == '' ||
        !Utils.isInt(document.forms['theForm'].elements['min_sale_number'].value))
    {
        alert('您没有输入销量下限或者销量下限不是一个整数。');
        return false;
    }

    if (Utils.trim(document.forms['theForm'].elements['max_sale_number'].value) == '' ||
        !Utils.isInt(document.forms['theForm'].elements['max_sale_number'].value))
    {
        alert('您没有输入销量上限或者销量上限不是一个整数。');
        return false;
    }
    if ((parseInt(document.forms['theForm'].elements['max_sale_number'].value) <=
        parseInt(document.forms['theForm'].elements['min_sale_number'].value)))
    {
        alert('销量上限必须大于销量下限。');
        return false;
    }
    if (parseInt(document.forms['theForm'].elements['commision_scale1'].value) < 1 ||
        parseInt(document.forms['theForm'].elements['commision_scale1'].value) > 100)
    {
        alert('您没有输入第1阶段佣金比例或者第1阶段佣金比例无效。');
        return false;
    }
    if (parseInt(document.forms['theForm'].elements['commision_scale2'].value) < 1 ||
        parseInt(document.forms['theForm'].elements['commision_scale2'].value) > 100)
    {
        alert('您没有输入第2阶段佣金比例或者第2阶段佣金比例无效。');
        return false;
    }
    if (parseInt(document.forms['theForm'].elements['sale_number'].value) < 1)
    {
        alert('您没有输入可销售数量或者可销售数量无效。');
        return false;
    }
    if (parseInt(document.forms['theForm'].elements['sale_price'].value) < 1)
    {
        alert('您没有输入售价或者售价无效。');
        return false;
    }

    validator = new Validator("theForm");
    validator.required('rank_name', rank_name_empty);
    validator.isInt('discount', discount_invalid, true);
    return validator.passed();
}

function doSpecial()
{
  if(document.forms['theForm'].elements['special_rank'].checked)
  {
      document.forms['theForm'].elements['max_points'].disabled = "true";
      document.forms['theForm'].elements['min_points'].disabled = "true";
  }
  else
  {
      document.forms['theForm'].elements['max_points'].disabled = "";
      document.forms['theForm'].elements['min_points'].disabled = "";
  }
}
//-->
</script>


<script type="text/javascript">
    layui.use('form', function(){
        var form = layui.form(),layer = layui.layer,layedit = layui.layedit,laydate = layui.laydate;
        form.render(); //更新全部

        form.on('checkbox(sel_special_rank)', function(data){
            doSpecial();
        });
    });
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>