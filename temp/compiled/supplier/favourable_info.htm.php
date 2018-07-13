<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'validator.js')); ?>
<script type="text/javascript" src="../js/My97DatePicker/WdatePicker.js"></script>

<form method="post" action="favourable.php" name="theForm" enctype="multipart/form-data" onSubmit="return validate()" class="layui-form layui-form-pane">
<div class="layui-tab-content">
    <div class="layui-tab-item layui-show">
        <!--优惠活动名称-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['require_field']; ?> <?php echo $this->_var['lang']['label_act_name']; ?></label>
            <div class="layui-input-block">
                <input name="act_name" type="text" id="act_name" value="<?php echo $this->_var['favourable']['act_name']; ?>" placeholder="请输入优惠活动名称" class="layui-input" />
            </div>
        </div>
        <!--优惠开始时间-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_start_time']; ?></label>
            <div class="layui-input-block">
                <input name="start_time" type="text" id="start_time" value="<?php echo $this->_var['favourable']['start_time']; ?>" readonly="readonly" class="layui-input"  onfocus="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})" />
            </div>
        </div>
        <!--优惠结束时间-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_end_time']; ?></label>
            <div class="layui-input-block">
                <input name="end_time" type="text" id="end_time" value="<?php echo $this->_var['favourable']['end_time']; ?>" readonly="readonly" class="layui-input"  onfocus="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'})" />
            </div>
        </div>
        <!--活动代表图片-->
        <div class="layui-form-item">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('logoNotice');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                    <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                </a>
                活动代表图片
            </label>
            <div class="layui-input-block">
                <input name="logo" type="file" style="margin-left:10px;margin-top:5px;" />
                <?php if ($this->_var['favourable']['logo']): ?>
                    <!-- <a href="?act=del&code=logo">删除图片</a>  --><img src="images/yes.gif" border="0" onmouseover="showImg('logo_layer', 'show')" onmouseout="showImg('logo_layer', 'hide')" />
                    <div id="logo_layer" style="position:absolute; width:100px; height:100px; z-index:1; visibility:hidden" border="1">
                      <img src="<?php echo $this->_var['favourable']['logo']; ?>" border="0" />
                    </div>
                <?php else: ?>
                    <?php if ($this->_var['favourable']['logo'] != ""): ?>
                    <img src="images/yes.gif" alt="yes" />
                    <?php else: ?>
                    <img src="images/no.gif" alt="no" />
                    <?php endif; ?>
                <?php endif; ?>
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="logoNotice">
                    为达到前台图标显示最佳状态，建议上传580X260px图片
                </span>
            </div>
        </div>
        <!--享受优惠的会员等级-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_user_rank']; ?></label>
            <div class="layui-input-block">
                <?php $_from = $this->_var['user_rank_list']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'user_rank');if (count($_from)):
    foreach ($_from AS $this->_var['user_rank']):
?>
                <input type="checkbox" name="user_rank[]" value="<?php echo $this->_var['user_rank']['rank_id']; ?>" <?php if ($this->_var['user_rank']['checked']): ?>checked="true"<?php endif; ?> title="<?php echo $this->_var['user_rank']['rank_name']; ?>" />
                <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
            </div>
        </div>
        <!--优惠范围-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_act_range']; ?></label>
            <div class="layui-input-block">
                <select name="act_range" lay-filter="sel_act_range">
                    <option value="0" selected="selected" <?php if ($this->_var['favourable']['act_range'] == 0): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['far_all']; ?></option>
                    <option value="1" <?php if ($this->_var['favourable']['act_range'] == 1): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['far_category']; ?></option>
                    <option value="2" <?php if ($this->_var['favourable']['act_range'] == 2): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['far_brand']; ?></option>
                    <option value="3" <?php if ($this->_var['favourable']['act_range'] == 3): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['far_goods']; ?></option>
                </select>
                <div id="range-div" style="width:320px;">
                    <?php $_from = $this->_var['act_range_ext']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'item');if (count($_from)):
    foreach ($_from AS $this->_var['item']):
?>
                        <input name="act_range_ext[]" type="checkbox" value="<?php echo $this->_var['item']['id']; ?>" checked="checked" title="<?php echo $this->_var['item']['name']; ?>" />
                    <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                </div>
            </div>
        </div>
        <!--搜索并加入优惠范围-->
        <div class="layui-form-item" id="range_search"<?php if ($this->_var['favourable']['act_range'] == 0): ?> style="display:none"<?php endif; ?>>
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_search_and_add']; ?></label>
            <div class="layui-input-block">
                <input name="keyword" type="text" id="keyword" class="layui-input" style="float:left;">
                <input name="search" type="button" id="search" value="<?php echo $this->_var['lang']['button_search']; ?>" class="layui-btn" onclick="searchItem()" style="float:left;margin:0 10px;" />
                <select name="result" id="result">
                    <option></option>
                </select> 
                <input type="button" name="add_range" value="+" class="layui-btn" onclick="addRange()" style="margin-left:10px;" />
            </div>
        </div>
        <!--金额下限-->
        <div class="layui-form-item">
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_min_amount']; ?></label>
            <div class="layui-input-block">
                <input name="min_amount" type="text" id="min_amount" value="<?php echo $this->_var['favourable']['min_amount']; ?>" class="layui-input" />
            </div>
        </div>
        <!--金额上限-->
        <div class="layui-form-item">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('maxNotice');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                    <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                </a>
                <?php echo $this->_var['lang']['label_max_amount']; ?>
            </label>
            <div class="layui-input-block">
                <input name="max_amount" type="text" id="max_amount" value="<?php echo $this->_var['favourable']['max_amount']; ?>" class="layui-input" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="maxNotice">
                    <?php echo $this->_var['lang']['notice_max_amount']; ?>
                </span>
            </div>
        </div>
        <!--优惠方式-->
        <div class="layui-form-item">
            <label class="layui-form-label">
                <a href="javascript:showNotice2('NoticeActType');" title="<?php echo $this->_var['lang']['form_notice']; ?>">
                    <img src="images/notice.gif" width="16" height="16" border="0" alt="<?php echo $this->_var['lang']['form_notice']; ?>">
                </a>
                <?php echo $this->_var['lang']['label_act_type']; ?>
            </label>
            <div class="layui-input-block">
                <select name="act_type" id="act_type" lay-filter="sel_act_type">
                    <option value="0" <?php if ($this->_var['favourable']['act_type'] == 0): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['fat_goods']; ?></option>
                    <option value="1" <?php if ($this->_var['favourable']['act_type'] == 1): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['fat_price']; ?></option>
                    <option value="2" <?php if ($this->_var['favourable']['act_type'] == 2): ?>selected="selected"<?php endif; ?>><?php echo $this->_var['lang']['fat_discount']; ?></option>
                </select>
                <input name="act_type_ext" type="text" id="act_type_ext" value="<?php echo $this->_var['favourable']['act_type_ext']; ?>" class="layui-input" />
                <span class="notice-span" <?php if ($this->_var['help_open']): ?>style="display:initial" <?php else: ?> style="display:none" <?php endif; ?> id="NoticeActType">
                    <?php echo $this->_var['lang']['notice_act_type']; ?>
                </span>
                <div id="gift-div" style="width:60%">
                    <table id="gift-table" class="layui-table">
                        <?php if ($this->_var['favourable']['gift']): ?>
                        <tr align="center">
                            <td><strong><?php echo $this->_var['lang']['js_languages']['gift']; ?></strong></td>
                            <td><strong><?php echo $this->_var['lang']['js_languages']['price']; ?></strong></td>
                        </tr>
                        <?php $_from = $this->_var['favourable']['gift']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('key', 'goods');if (count($_from)):
    foreach ($_from AS $this->_var['key'] => $this->_var['goods']):
?>
                        <tr>
                            <td>
                                <input type="checkbox" name="gift_id[<?php echo $this->_var['key']; ?>]" value="<?php echo $this->_var['goods']['id']; ?>" checked="checked" /><?php echo $this->_var['goods']['name']; ?>
                            </td>
                            <td align="right">
                                <input name="gift_price[<?php echo $this->_var['key']; ?>]" type="text" value="<?php echo $this->_var['goods']['price']; ?>" class="layui-input" style="text-align:right" />
                                <input name="gift_name[<?php echo $this->_var['key']; ?>]" type="hidden" value="<?php echo $this->_var['goods']['name']; ?>" />
                            </td>
                        </tr>
                        <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        <!--搜索并加入赠品（特惠品）-->
        <div class="layui-form-item" id="type_search"<?php if ($this->_var['favourable']['act_type'] != 0): ?> style="display:none"<?php endif; ?>>
            <label class="layui-form-label"><?php echo $this->_var['lang']['label_search_and_add_gift']; ?></label>
            <div class="layui-input-block">
                <input name="keyword1" type="text" id="keyword1" class="layui-input" style="float:left;"/>
                <input name="search1" type="button" id="search1" value="<?php echo $this->_var['lang']['button_search']; ?>" class="layui-btn" onclick="searchItem1()" style="float:left;margin:0 10px;" />
                <select name="result1" id="result1">
                    <option></option>
                </select>
                <input name="add_gift" type="button" class="layui-btn" id="add_gift" onclick="addGift()" value="+" style="margin-left:10px;" />
            </div>
        </div>
        <p class="btn_padding_left">
            <input type="submit" class="layui-btn" value="<?php echo $this->_var['lang']['button_submit']; ?>" />
            <input type="reset" class="layui-btn layui-btn-primary" value="<?php echo $this->_var['lang']['button_reset']; ?>" />
            <input type="hidden" name="act" value="<?php echo $this->_var['form_action']; ?>" />
            <input type="hidden" name="id" value="<?php echo $this->_var['favourable']['act_id']; ?>" />
        </p>

    </div>
</div>
</form>


<script language="JavaScript">
<!--
onload = function()
{
    // 开始检查订单
    startCheckOrder();
//    changeRange(document.forms['theForm'].elements['act_range'].value);
//    changeType(document.forms['theForm'].elements['act_type'].value);
}
/**
 * 检查表单输入的数据
 */
function validate()
{
    validator = new Validator("theForm");
    validator.required('act_name', act_name_not_null);
    validator.isNumber('min_amount', min_amount_not_number, true);
    validator.isNumber('max_amount', max_amount_not_number, true);
    validator.isNumber('act_type_ext', act_type_ext_not_number, true);
    validator.islt('start_time', 'end_time', start_lt_end);
    if (document.forms['theForm'].elements['max_amount'].value > 0)
    {
      validator.gt('max_amount', 'min_amount', amount_invalid);
    }

    return validator.passed();
}

function searchItem()
{
  var filter = new Object;
  filter.keyword  = document.forms['theForm'].elements['keyword'].value;
  filter.act_range = document.forms['theForm'].elements['act_range'].value;
  if (filter.act_range == 0)
  {
    alert(all_need_not_search);
    return;
  }

  Ajax.call('favourable.php?is_ajax=1&act=search', filter, searchResponse, 'GET', 'JSON');
}

function searchResponse(result)
{
  if (result.error == '1' && result.message != '')
  {
    alert(result.message);
  return;
  }

  var sel = document.forms['theForm'].elements['result'];

  sel.length = 0;

  /* 创建 options */
  var goods = result.content;
  if (goods)
  {
    for (i = 0; i < goods.length; i++)
    {
      var opt = document.createElement("OPTION");
      opt.value = goods[i].id;
      opt.text  = goods[i].name;
      sel.options.add(opt);
    }
  }

  var form = layui.form();
  form.render(); //更新全部

  return;
}

/**
 * 改变优惠范围
 * @param int rangeId
 */
function changeRange(rangeId)
{
  document.getElementById('range-div').innerHTML = '';
  document.getElementById('result').length = 0;
  var row = document.getElementById('range_search');
  if (rangeId <= 0)
  {
    row.style.display = 'none';
  }
  else
  {
    row.style.display = '';
  }
}

function addRange()
{
  var selRange = document.forms['theForm'].elements['act_range'];
  if (selRange.value == 0)
  {
    alert(all_need_not_search);
    return;
  }
  var selResult = document.getElementById('result');
  if (selResult.value == 0)
  {
    alert(pls_search);
    return;
  }
  var id = selResult.options[selResult.selectedIndex].value;
  var name = selResult.options[selResult.selectedIndex].text;

  // 检查是否已经存在
  var exists = false;
  var eles = document.forms['theForm'].elements;
  for (var i = 0; i < eles.length; i++)
  {
    if (eles[i].type=="checkbox" && eles[i].name.substr(0, 13) == 'act_range_ext')
    {
      if (eles[i].value == id)
      {
        exists = true;
        alert(range_exists);
        break;
      }
    }
  }

  // 创建checkbox
  if (!exists)
  {
    var html = '<input name="act_range_ext[]" type="checkbox" value="' + id + '" checked="checked" />' + name + '<br />';
    document.getElementById('range-div').innerHTML += html;
  }

    var form = layui.form();
    form.render(); //更新全部
}

/**
 * 搜索赠品
 */
function searchItem1()
{
  if (document.forms['theForm'].elements['act_type'].value == 1)
  {
    alert(price_need_not_search);
    return;
  }
  var filter = new Object;
  filter.keyword  = document.forms['theForm'].elements['keyword1'].value;
  filter.act_range = 3;
  Ajax.call('favourable.php?is_ajax=1&act=search', filter, searchResponse1, 'GET', 'JSON');
}

function searchResponse1(result)
{
  if (result.error == '1' && result.message != '')
  {
    alert(result.message);
  return;
  }

  var sel = document.forms['theForm'].elements['result1'];

  sel.length = 0;

  /* 创建 options */
  var goods = result.content;
  if (goods)
  {
    for (i = 0; i < goods.length; i++)
    {
      var opt = document.createElement("OPTION");
      opt.value = goods[i].id;
      opt.text  = goods[i].name;
      sel.options.add(opt);
    }
  }

  var form = layui.form();
  form.render(); //更新全部

  return;
}

function addGift()
{
  var selType = document.forms['theForm'].elements['act_type'];
  if (selType.value == 1)
  {
    alert(price_need_not_search);
    return;
  }
  var selResult = document.getElementById('result1');
  if (selResult.value == 0)
  {
    alert(pls_search);
    return;
  }
  var id = selResult.options[selResult.selectedIndex].value;
  var name = selResult.options[selResult.selectedIndex].text;

  // 检查是否已经存在
  var exists = false;
  var eles = document.forms['theForm'].elements;
  for (var i = 0; i < eles.length; i++)
  {
    if (eles[i].type=="checkbox" && eles[i].name.substr(0, 7) == 'gift_id')
    {
      if (eles[i].value == id)
      {
        exists = true;
        alert(range_exists);
        break;
      }
    }
  }

  // 创建checkbox
  if (!exists)
  {
    var table = document.getElementById('gift-table');
    if (table.rows.length == 0)
    {
        var row = table.insertRow(-1);
        var cell = row.insertCell(-1);
        cell.align = 'center';
        cell.innerHTML = '<strong>' + gift + '</strong>';
        var cell = row.insertCell(-1);
        cell.align = 'center';
        cell.innerHTML = '<strong>' + price + '</strong>';
    }
    var row = table.insertRow(-1);
    var cell = row.insertCell(-1);
    cell.innerHTML = '<input name="gift_id[]" type="checkbox" value="' + id + '" checked="checked" />' + name;
    var cell = row.insertCell(-1);
    cell.align = 'right';
    cell.innerHTML = '<input name="gift_price[]" type="text" value="0" class="layui-input" style="text-align:right" />' +
                     '<input name="gift_name[]" type="hidden" value="' + name + '" />';
  }
  var form = layui.form();
  form.render(); //更新全部
}

function changeType(typeId)
{
  document.getElementById('gift-div').innerHTML = '<table id="gift-table"></table>';
  document.getElementById('result1').length = 0;
  var row = document.getElementById('type_search');
  if (typeId <= 0)
  {
    row.style.display = '';
  }
  else
  {
    row.style.display = 'none';
  }
}

//-->
</script>


<script type="text/javascript">
    layui.use('form', function(){
        var form = layui.form();
        form.render(); //更新全部

        //监听指定开关
        form.on('select(sel_act_range)', function(data){
            changeRange(data.value);
        });  
        form.on('select(sel_act_type)', function(data){
            changeType(data.value);
        });   
    });
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>
