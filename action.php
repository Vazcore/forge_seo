<?php
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
if(isset($_GET['type'])){
	$type = $_GET['type'];
	require_once("api/Core.php");
	$core = new Core();
	$core->start($type);
}else{
	return false;
}