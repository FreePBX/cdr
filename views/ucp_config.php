<script>
$('.extension-checkbox').change(function(event){
	var ext = $(this).data('extension');
	var name = $(this).data('name');
	if($(this).is(':checked')) {
		$('#cdr-ext-list').append('<div class="cdr-extensions" data-extension="'+ext+'"><label><input type="checkbox" name="ucp|cdr[]" value="'+ext+'" checked> '+name+' &lt;'+ext+'&gt;</label><br /></div>');
	} else {
		$('.cdr-extensions[data-extension="'+ext+'"]').remove();
	}
});
</script>
<div id="cdr-ext-list" class="extensions-list">
<?php foreach($fpbxusers as $fpbxuser) {?>
	<div class="cdr-extensions" data-extension="<?php echo $fpbxuser['ext']?>">
		<label>
			<input type="checkbox" name="ucp|cdr[]" value="<?php echo $fpbxuser['ext']?>" <?php echo $fpbxuser['selected'] ? 'checked' : '' ?>> <?php echo $fpbxuser['data']['name']?> &lt;<?php echo $fpbxuser['ext']?>&gt;
		</label>
		<br />
	</div>
<?php } ?>
</div>
