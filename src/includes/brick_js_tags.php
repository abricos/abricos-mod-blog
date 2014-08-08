<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$man = BlogModule::$instance->GetManager();
$query = Abricos::CleanGPC('p', 'query', TYPE_STR);

header('Content-type: text/plain');
$tags = $man->TagListByLikeQuery($query);
for ($i=0;$i<count($tags);$i++){
	print ($tags[$i]."\n");
}
exit;

?>