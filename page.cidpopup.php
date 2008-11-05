<?php 
/** $Id
 * Copyright 2008 Philippe Lindheimer - Astrogen LLC
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'setup';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] :  '';
if (isset($_REQUEST['delete'])) $action = 'delete'; 

$cidpopup_id = isset($_REQUEST['cidpopup_id']) ? $_REQUEST['cidpopup_id'] :  false;
$description = isset($_REQUEST['description']) ? $_REQUEST['description'] :  '';
$ipaddr = isset($_REQUEST['ipaddr']) ? $_REQUEST['ipaddr'] :  '';
$popup_script = isset($_REQUEST['popup_script']) ? $_REQUEST['popup_script'] :  '';
$extdisplay = isset($_REQUEST['extdisplay']) ? $_REQUEST['extdisplay'] :  '';

switch ($action) {
	case 'add':
		cidpopup_instance_add($description, $popup_script, $ipaddr);
		needreload();
		redirect_standard();
	break;
	case 'edit':
		cidpopup_instance_edit($cidpopup_id, $description, $popup_script, $ipaddr);
		needreload();
		redirect_standard('extdisplay');
	break;
	case 'delete':
		cidpopup_instance_delete($cidpopup_id);
		needreload();
		redirect_standard();
	break;
}

?> 
</div>

<div class="rnav"><ul>
<?php 

echo '<li><a href="config.php?display=cidpopup&amp;type='.$type.'">'._('Add CID Popup').'</a></li>';

foreach (cidpopup_instance_list() as $row) {
	echo '<li><a href="config.php?display=cidpopup&amp;type='.$type.'&amp;extdisplay='.$row['cidpopup_id'].'" class="">'.$row['description'].'</a></li>';
}

?>
</ul></div>

<div class="content">

<?php

if ($extdisplay) {
	// load
	$row = cidpopup_instance_get($extdisplay);
	
	$description = $row['description'];
	$ipaddr      = $row['ipaddr'];
	$popup_script   = $row['popup_script'];

	echo "<h2>"._("Edit: ")."$description ($popup_script/$ipaddr)"."</h2>";
} else {
	echo "<h2>"._("Add CID Popup")."</h2>";
}

$helptext = _("This specialized module allows you to specify a destination IP Address of FQDN to be associated with various AGI Scripts that can be launched as part of a post answer action in a ringgroup. The scripts are specialized to deal with various destination CRM systems such as SugarCRM and other future system to provide push based CID PoPup and other CRM data to the agent who answers the call. Once you make an entry including the relevant information, these instances will be available within ringgroups to optionally associated a ringgroup with one of the configured servers so that such CRM data can be displayed to its agents.");
echo $helptext;

$dir = opendir(dirname(__FILE__).'/agi-bin');
$files = Array();
while ($fn = readdir($dir)) {
	if ($fn == '.' || $fn == '..') { 
		continue; 
	} else {
		$files[] = $fn;
	}
}

?>

<form name="editCidPopup" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return checkCidPopup(editCidPopup);">
	<input type="hidden" name="extdisplay" value="<?php echo $extdisplay; ?>">
	<input type="hidden" name="cidpopup_id" value="<?php echo $extdisplay; ?>">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edit' : 'add'); ?>">
	<table>
	<tr><td colspan="2"><h5><?php  echo ($extdisplay ? _("Edit CID Popup Instance") : _("Add CID Popup Instance")) ?><hr></h5></td></tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Description")?>:<span><?php echo _("The descriptive name of this CID Popup instance.")?></span></a></td>
		<td><input size="30" type="text" name="description" value="<?php  echo $description; ?>" tabindex="<?php echo ++$tabindex;?>"></td>
	</tr>
	<tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("IP Address")?>:<span><?php echo _("The IP Address of FQDN of the destination server where PopUp messages will be attempted")?></span></a></td>
		<td><input size="30" type="text" name="ipaddr" value="<?php  echo $ipaddr; ?>" tabindex="<?php echo ++$tabindex;?>"></td>
	</tr>
	<tr>
		<td><a href="#" class="info"><?php echo _("Script to Run")?>:<span><?php echo _("The AGI Script that will be run and passed this IP address")?></span></a></td>
		<td>
			<select name="popup_script" tabindex="<?php echo ++$tabindex;?>">
			<?php 
				$default = (isset($popup_script) ? $popup_script : '');
				foreach ($files as $script) {
					echo '<option value="'.$script.'" '.($script == $default ? 'SELECTED' : '').'>'.$script.'</option>';
				}
			?>		
			</select>		
		</td>
	<tr>
		<td colspan="2"><br><input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>" tabindex="<?php echo ++$tabindex;?>">
			<?php if ($extdisplay) { echo '&nbsp;<input name="delete" type="submit" value="'._("Delete").'">'; } ?>
		</td>		
	</tr>
</table>
</form>

<script cidpopup="javascript">
<!--

function checkCidPopup(theForm) {
	var msgInvalidDescription = "<?php echo _('Invalid description specified'); ?>";

	// set up the Destination stuff
	setDestinations(theForm, '_post_dest');

	// form validation
	defaultEmptyOK = false;	
	if (isEmpty(theForm.description.value))
		return warnInvalid(theForm.description, msgInvalidDescription);

	if (!validateDestinations(theForm, 1, true))
		return false;

	return true;
}
//-->
</script>
