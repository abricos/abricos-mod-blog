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

if (false && $updateManager->isInstall()){

    $db->query_write("
		CREATE TABLE ".$pfx."bg_cat (
			catid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор',
			parentcatid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Родитель',
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Создатель',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			domain VARCHAR(25) NOT NULL default 'Имя домена',
			
			name VARCHAR(150) NOT NULL DEFAULT '' COMMENT 'Имя для URL',
			title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
			descript TEXT NOT NULL COMMENT 'Описание категории',
			
			isprivate TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 - публичнй, 1 -приватный',
			reputation INT(7) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя для создания топика',
			
			topiccount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков',
			membercount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во подписчиков',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			
			PRIMARY KEY (catid),
			KEY parentcatid (parentcatid),
			KEY name (name),
			KEY ld (language, deldate),
			KEY domain (domain)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."bg_topic (
			topicid INT(10) UNSIGNED NOT NULL auto_increment,
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			catid INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Язык',
			userid INT(10) UNSIGNED NOT NULL COMMENT 'Автор',
			
			name VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Имя для URL',
			title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
			
			intro TEXT NOT NULL,
            body TEXT NOT NULL COMMENT 'Запись топика',
			notcomment TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-запретить комментарии',

			isdraft TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-черновик',
			isban TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Наложить запрет на публикацию (админ, модер)',
			isindex TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-топик был выведен на главную',
			autoindex TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 - не менять автоматически статус вывода на главную',
			
			domain VARCHAR(25) NOT NULL default 'Имя домена',
			
			dateline INT(10) UNSIGNED NOT NULL COMMENT 'Дата создания',
			upddate INT(10) UNSIGNED NOT NULL COMMENT 'Дата редактирования',
			pubdate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Дата публикации',
			deldate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Дата удаления',

			commentcount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во комментариев',
			viewcount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во просмотров (учет зарег.польз.)',
			
			metadesc VARCHAR( 250 ) NOT NULL DEFAULT '',
			metakeys VARCHAR( 150 ) NOT NULL DEFAULT '',

            scblastuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя получивший уведомление',
            scbcomplete TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-рассылка закончена',

			PRIMARY KEY (topicid), 
			KEY name (name),
			KEY catid (catid),
			KEY userid (userid),
			KEY domain (domain),
			KEY pubdate (pubdate),
			KEY pub (isdraft, language, deldate)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."bg_tag (
			tagid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			name VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Имя в транслите (for URL)',
			title VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Фраза',
			
			topiccount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков с этим тегом',
			
			PRIMARY KEY (tagid),
			UNIQUE KEY phrase (title, language)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."bg_toptag (
			topicid INT(10) UNSIGNED NOT NULL,
			tagid INT(10) UNSIGNED NOT NULL,
			
			UNIQUE KEY toptag (topicid, tagid),
			KEY topicid (topicid),
			KEY tagid (tagid)
		)".$charset
    );
}

if ($updateManager->isUpdate('0.4.4') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."bg_cat
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
    $db->query_write("UPDATE ".$pfx."bg_cat SET language='ru'");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_tag
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
    $db->query_write("UPDATE ".$pfx."bg_tag SET language='ru'");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
		ADD scblastuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя получивший уведомление',
		ADD scbcomplete TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-рассылка закончена'
	");
    $db->query_write("
        UPDATE ".$pfx."bg_topic 
        SET language='ru',
            scbcomplete=1
    ");
}

if ($updateManager->isUpdate('0.4.4.1')){
    // отписка пользователя от всех рассылок в блоге
    $db->query_write("
		CREATE TABLE ".$pfx."bg_scbunset (
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата отписки',
		PRIMARY KEY (userid)
	)".$charset);
}

if ($updateManager->isUpdate('0.5')){
    // отношение пользователя к категории
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."bg_catuserrole (
			catid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Категория',
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь',
	
			ismember TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Подписан на категорию (блог): 0 - нет, 1 - да',
			
			pubkey char(32) NOT NULL DEFAULT '' COMMENT 'Публичный ключ',
			scboff TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-рассылка отключена',
			
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',

			UNIQUE KEY userrole (catid,userid),
			KEY userid (userid)
		)".$charset
    );

    // отношение пользователя к топику
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."bg_topicuserrole (
			topicid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Топик',
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Пользователь',
	
			iscommentnotify TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Уведомлять о новом комментарии',

			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			viewdate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата просмотра',
			
			UNIQUE KEY userrole (topicid,userid),
			KEY topicid (topicid)			
		)".$charset
    );
}

if ($updateManager->isUpdate('0.5.0') && !$updateManager->isInstall()){

    // Таблица более не нужна
    $db->query_write("DROP TABLE IF EXISTS ".$pfx."bg_topcat");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_cat
			
		CHANGE phrase title VARCHAR(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',

		ADD domain VARCHAR(25) NOT NULL default 'Имя домена',
			
		ADD descript TEXT NOT NULL COMMENT 'Описание категории',
		ADD userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Создатель',
		ADD isprivate TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 - публичнй, 1 -приватный',
		ADD reputation INT(7) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя для создания топика',
		
		ADD topiccount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков',
		ADD membercount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во подписчиков',
		
		ADD dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата создания',
		ADD upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
		ADD deldate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
		
		ADD KEY language (language),
		ADD KEY name (name),
		ADD KEY ld (language, deldate),
		ADD KEY domain (domain)
	");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic

		ADD notcomment TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-запретить комментарии',
		ADD isdraft TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-черновик',
		ADD isban TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Наложить запрет на публикацию (админ, модер)',
		ADD isindex TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1-принудительный вывод на главную',
		ADD autoindex TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 - не менять автоматически статус вывода на главную',

		ADD domain VARCHAR(25) NOT NULL default 'Имя домена',

		ADD commentcount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во комментариев',
		ADD viewcount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во просмотров (учет зарег.польз.)',
		
		CHANGE dateedit upddate INT(10) UNSIGNED NOT NULL COMMENT 'Дата редактирования',
		CHANGE datepub pubdate INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Дата публикации',
		
		ADD KEY catid (catid),
		ADD KEY userid (userid),
		ADD KEY domain (domain),
		ADD KEY pubdate (pubdate),
		ADD KEY pub (isdraft, language, deldate)
	");

    // перенести подписчиков
    $db->query_write("
		INSERT IGNORE INTO ".$pfx."bg_catuserrole
			(catid, userid, ismember, pubkey, dateline, upddate)
		SELECT
			catid, userid, 1, pubkey, ".TIMENOW.", ".TIMENOW."
		FROM ".$pfx."bg_scbblog
		WHERE scboff=0
	");
    $db->query_write("DROP TABLE IF EXISTS".$pfx."bg_scbblog");

    $db->query_write("
        UPDATE ".$db->prefix."bg_cat cat
        SET cat.membercount = IFNULL((
            SELECT count(*) as cnt
            FROM ".$db->prefix."bg_catuserrole ur
            WHERE cat.catid=ur.catid AND ur.ismember=1
            GROUP BY ur.catid
        ), 0)
    ");

    // В предыдущих версиях были дубликаты в регистре, их необходимо удалить
    $db->query_write("RENAME TABLE ".$pfx."bg_tag TO ".$pfx."bg_tag_old");

    $db->query_write("
		CREATE TABLE ".$pfx."bg_tag (
			tagid INT(10) UNSIGNED NOT NULL auto_increment COMMENT 'Идентификатор',
				
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
				
			name VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Имя в транслите (for URL)',
			title VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Фраза',
				
			topiccount INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков с этим тегом',
				
			PRIMARY KEY (tagid),
			UNIQUE KEY phrase (title, language)
		)".$charset
    );

    $db->query_write("
		INSERT IGNORE INTO ".$pfx."bg_tag 
			(tagid, language, name, title)  
		SELECT tagid, language, name, phrase
		FROM ".$pfx."bg_tag_old
	");

    $db->query_write("DROP TABLE ".$pfx."bg_tag_old");

    $db->query_write("
        ALTER TABLE ".$pfx."bg_toptag
        DROP toptagid,
        ADD UNIQUE KEY toptag (topicid, tagid),
        ADD KEY topicid (topicid),
        ADD KEY tagid (tagid)
	");

    $rows = $db->query_write("
		SELECT tt.tagid, tt.topicid
		FROM ".$pfx."bg_toptag tt
		LEFT JOIN ".$pfx."bg_tag t ON tt.tagid=t.tagid
		WHERE isnull(t.tagid)
	");

    while (($row = $this->db->fetch_array($rows))){
        $db->query_write("
			DELETE FROM ".$pfx."bg_toptag
			WHERE tagid=".INTval($row['tagid'])." AND topicid=".INTval($row['topicid'])."
		");
    }

    $db->query_write("
		UPDATE ".$db->prefix."bg_tag
		SET title=LOWER(title)
	");

    // Так как в предыдущих версиях создатель категории не заносился, проставить админа
    // А так же проставить дату создание топика
    $db->query_write("
		UPDATE ".$db->prefix."bg_cat cat
		SET userid=1,
			cat.dateline = (
				SELECT min(t.dateline)
				FROM ".$pfx."bg_topic t
				WHERE cat.catid=t.catid
			)
	");

    // перенести старое значения статуса в значение черновика и удалить поле
    $db->query_write("
		UPDATE ".$pfx."bg_topic
		SET isdraft=IF(status<1, 1, 0),
			isindex=1
	");
    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		DROP  status
	");

    $db->query_write($sql = "
        UPDATE ".$db->prefix."bg_cat cat
        SET cat.topiccount = IFNULL((
            SELECT count(*) as cnt
            FROM ".$db->prefix."bg_topic t
            WHERE cat.catid=t.catid AND t.deldate=0 AND t.isdraft=0 
            GROUP BY t.catid
        ), 0)
    ");

    // обновить информацию по количеству топиков на каждый тег
    $db->query_write("
		UPDATE ".$db->prefix."bg_tag t
		SET topiccount=(
			SELECT count(*)
			FROM ".$db->prefix."bg_toptag tt
			INNER JOIN ".$db->prefix."bg_topic top ON top.topicid=tt.topicid
			WHERE t.tagid=tt.tagid AND top.isdraft=0 AND top.deldate=0
		)
	");
}

if ($updateManager->isUpdate('0.5.3') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		ADD body TEXT NOT NULL COMMENT 'Запись топика'
	");

    $db->query_write("
		UPDATE ".$pfx."bg_topic t
		INNER JOIN ".$pfx."content c ON c.contentid=t.contentid
		SET t.body=c.body
	");

    $db->query_write("
		UPDATE ".$pfx."comment_owner o
		INNER JOIN ".$pfx."bg_topic t ON t.contentid=o.ownerid
		    AND o.ownerModule='blog' AND o.ownerType='content'
		SET
		    o.ownerid=t.topicid,
		    o.ownerType='topic'
	");

    $db->query_write("
		UPDATE ".$pfx."comment_ownerstat o
		INNER JOIN ".$pfx."bg_topic t ON t.contentid=o.ownerid
		    AND o.ownerModule='blog' AND o.ownerType='content'
		SET
		    o.ownerid=t.topicid,
		    o.ownerType='topic'
	");

    $db->query_write("DELETE FROM ".$pfx."content WHERE modman='blog'");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		DROP contentid
	");
}
