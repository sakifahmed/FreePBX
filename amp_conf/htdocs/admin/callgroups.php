<?php /* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.


//script to write extensions_additional.conf file from mysql
$wScript1 = rtrim($_SERVER['SCRIPT_FILENAME'],$currentFile).'retrieve_extensions_from_mysql.pl';
	
$action = $_REQUEST['action'];
$extdisplay=$_REQUEST['extdisplay'];
$dispnum = 4; //used for switch on config.php

$goto = $_REQUEST['goto0'];
$account = $_REQUEST['account'];
$grptime = $_REQUEST['grptime'];
$grppre = $_REQUEST['grppre'];


$grplist = array();
if (isset($_REQUEST["grplist"])) {
	$grplist = explode("\n",$_REQUEST["grplist"]);

	if (!$grplist) {
		$grplist = array();
	}
	
	foreach (array_keys($grplist) as $key) {
		//trim it
		$grplist[$key] = trim($grplist[$key]);
		
		// remove invalid chars
		$grplist[$key] = preg_replace("/[^0-9#*]/", "", $grplist[$key]);
		
		// remove blanks
		if ($grplist[$key] == "") unset($grplist[$key]);
	}
	
	// check for duplicates, and re-sequence
	$grplist = array_values(array_unique($grplist));
}



//check if the extension is within range for this user
if (isset($account) && !checkRange($account)){
	echo "<script>javascript:alert('Warning! Extension $account is not allowed for your account.');</script>";
} else {
	//add group
	if ($action == 'addGRP') {
		
		addgroup($account,implode("-",$grplist),$grptime,$grppre,$goto);
		
		exec($wScript1);
		needreload();
	}
	
	//del group
	if ($action == 'delGRP') {
		delextensions('ext-group',ltrim($extdisplay,'GRP-'));
		
		exec($wScript1);
		needreload();
	}
	
	//edit group - just delete and then re-add the extension
	if ($action == 'edtGRP') {
	
		delextensions('ext-group',$account);	
		addgroup($account,implode("-",$grplist),$grptime,$grppre,$goto);
	
		exec($wScript1); 
		needreload();
	
	}
}
?>
</div>

<div class="rnav">
    <li><a id="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo $dispnum?>">Add Ring Group</a><br></li>
<?php 
//get unique ring groups
$gresults = getgroups();

if (isset($gresults)) {
	foreach ($gresults as $gresult) {
		echo "<li><a id=\"".($extdisplay=='GRP-'.$gresult[0] ? 'current':'')."\" href=\"config.php?display=".$dispnum."&extdisplay=GRP-{$gresult[0]}\">Ring Group {$gresult[0]}</a></li>";
	}
}
?>
</div>

<div class="content">
<?php 

		
		if ($action == 'delGRP') {
			echo '<br><h3>Group '.ltrim($extdisplay,'GRP-').' deleted!</h3><br><br><br><br><br><br><br><br>';
		} else {
			
	
			if (!getgroupinfo(ltrim($extdisplay,'GRP-'), $thisGRPtime, $thisGRPprefix, $thisGRP)) {
				//TODO : handle this error better
				//die("Invalid ext-group line in database");
			}
			
			$grplist = explode("-",$thisGRP);

			$delURL = $_REQUEST['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=delGRP';
	?>
			<h2>Ring Group: <?php  echo ltrim($extdisplay,'GRP-'); ?></h2>
<?php 		if ($extdisplay){ ?>
			<p><a href="<?php  echo $delURL ?>">Delete Group <?php  echo ltrim($extdisplay,'GRP-'); ?></a></p>
<?php 		} ?>
			<form name="editGRP" action="<?php  $_REQUEST['PHP_SELF'] ?>" method="post">
			<input type="hidden" name="display" value="<?php echo $dispnum?>">
			<input type="hidden" name="action" value="">
			<table>
			<tr><td colspan="2"><h5><?php  echo ($extdisplay ? 'Edit Ring Group' : 'Add Ring Group') ?><hr></h5></td></tr>
			<tr>
<?php 		if ($extdisplay){ ?>
				<input size="5" type="hidden" name="account" value="<?php  echo ltrim($extdisplay,'GRP-'); ?>">
<?php 		} else { ?>
				<td><a href="#" class="info">group number:<span>The number users will dial to ring extensions in this ring group</span></a></td>
				<td><input size="5" type="text" name="account" value="<?php  echo $gresult[0] + 1; ?>"></td>
<?php 		} ?>
			</tr>
			<tr>
				<td valign="top"><a href="#" class="info">extension list:<span><br>List extensions to ring, one per line.<br><br>You can include an extension on a remote system, or an external number by suffixing a number with a pound (#).  ex:  2448089# would dial 2448089 on the appropriate trunk (see Outbound Routing).<br><br></span></a></td>
				<td valign="top">&nbsp;
					<textarea id="grplist" cols="15" rows="<?php  $rows = count($grplist)+1; echo (($rows < 5) ? 5 : (($rows > 20) ? 20 : $rows) ); ?>" name="grplist"><?php echo implode("\n",$grplist);?></textarea><br>
					
					<input type="submit" style="font-size:10px;" value="Clean & Remove duplicates" />
				</td>
			</tr>
			<tr>
				<td><a href="#" class="info">CID name prefix:<span>You can optionally prefix the Caller ID name when ringing extensions in this group. ie: If you prefix with "Sales:", a call from John Doe would display as "Sales:John Doe" on the extensions that ring.</span></a></td>
				<td><input size="4" type="text" name="grppre" value="<?php  echo $thisGRPprefix ?>"></td>
			</tr><tr>
				<td>ring time (max 60 sec):</td>
				<td><input size="4" type="text" name="grptime" value="<?php  echo $thisGRPtime ?>"></td>
			</tr>
			<tr><td colspan="2"><br><h5>Destination if no answer:<hr></h5></td></tr>

<?php 
//get goto for this group - note priority 2
$goto = getargs(ltrim($extdisplay,'GRP-'),2);
//draw goto selects
echo drawselects('editGRP',$goto,0);
?>
			
			<tr>
			<td colspan="2"><br><h6><input name="Submit" type="button" value="Submit Changes" onclick="checkGRP(editGRP, <?php  echo ($extdisplay ? "'edtGRP'" : "'addGRP'") ?>);"></h6></td>		
			
			</tr>
			</table>
			</form>
<?php 		
		} //end if action == delGRP
		

?>

<?php  //Make sure the bottom border is low enuf
if (isset($queues)) {
	foreach ($gresults as $gresult) {
		echo "<br>";
	}
}
?>




