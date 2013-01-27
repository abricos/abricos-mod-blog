<?php
/**
 * Схема таблиц модуля
 * @version $Id$
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current; 
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_cat` (
			`catid` INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`parentcatid` INTEGER(10) UNSIGNED NOT NULL DEFAULT '0',
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			`name` VARCHAR(150) NOT NULL,
			`phrase` VARCHAR(250) NOT NULL,
			PRIMARY KEY (`catid`),
			KEY `parentcatid` (`parentcatid`))
		". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_tag` (
			`tagid` INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			`name` VARCHAR(50) NOT NULL,
			`phrase` VARCHAR(100) NOT NULL,
			PRIMARY KEY (`tagid`))
		". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_topcat` (
			`topcat` INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`catid` INTEGER(10) UNSIGNED NOT NULL,
			`topicid` INTEGER(10) UNSIGNED NOT NULL,
			PRIMARY KEY (`topcat`)) 
		". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_topic` (
			`topicid` INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			`name` VARCHAR(250) NOT NULL,
			`title` VARCHAR(250) NOT NULL,
			`metadesc` VARCHAR( 250 ) NOT NULL DEFAULT '',
			`metakeys` VARCHAR( 150 ) NOT NULL DEFAULT '',
			`catid` INTEGER(10) UNSIGNED NOT NULL DEFAULT '0',
			`intro` TEXT NOT NULL,
			`contentid` INTEGER(10) UNSIGNED NOT NULL,
			`userid` INTEGER(10) UNSIGNED NOT NULL,
			`dateline` INTEGER(10) UNSIGNED NOT NULL,
			`dateedit` INTEGER(10) UNSIGNED NOT NULL,
			`datepub` INTEGER(10) UNSIGNED NOT NULL DEFAULT '0',
			`status` INTEGER(2) NOT NULL DEFAULT '0',
			`deldate` INTEGER(10) UNSIGNED NOT NULL DEFAULT '0',
			PRIMARY KEY (`topicid`), KEY `name` (`name`)) 
	". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_toptag` (
			`toptagid` INTEGER(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`topicid` INTEGER(10) UNSIGNED NOT NULL,
			`tagid` INTEGER(10) UNSIGNED NOT NULL,
			PRIMARY KEY (`toptagid`)) 
	". $charset);

}

if ($updateManager->isUpdate('0.4.1')){
	Abricos::GetModule('blog')->permission->Install();
}

if ($updateManager->isUpdate('0.4.4') && !$updateManager->isInstall()){
	
	$db->query_write("
		ALTER TABLE ".$pfx."bg_cat
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
	$db->query_write("UPDATE ".$pfx."bg_cat SET language='ru'");
	
	$db->query_write("
		ALTER TABLE ".$pfx."bg_tag
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
	$db->query_write("UPDATE ".$pfx."bg_tag SET language='ru'");
	
	$db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		ADD `language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
	$db->query_write("UPDATE ".$pfx."bg_topic SET language='ru'");
}


// Рассылка уведомлений
if ($updateManager->isUpdate('0.4.4.1')){

	$db->query_write("
		ALTER TABLE ".$pfx."bg_cat
		ADD `grouplist` varchar(250) NOT NULL DEFAULT '' COMMENT 'Группы пользователей рассылки, идент. через запятую'
	");

	$db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		ADD `scblastuserid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя получивший уведомление',
		ADD `scbcomplete` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1-рассылка закончена'
	");
	// по существующим рассылку делать не нужно
	$db->query_write("UPDATE ".$pfx."bg_topic SET scbcomplete=1");
	
	// отписка пользователя от всех рассылок в блоге
	$db->query_write("
		CREATE TABLE `".$pfx."bg_scbunset` (
		`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
		`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата отписки',
		PRIMARY KEY (`userid`) 
	)". $charset);
	
	// подписка пользователя на новые записи в блоге
	$db->query_write("
		CREATE TABLE `".$pfx."bg_scbblog` (
		  `catid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Блог',
		  `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
		  `pubkey` char(32) NOT NULL DEFAULT '' COMMENT 'Ключ отписки',
		  
		  `scboff` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1-рассылка отключена',
		  `scbcustom` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1-пользователь сам подписался',
			
		UNIQUE KEY `blog` (`userid`,`catid`)
	)". $charset);
	
}

if ($updateManager->isUpdate('0.5')){
	
	// таблица голосов рейтинга категорий
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."bg_catvote (
		`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			
		`voteup` int(2) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ЗА',
		`votedown` int(2) unsigned NOT NULL DEFAULT 0 COMMENT 'Голос ПРОТИВ',

		`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата голосования',
		UNIQUE KEY `user` (`userid`)
	)".$charset);
}


?>