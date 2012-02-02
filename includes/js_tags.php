<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @copyright Copyright (C) 2008 Abricos All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$query = Abricos::CleanGPC('p', 'query', TYPE_STR);

header('Content-type: text/plain');

require_once 'dbquery.php';
$rows = BlogQuery::TagAC(Abricos::$db, $query);

while (($row = Abricos::$db->fetch_array($rows))){
	print $row['ph']."\n";
}
exit;
	
?>