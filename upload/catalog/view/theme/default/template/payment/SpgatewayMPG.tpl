<form action="<?php echo $action; ?>" method="post" id="SpgatewayMPG_Form">

  <h3>請按下方 <font color="#d9534f"><?php echo $button_confirm; ?></font><p></p>
    <p>選擇您的付款方式</p><p></p>
  </h3>
  <p></p>
  <p>智付通整合式金流（SSL 256bit加密機制）</p>

    <?php foreach($params as $name => $value){ ?>
		<input type="hidden" name="<?php echo $name?>" value="<?php echo $value?>">
    <?php } ?>

	<div class="buttons">
		<div class="pull-right">
			<input id="SpgatewayMgr" type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
		</div>
	</div>

</form>

<script type="text/javascript">
    $("#SpgatewayMgr").click(function() {
        $.ajax({
            type: 'GET',
            url: '<?php echo $ajaxUrl; ?>',
            success: function() {
                $('#SpgatewayMPG_Form').submit();
            }
        });
    });
</script>
