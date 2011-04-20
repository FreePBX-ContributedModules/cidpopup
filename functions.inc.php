<?php

/* cidpopup_get_config()
 *
 * generate the required gosub() target that will handle our script and provide feedback
 * to the agent (the beep).
**/
function cidpopup_get_config($engine) {
	global $ext;
	switch ($engine) {
		case 'asterisk':
			$context = 'sub-cidpost';
			$exten = 's';
			$ext->add($context, $exten, '', new ext_playback('beep'));
			$ext->add($context, $exten, '', new ext_agi('${DOPOSTAGI}'));
			$ext->add($context, $exten, '', new ext_return(''));
		break;
	}
}

/* cidpopup_hookGet_config()
 *
 * splice into the auto-confirm and auto-blkvm macros to launch our AGI script since these are
 * tacked onto every ringgoup.
**/
function cidpopup_hookGet_config($engine) {
	global $ext;
	switch($engine) {
		case "asterisk":
			// First splice the auto-confirm routines used by ringgoups
			//
			$priority = 'no_such_priority_end';
			$macro = 'macro-auto-confirm';
			$ext->splice($macro,'s',$priority,new ext_gosubif('$["${DOPOSTAGI}" != ""]','sub-cidpost,s,1'));
			$macro = 'macro-auto-blkvm';
			$ext->splice($macro,'s',$priority,new ext_gosubif('$["${DOPOSTAGI}" != ""]','sub-cidpost,s,1'));

			// Now splice each ringgoup that has this set
			//
			$context = 'ext-group';
			$priority = 'skipvmblk';
			$groups = cidpopup_list('ringgroups');
			foreach ($groups as $group) {
				$exten = $group['id'];

				//Sanity check for blank extensions from past bug
				if ($exten == "") {
					continue;
				}

				$popup_info = cidpopup_instance_get($group['postagi']);
				if (!empty($popup_info)) {
					$ext->splice($context,$exten,$priority,new ext_setvar('_DOPOSTAGI',$popup_info['popup_script']));
					$ext->splice($context,$exten,$priority,new ext_setvar('_POSTIPADDR',$popup_info['ipaddr']));
					$ext->splice($context,$exten,$priority,new ext_setvar('_SAVEDCID','${CALLERID(all)}'));
				}
			}
		break;
	}
}

/* cidpopup_display_text()
 *
 * display the select box that is hooked into the Ring Group to choose a PopUp instance.
**/
function cidpopup_display_text($type, $viewing_itemid) {
	$cidpopup_id = cidpopup_get($type,$viewing_itemid);
	$html = '<tr><td colspan="2"><h5>';
	$html .= _("Post Answer CID PopUps");
	$html .= '<hr></h5></td></tr>';
	$html .= '<tr>';
	$html .= '<td valign="top"><a href="#" class="info">';
	$html .= _("PopUp Instance").'<span>'._("Select the PopUp Instance you want executed upon an agent answering a call.").'.</span></a>:</td>';

	$tresults = cidpopup_instance_list();
	$default = (isset($cidpopup_id) ? $cidpopup_id : '');

	$html .= '<td><select name="cidpopup_id" tabindex="<?php echo ++$tabindex;?>">';
	$html .= '<option value="">'._("None")."</option>";
	if (isset($tresults[0])) {
		foreach ($tresults as $tresult) {
			$html .=  '<option value="'.$tresult['cidpopup_id'].'"'.($tresult['cidpopup_id'] == $default ? ' SELECTED' : '').'>'.$tresult['description']."</option>\n";
		}
	}
	$html .= '</select></td></tr>';
	return $html;
}

/* cidpopup_hook_ringgroups()
 *
 * explicitly hook into ringgroups with the PopUp selections
**/
function cidpopup_hook_ringgroups($viewing_itemid, $target_menuid) {
	return cidpopup_display_text('ringgroups', ltrim($viewing_itemid, "GRP-"));
}

/* cidpopup_hookProcess_ringgroups()
 *
 * process the hook after being submitted from the ringgroup page
**/
function cidpopup_hookProcess_ringgroups($viewing_itemid, $request) {

	$viewing_itemid = ltrim($viewing_itemid, "GRP-");

	if (!isset($request['action'])) {
		return;
	}

	$cidpopup_id = isset($request['cidpopup_id'])?$request['cidpopup_id']:'';

	switch ($request['action'])	{
		case 'addGRP':
		case 'edtGRP':	
			cidpopup_update('ringgroups', $viewing_itemid, $cidpopup_id);
		break;
		case 'delGRP':
			cidpopup_del('ringgroups', $request['account']);
		break;
	}
}


/* cidpopup__XXXX() functions
 *
 * These functions are responsible for maintaining the list of Ring Groups
 * (or eventually other module types) that have been hooked with a popup and
 * the associated agi script/IP address instance to use.
**/

function cidpopup_get($type, $xtn) {
	global $db;

	$cidpopup_agi = $db->getOne("SELECT `postagi` FROM `cidpopup` WHERE `type` = '$type' AND `id` = '$xtn'");
	if(DB::IsError($cidpopup_arr)) {
		die_freepbx($cidpopup_arr->getDebugInfo()."<br><br>".'selecting from cidpopup table');	
	}
	return $cidpopup_agi;
}

function cidpopup_update($type, $ext, $cidpopup_id) {
	global $db;

	sql("DELETE FROM `cidpopup` WHERE `type` = '$type' AND `id` = '$ext'");

	$cidpopup_id = $db->escapeSimple(trim($cidpopup_id));
	if ($cidpopup_id != '') {
		$sql = "INSERT INTO `cidpopup` (`type`, `id`, `postagi`) VALUES ('$type','$ext','$cidpopup_id')";
		sql($sql);
	}
}

function cidpopup_del($type, $ext) {
	sql("DELETE FROM `cidpopup` WHERE `type` = '$type' AND `id` = '$ext'");
}

function cidpopup_list($type = '') {
	global $db;

	$sql = "SELECT * FROM `cidpopup`";
	if ($type != '') {
		$sql .= " WHERE `type` = '$type'";
	}
	$cidpopup_agi = sql($sql,'getAll',DB_FETCHMODE_ASSOC);
	return $cidpopup_agi;
}


/* cidpopup_instance_XXXX() functions
 *
 * These functions are responsible for maintaining the list of agi script / IP Address
 * pairs that are presented as a select box into the Ring Groups that use them to choose
 * if and where to send popup messages to.
**/

function cidpopup_instance_add($description, $popup_script, $ipaddr) {
	global $db;
	$sql = "INSERT INTO `cidpopup_instance` (`description`, `popup_script`, `ipaddr`) VALUES (".
		"'".$db->escapeSimple($description)."', ".
		"'".$db->escapeSimple($popup_script)."', ".
		"'".$db->escapeSimple($ipaddr)."')";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function cidpopup_instance_edit($cidpopup_id, $description, $popup_script, $ipaddr) {
	global $db;
	$sql = "UPDATE `cidpopup_instance` SET ".
		"`description` = '".$db->escapeSimple($description)."', ".
		"`popup_script` = '".$db->escapeSimple($popup_script)."', ".
		"`ipaddr` = '".$db->escapeSimple($ipaddr)."' ".
		"WHERE `cidpopup_id` = ".$db->escapeSimple($cidpopup_id);
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function cidpopup_instance_delete($cidpopup_id) {
	global $db;
	$sql = "DELETE FROM `cidpopup_instance` WHERE `cidpopup_id` = ".$db->escapeSimple($cidpopup_id);
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function cidpopup_instance_list() {
	global $db;
	$sql = "SELECT `cidpopup_id`, `description`, `ipaddr`, `popup_script` FROM `cidpopup_instance` ORDER BY `description`";
	$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage()."<br><br>Error selecting from cidpopup_instance in cidpopup_instance_list");	
	}
	return $results;
}

function cidpopup_instance_get($cidpopup_id) {
	global $db;
	$sql = "SELECT `cidpopup_id`, `description`, `ipaddr`, `popup_script` FROM `cidpopup_instance` WHERE `cidpopup_id` = ".$db->escapeSimple($cidpopup_id);
	$row = $db->getRow($sql, DB_FETCHMODE_ASSOC);
	if(DB::IsError($row)) {
		die_freepbx($row->getMessage()."<br><br>Error selecting row from cidpopup_instance in cidpopup_instance_get");	
	}
	return $row;
}

?>
