<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$man = BlogModule::$instance->GetManager();
$query = Abricos::CleanGPC('p', 'query', TYPE_STR);

header('Content-type: text/plain');
$tags = $man->TagListByLikeQuery($query);
for ($i = 0; $i < count($tags); $i++){
    print ($tags[$i]."\n");
}
exit;

?>