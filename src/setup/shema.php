<?php
/**
 * @package Abricos
 * @subpackage Blog
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isUpdate('0.4.1')){
    Abricos::GetModule('blog')->permission->Install();
}

if ($updateManager->isUpdate('0.6.0')){
    $db->query_write("
		CREATE TABLE ".$pfx."blog (
			blogid INT(10) UNSIGNED NOT NULL auto_increment COMMENT '',
			
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Автор',
			
			blogType ENUM('public', 'personal') DEFAULT 'public' COMMENT 'Тип блога',

            title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
			slug VARCHAR(150) NOT NULL DEFAULT '' COMMENT 'Имя для URL',
			descript TEXT COMMENT 'Описание',
			
			newTopicUserRep INT(7) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ограничение по репутация пользователя для создания топика',
			
			topicCount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков',
			memberCount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во подписчиков',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			
			PRIMARY KEY (blogid),
			KEY blogType (blogType),
			KEY slug (slug),
			KEY topicCount (topicCount),
			KEY memberCount (memberCount),
			KEY deldate (deldate)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."blog_topic (
			topicid INT(10) UNSIGNED NOT NULL auto_increment COMMENT '',
			
			blogid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			
			intro TEXT NOT NULL,
            body TEXT NOT NULL COMMENT 'Запись топика',
            
			metaDesc VARCHAR( 250 ) NOT NULL DEFAULT '',
			metaKeys VARCHAR( 150 ) NOT NULL DEFAULT '',
            
			isDraft TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 - черновик',
			isIndex TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 - топик был выведен на главную',
			autoIndex TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 - принудительно вывести на главную',

			views INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во просмотров',
            
            deliveryUserId INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователя получивший уведомление',
            deliveryCompleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 - рассылка закончена',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			pubdate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата публикации',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			
			PRIMARY KEY (topicid),
			KEY blogid (blogid),
			KEY userid (userid),
			KEY topic (isDraft, isIndex, deldate),
			KEY pubdate (pubdate),
			KEY delivery (deliveryCompleted),
			KEY deldate (deldate)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."blog_tag (
			tagid INT(10) UNSIGNED NOT NULL auto_increment COMMENT '',
			
			title VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Фраза',
			slug VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Имя в транслите (for URL)',
			
			topicCount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков',
			
			PRIMARY KEY (tagid),
			KEY slug (slug)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."blog_tagInTopic (
			tagInTopicId INT(10) UNSIGNED NOT NULL auto_increment COMMENT '',
			
			topicid INT(10) UNSIGNED NOT NULL,
			tagid INT(10) UNSIGNED NOT NULL,
			
			PRIMARY KEY (tagInTopicId),
            UNIQUE KEY tagInTopic (topicid, tagid),
			KEY topicid (topicid),
			KEY tagid (tagid)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."blog_userRole (
			userRoleId INT(10) UNSIGNED NOT NULL auto_increment COMMENT '',
			
			blogid INT(10) UNSIGNED NOT NULL,
			userid INT(10) UNSIGNED NOT NULL,
			
			isMember TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Подписан на блог: 0 - нет, 1 - да',
			deliveryOff TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 - рассылка отключена',
			
			pubKey char(32) NOT NULL DEFAULT '' COMMENT 'Публичный ключ',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			
			PRIMARY KEY (userRoleId),
            UNIQUE KEY userRole (blogid, userid),
			KEY blogid (blogid),
			KEY userid (userid)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."blog_userConfig (
			userid INT(10) UNSIGNED NOT NULL,
			
			delivery ENUM('custom', 'off', 'on') DEFAULT 'custom' COMMENT '',
			
			PRIMARY KEY (userid),
			KEY delivery (delivery)
		)".$charset
    );
}

if ($updateManager->isUpdate('0.6.0') && !$updateManager->isInstall()){
    require_once 'shema-041.php';

    $db->query_write("
		INSERT INTO ".$pfx."blog (
		    blogid, userid, title, slug, descript, newTopicUserRep, 
		    topicCount, memberCount, dateline, upddate, deldate
		)
		SELECT 
		    catid, userid, title, `name`, descript, reputation, 
		    topiccount, membercount, dateline, upddate, deldate
		FROM ".$pfx."bg_cat
    ");

    $db->query_write("
		INSERT INTO ".$pfx."blog_topic (
		    topicid, blogid, userid, intro, body, metaDesc, metaKeys, 
		    isDraft, isIndex, autoIndex, 
		    views, deliveryUserId, deliveryCompleted,
            dateline, upddate, pubdate, deldate
		)
		SELECT 
		    topicid, catid, userid, intro, body, metadesc, metakeys,
		    isdraft, isindex, autoindex, 
            viewcount, scblastuserid, scbcomplete,
            dateline, upddate, pubdate, deldate
		FROM ".$pfx."bg_topic
    ");

    $db->query_write("
		INSERT INTO ".$pfx."blog (
		    userid, title, slug, 
		    dateline, upddate, blogType
		)
		SELECT 
		    t.userid, CONCAT('Блог им.', u.username), u.username, 
		    MIN(t.dateline), MIN(t.dateline), 'personal'  
		FROM ".$pfx."blog_topic t
		INNER JOIN ".$pfx."user u ON t.userid=u.userid
		WHERE t.blogid=0
		GROUP BY t.userid
    ");

    $db->query_write("
		UPDATE ".$pfx."blog_topic t
		INNER JOIN ".$pfx."blog b ON b.userid=t.userid AND b.blogType='personal'
		SET t.blogid=b.blogid
		WHERE t.blogid=0
    ");

    $db->query_write("
		INSERT INTO ".$pfx."blog_tag (
		    tagid, title, slug, topicCount
		)
		SELECT 
		    tagid, title, `name`, topiccount
		FROM ".$pfx."bg_tag
    ");

    $db->query_write("
		INSERT INTO ".$pfx."blog_tagInTopic (
		    topicid, tagid
		)
		SELECT 
		    topicid, tagid
		FROM ".$pfx."bg_toptag
    ");

    $db->query_write("
		INSERT INTO ".$pfx."blog_userRole (
		    blogid, userid, isMember, deliveryOff,
		    pubKey, dateline, upddate
		)
		SELECT 
		    catid, userid, ismember, scboff,
		    pubkey, dateline, upddate
		FROM ".$pfx."bg_catuserrole
    ");

    $db->query_write("
		INSERT INTO ".$pfx."blog_userConfig (
		    userid, delivery
		)
		SELECT 
		    userid, 'off'
		FROM ".$pfx."bg_scbunset
    ");
}
