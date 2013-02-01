<?php
/**
 * Схема таблиц модуля
 * @package Abricos
 * @subpackage Blog
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current; 
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_cat` (
			`catid` integer(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
			`parentcatid` integer(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Родитель',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Создатель',
			
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			`name` varchar(150) NOT NULL DEFAULT '' COMMENT 'Имя для URL',
			`phrase` varchar(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
			
			`isprivate` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0 - публичнй, 1 -приватный',
			`reputation` int(7) unsigned NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя для создания топика',
			
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			`deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			
			PRIMARY KEY (`catid`),
			KEY `parentcatid` (`parentcatid`),
			KEY `name` (`name`),
			KEY `language` (`language`),
			KEY `deldate` (`deldate`)
		)". $charset
	);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_tag` (
			`tagid` integer(10) unsigned NOT NULL auto_increment,
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			`name` varchar(50) NOT NULL,
			`phrase` varchar(100) NOT NULL,
			PRIMARY KEY (`tagid`))
		". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_topcat` (
			`topcat` integer(10) unsigned NOT NULL auto_increment,
			`catid` integer(10) unsigned NOT NULL,
			`topicid` integer(10) unsigned NOT NULL,
			PRIMARY KEY (`topcat`)) 
		". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_topic` (
			`topicid` integer(10) unsigned NOT NULL auto_increment,
			`language` CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			`name` varchar(250) NOT NULL,
			`title` varchar(250) NOT NULL,
			`metadesc` varchar( 250 ) NOT NULL DEFAULT '',
			`metakeys` varchar( 150 ) NOT NULL DEFAULT '',
			`catid` integer(10) unsigned NOT NULL DEFAULT '0',
			`intro` TEXT NOT NULL,
			`contentid` integer(10) unsigned NOT NULL,
			`userid` integer(10) unsigned NOT NULL,
			`dateline` integer(10) unsigned NOT NULL,
			`dateedit` integer(10) unsigned NOT NULL,
			`datepub` integer(10) unsigned NOT NULL DEFAULT '0',
			`status` integer(2) NOT NULL DEFAULT '0',
			`deldate` integer(10) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`topicid`), KEY `name` (`name`)) 
	". $charset);
	
	$db->query_write("
		CREATE TABLE `".$pfx."bg_toptag` (
			`toptagid` integer(10) unsigned NOT NULL auto_increment,
			`topicid` integer(10) unsigned NOT NULL,
			`tagid` integer(10) unsigned NOT NULL,
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
	// отношение пользователя к категории
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."bg_catuserrole (
			`catid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Категория',
			`userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
	
			`isadmin` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Админ категории',
			`ismoder` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Модератор категории',
			`ismember` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Подписан на категорию (блог): 0 - нет, 1 - да',
			
			`dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			`upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',

			UNIQUE KEY `userrole` (`catid`,`userid`),
			KEY `user` (`userid`)
		)".$charset
	);
}


if ($updateManager->isUpdate('0.5') && !$updateManager->isInstall()){
	
	$db->query_write("
		ALTER TABLE ".$pfx."bg_cat
			ADD `userid` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Создатель',
			ADD `isprivate` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0 - публичнй, 1 -приватный',
			ADD `reputation` int(7) unsigned NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя для создания топика',
			ADD `dateline` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			ADD `upddate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			ADD `deldate` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			ADD KEY `language` (`language`),
			ADD KEY `name` (`name`),
			ADD KEY `deldate` (`deldate`)
	");
	
	// Так как в предыдущих версиях создатель категории не заносился, проставить админа
	$db->query_write("
		UPDATE TABLE ".$pfx."bg_cat SET userid=1
	");
	
	// проставить дату создание топика
	$rows = $db->query_write("
		SELECT 
			DISTINCT catid, dateline
		FROM ".$pfx."bg_topic
		ORDER BY dateline
	");
	
	while (($row = $db->fetch_array($rows))){
		$db->query_write("
			UPDATE TABLE ".$pfx."bg_cat 
			SET dateline=".$row['dateline']."
			WHERE catid=".$row['catid']."
		");
	}
}


?>