<?php

$query = Brick::$input->clean_gpc('p', 'query', TYPE_STR);

header('Content-type: text/plain');

$rows = CMSQBlog::TagAC(Brick::$db, $query);

while (($row = Brick::$db->fetch_array($rows))){
	print $row['ph']."\n";
}
exit;
	
?>