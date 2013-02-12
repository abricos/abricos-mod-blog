<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$brick = Brick::$builder->brick;
$manager = Abricos::GetModuleManager('blog');

$rows =  $manager->CategoryBlock();
$lst = "";
$showCount = 0;
while (($row = Abricos::$db->fetch_array($rows))){
	$lst .= Brick::ReplaceVarByData($brick->param->var['t'], array(
		"lnk" => $row['nm'],
		"cnt" => $row['cnt'],
		"c" => $row['ph']
	));
	$showCount++;	
}
unset($brick->param->var['t']);
$brick->param->var['lst'] = $lst;

if ($showCount == 0 && !$brick->param->param['showempty']){
	$brick->content = "";
}

?>