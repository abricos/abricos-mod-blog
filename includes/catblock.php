<?php
/**
 * @version $Id: catblock.php 774 2009-04-28 11:39:40Z AKuzmin $
 * @package CMSBrick
 * @copyright Copyright (C) 2008 CMSBrick. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

$brick = Brick::$builder->brick;
$limit = 30;

$rows = CMSQBlog::CatBlock(Brick::$db);
$lst = "";
while (($row = Brick::$db->fetch_array($rows))){
	$t = str_replace('#lnk#', $row['nm'], $brick->param->var['t']);
	$t = str_replace('#cnt#', $row['cnt'], $t);
	$t = str_replace('#c#', $row['ph'], $t);
	$lst .= $t . ' ';
}
unset($brick->param->var['t']);
$brick->param->var['lst'] = $lst;
?>