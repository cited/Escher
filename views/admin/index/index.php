<?php
echo head(array('title' => 'Escher', 'bodyclass' => 'escher browse'));
?>
<style>
    .escher-success{
        background: #3c763d;
        padding: 10px;
        color: #fff;   
        border: 1px solid transparent;
        border-radius: 4px;
    }
    .escher-error{
        background: #a94442;
        padding: 10px;
        color: #fff;  
        border: 1px solid transparent;
        border-radius: 4px;
    }
</style>
<div class="mapfig-img"><img src="<?php echo url("escher"); ?>/../../plugins/Escher/image/escher-logo.gif" /></div>

<?php
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo '<p class="escher-success">' . $_GET['msg'] . '</p>';
} elseif (isset($_GET['error']) && $_GET['error'] == 1) {
    echo '<p class="escher-error">' . $_GET['msg'] . '</p>';
}
?>
<p>Select Plugin and Click Upload</p>

<form action="<?php echo url("escher/index/upload"); ?>" method="post">
    <p>
        <select name="plugin-name">
            <?php foreach ($plugins as $v => $k) { ?>
                <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
            <?php } ?>
        </select>
    </p>
    <input type="submit" value="Upload"/>
</form>


<?php echo foot(); ?>