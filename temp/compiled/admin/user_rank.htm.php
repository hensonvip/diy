<?php if ($this->_var['full_page']): ?>
<?php echo $this->fetch('pageheader.htm'); ?>
<?php echo $this->smarty_insert_scripts(array('files'=>'../js/utils.js,listtable.js')); ?>

<form method="post" action="" name="listForm">
<!-- start ads list -->
<div class="list-div" id="listDiv">
<?php endif; ?>

<table cellspacing='1' id="list-table" class="layui-table">
  <tr>
    <th><?php echo $this->_var['lang']['rank_name']; ?></th>
    <th style="display:none;"><?php echo $this->_var['lang']['integral_min']; ?></th>
    <th style="display:none;"><?php echo $this->_var['lang']['integral_max']; ?></th>
    <th>销量下限</th>
    <th>销量上限</th>
    <th>第1阶段佣金比例(%)</th>
    <th>第2阶段佣金比例(%)</th>
    <th>可销售数量</th>
    <th>售价</th>
    <th style="display:none;"><?php echo $this->_var['lang']['discount']; ?>(%)</th>
    <th style="display:none;"><?php echo $this->_var['lang']['special_rank']; ?></th>
    <th style="display:none;"><?php echo $this->_var['lang']['show_price_short']; ?></th>
    <th style="display:none;">分成会员</th>
    <th><?php echo $this->_var['lang']['handler']; ?></th>
  </tr>
  <?php $_from = $this->_var['user_ranks']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }; $this->push_vars('', 'rank');if (count($_from)):
    foreach ($_from AS $this->_var['rank']):
?>
  <tr>
    <td class="first-cell" ><span onclick="listTable.edit(this,'edit_name', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['rank_name']; ?></span></td>
    <td align="right" style="display:none;"><span <?php if ($this->_var['rank']['special_rank'] != 1): ?> onclick="listTable.edit(this, 'edit_min_points', <?php echo $this->_var['rank']['rank_id']; ?>)" <?php endif; ?> ><?php echo $this->_var['rank']['min_points']; ?></span></td>
    <td align="right" style="display:none;"><span <?php if ($this->_var['rank']['special_rank'] != 1): ?> onclick="listTable.edit(this, 'edit_max_points', <?php echo $this->_var['rank']['rank_id']; ?>)" <?php endif; ?> ><?php echo $this->_var['rank']['max_points']; ?></span></td>
    <td align="right"><span onclick="listTable.edit(this, 'edit_min_sale_number', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['min_sale_number']; ?></span></td>
    <td align="right"><span onclick="listTable.edit(this, 'edit_max_sale_number', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['max_sale_number']; ?></span></td>
    <td align="right"><span onclick="listTable.edit(this, 'edit_commision_scale1', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['commision_scale1']; ?></span></td>
    <td align="right"><span onclick="listTable.edit(this, 'edit_commision_scale2', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['commision_scale2']; ?></span></td>
    <td align="right"><span onclick="listTable.edit(this, 'edit_sale_number', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['sale_number']; ?></span></td>
    <td align="right"><span onclick="listTable.edit(this, 'edit_sale_price', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['sale_price']; ?></span></td>
    <td align="right" style="display:none;"><span onclick="listTable.edit(this, 'edit_discount', <?php echo $this->_var['rank']['rank_id']; ?>)"><?php echo $this->_var['rank']['discount']; ?></span></td>
    <td align="center" style="display:none;"><img src="images/<?php if ($this->_var['rank']['special_rank']): ?>yes<?php else: ?>no<?php endif; ?>.gif" onclick="listTable.toggle(this, 'toggle_special', <?php echo $this->_var['rank']['rank_id']; ?>)" /></td>
    <td align="center" style="display:none;"><img src="images/<?php if ($this->_var['rank']['show_price']): ?>yes<?php else: ?>no<?php endif; ?>.gif" onclick="listTable.toggle(this, 'toggle_showprice', <?php echo $this->_var['rank']['rank_id']; ?>)" /></td>
    <td align="center" style="display:none;"><img src="images/<?php if ($this->_var['rank']['is_recomm']): ?>yes<?php else: ?>no<?php endif; ?>.gif" onclick="listTable.toggle(this, 'toggle_is_recomm', <?php echo $this->_var['rank']['rank_id']; ?>)" /></td>
    <td align="center"><a href="javascript:;" onclick="listTable.remove(<?php echo $this->_var['rank']['rank_id']; ?>, '<?php echo $this->_var['lang']['drop_confirm']; ?>')" title="<?php echo $this->_var['lang']['remove']; ?>"><img src="images/icon_drop.gif" border="0" height="16" width="16"></a></td>
  </tr>
  <?php endforeach; endif; unset($_from); ?><?php $this->pop_vars();; ?>
  </table>

<?php if ($this->_var['full_page']): ?>
</div>
<!-- end user ranks list -->
</form>
<script type="Text/Javascript" language="JavaScript">
<!--

onload = function()
{
    // 开始检查订单
    startCheckOrder();
}

//-->
</script>
<?php echo $this->fetch('pagefooter.htm'); ?>
<?php endif; ?>
