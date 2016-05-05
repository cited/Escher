<?php

echo head(array('title'=>'Escher', 'bodyclass'=>'escher browse'));

?>

<div id='primary'>	  	
    <p></p>
</div>

<div class="mapfig-img"><img src="<?php echo  url("escher"); ?>/../../plugins/Escher/image/escher-logo.gif" /></div>

Select Plugin and Click Upload

<?php if (isset($_GET['s']) && $_GET['s'] == 1) : ?>
	<p>Plugin uploaded successfully.</p>
<?php endif ?>
<?php if (isset($_GET['e']) && $_GET['e'] == 1) : ?>
	<p>Plugin directory already exists.</p>
<?php endif ?>
<form action="<?php echo url("escher/index/upload"); ?>" method="post">
	<p>
		<select name="plugin-name">
		<?php foreach($plugins as $v=>$k) { ?>
			<option value="<?php echo $k; ?>"><?php echo $v; ?></option>
		<?php } ?>
		</select>
	</p>
	<input type="submit" value="Upload"/>
</form>


<?php echo foot(); ?>