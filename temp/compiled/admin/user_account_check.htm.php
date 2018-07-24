<?php echo $this->fetch('pageheader.htm'); ?>
<div class="main-div">
<form method="post" action="user_account.php" name="theForm" onsubmit="return validate();" class="layui-form">
<table border="0" width="100%">
  <?php if ($this->_var['bank_info'] != ''): ?>
    <tr>
      <td colspan="2" style="padding-left:10px;"><strong>银行卡信息：</strong><hr /></td>
    </tr>
    <tr>
      <td colspan="2" style="padding-left:10px;">
        <strong>真实姓名：</strong><?php echo $this->_var['bank_info']['real_name']; ?><br/><br/>
        <strong>银行卡号码：</strong><?php echo $this->_var['bank_info']['card_number']; ?><br/><br/>
        <strong>开户支行名称：</strong><?php echo $this->_var['bank_info']['card_name']; ?><br/><br/>
        <strong>银行卡信息：</strong><?php echo $this->_var['bank_info']['card_info']; ?><br/><br/><hr />
      </td>
    </tr>
  <?php endif; ?>
  <tr>
    <td colspan="2" style="padding-left:10px;"><strong><?php echo $this->_var['lang']['surplus_info']; ?>：</strong><hr /></td>
  </tr>
  <tr>
    <td colspan="2" style="padding-left:10px;">
      <strong><?php echo $this->_var['lang']['user_id']; ?>：</strong><?php echo $this->_var['user_name']; ?><br/><br/>
      <strong><?php echo $this->_var['lang']['surplus_amount']; ?>：</strong><?php echo $this->_var['surplus']['amount']; ?> <br/><br/>
      <strong><?php echo $this->_var['lang']['add_date']; ?>：</strong><?php echo $this->_var['surplus']['add_time']; ?><br/><br/>
      <strong><?php echo $this->_var['lang']['process_type']; ?>：</strong><?php echo $this->_var['process_type']; ?><br/><br/>
      <?php if ($this->_var['surplus']['pay_method']): ?>
      <strong><?php echo $this->_var['lang']['pay_method']; ?>：</strong><?php echo $this->_var['surplus']['payment']; ?>
      <?php endif; ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" style="padding-left:10px;"><strong><?php echo $this->_var['lang']['surplus_desc']; ?>：</strong><?php echo $this->_var['surplus']['user_note']; ?><hr /></td>
  </tr>
  <tr>
    <th width="15%" valign="middle" align="right"><?php echo $this->_var['lang']['surplus_notic']; ?>：</th>
    <td width="85%">
      <textarea name="admin_note" cols="55" rows="5"><?php echo $this->_var['surplus']['admin_note']; ?></textarea><span class="require-field">*</span>
    </td>
  </tr>
  <tr>
    <th width="15%" valign="middle" align="right">扣除手续费：</th>
    <td width="85%">
      <input type="text" name="poundage" value="<?php echo $this->_var['surplus']['poundage']; ?>">元
    </td>
  </tr>
  <tr>
    <th width="15%" valign="middle" align="right"><?php echo $this->_var['lang']['status']; ?>：</th>
    <td>
      <input type="radio" name="is_paid" value="0" checked="true" title="<?php echo $this->_var['lang']['unconfirm']; ?>" />
      <input type="radio" name="is_paid" value="1" title="<?php echo $this->_var['lang']['confirm']; ?>" />
      <font color="red">设置“已转账”状态，系统会自动扣除用户申请提现的余额！</font>
    </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>
      <input type="hidden" name="act" value="action" />
      <input type="hidden" name="id" value="<?php echo $this->_var['id']; ?>" />
      <input name="submit" type="submit" value="<?php echo $this->_var['lang']['button_submit']; ?>" class="layui-btn" />
      <input type="reset" value="<?php echo $this->_var['lang']['button_reset']; ?>" class="layui-btn layui-btn-primary" />
    </td>
  </tr>
</table>
</form>
</div>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,validator.js')); ?>

<script language="JavaScript">
<!--
document.forms['theForm'].elements['admin_note'].focus();

/**
 * 检查表单输入的数据
 */
function validate()
{
    validator = new Validator("theForm");
    validator.required("admin_note",  deposit_notic_empty);
    return validator.passed();
}

onload = function()
{
    // 开始检查订单
    startCheckOrder();
}
//-->
</script>


<script type="text/javascript">
    layui.use('form', function(){
        var form = layui.form();
        form.render(); //更新全部 
    });
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>