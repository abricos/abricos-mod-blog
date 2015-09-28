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

if ($updateManager->isInstall()){

    $db->query_write("
		CREATE TABLE ".$pfx."bg_cat (
			catid integer(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
			parentcatid integer(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Родитель',
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Создатель',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			domain varchar(25) NOT NULL default 'Имя домена',
			
			name varchar(150) NOT NULL DEFAULT '' COMMENT 'Имя для URL',
			title varchar(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
			descript TEXT NOT NULL COMMENT 'Описание категории',
			
			isprivate tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0 - публичнй, 1 -приватный',
			reputation int(7) unsigned NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя для создания топика',
			
			rating int(10) NOT NULL DEFAULT 0 COMMENT 'Рейтинг',
			voteup int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА',
			votedown int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ',
			votecount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во всего',
			votedate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',
			
			topiccount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков',
			membercount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во подписчиков',
			
			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
			deldate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
			
			PRIMARY KEY (catid),
			KEY parentcatid (parentcatid),
			KEY name (name),
			KEY ld (language, deldate),
			KEY domain (domain)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."bg_topic (
			topicid integer(10) unsigned NOT NULL auto_increment,
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			catid integer(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Язык',
			userid integer(10) unsigned NOT NULL COMMENT 'Автор',
			
			name varchar(250) NOT NULL DEFAULT '' COMMENT 'Имя для URL',
			title varchar(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',
			
			intro TEXT NOT NULL,
			contentid integer(10) unsigned NOT NULL,
			notcomment tinyint(1) NOT NULL DEFAULT 0 COMMENT '1-запретить комментарии',
			
			rating int(10) NOT NULL DEFAULT 0 COMMENT 'Рейтинг',
			voteup int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА',
			votedown int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ',
			votecount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во всего',
			votedate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',

			isdraft tinyint(1) NOT NULL DEFAULT 0 COMMENT '1-черновик',
			isban tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Наложить запрет на публикацию (админ, модер)',
			isindex tinyint(1) NOT NULL DEFAULT 0 COMMENT '1-топик был выведен на главную',
			autoindex tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - не менять автоматически статус вывода на главную',
			
			domain varchar(25) NOT NULL default 'Имя домена',
			
			dateline integer(10) unsigned NOT NULL COMMENT 'Дата создания',
			upddate integer(10) unsigned NOT NULL COMMENT 'Дата редактирования',
			pubdate integer(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата публикации',
			deldate integer(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата удаления',

			commentcount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во комментариев',
			viewcount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во просмотров (учет зарег.польз.)',
			
			metadesc varchar( 250 ) NOT NULL DEFAULT '',
			metakeys varchar( 150 ) NOT NULL DEFAULT '',
			
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
			tagid integer(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
			
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
			
			name varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя в транслите (for URL)',
			title varchar(100) NOT NULL DEFAULT '' COMMENT 'Фраза',
			
			topiccount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков с этим тегом',
			
			PRIMARY KEY (tagid),
			UNIQUE KEY phrase (title, language)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE ".$pfx."bg_toptag (
			topicid integer(10) unsigned NOT NULL,
			tagid integer(10) unsigned NOT NULL,
			
			UNIQUE KEY toptag (topicid, tagid),
			KEY topicid (topicid),
			KEY tagid (tagid)
		)".$charset
    );

}

if ($updateManager->isUpdate('0.4.1')){
    Abricos::GetModule('blog')->permission->Install();
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
		ADD language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык'
	");
    $db->query_write("UPDATE ".$pfx."bg_topic SET language='ru'");
}


// Рассылка уведомлений
if ($updateManager->isUpdate('0.4.4.1')){

    // принудительная подписка групп пользователей
    $db->query_write("
		ALTER TABLE ".$pfx."bg_cat
		ADD grouplist varchar(250) NOT NULL DEFAULT '' COMMENT 'Группы пользователей рассылки, идент. через запятую'
	");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic
		ADD scblastuserid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя получивший уведомление',
		ADD scbcomplete tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1-рассылка закончена'
	");
    // по существующим рассылку делать не нужно
    $db->query_write("UPDATE ".$pfx."bg_topic SET scbcomplete=1");

    // отписка пользователя от всех рассылок в блоге
    $db->query_write("
		CREATE TABLE ".$pfx."bg_scbunset (
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата отписки',
		PRIMARY KEY (userid) 
	)".$charset);

}

if ($updateManager->isUpdate('0.5')){
    // отношение пользователя к категории
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."bg_catuserrole (
			catid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Категория',
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
	
			isadmin tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Админ категории',
			ismoder tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Модератор категории',
			ismember tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Подписан на категорию (блог): 0 - нет, 1 - да',
			
			pubkey char(32) NOT NULL DEFAULT '' COMMENT 'Публичный ключ',
			scboff tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '1-рассылка отключена',
			
			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',

			UNIQUE KEY userrole (catid,userid),
			KEY userid (userid)
		)".$charset
    );

    // отношение пользователя к топику
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."bg_topicuserrole (
			topicid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Топик',
			userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Пользователь',
	
			iscommentnotify tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Уведомлять о новом комментарии',

			dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
			viewdate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата просмотра',
			
			UNIQUE KEY userrole (topicid,userid),
			KEY topicid (topicid)			
		)".$charset
    );

}

if ($updateManager->isUpdate('0.5') && !$updateManager->isInstall()){

    require_once 'dbquery.php';

    // Таблица более не нужна
    $db->query_write("DROP TABLE IF EXISTS".$pfx."bg_topcat");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_cat
			
		CHANGE phrase title varchar(250) NOT NULL DEFAULT '' COMMENT 'Заголовок',

		ADD domain varchar(25) NOT NULL default 'Имя домена',
			
		ADD descript TEXT NOT NULL COMMENT 'Описание категории',
		ADD userid int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Создатель',
		ADD isprivate tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT '0 - публичнй, 1 -приватный',
		ADD reputation int(7) unsigned NOT NULL DEFAULT 0 COMMENT 'Репутация пользователя для создания топика',
		
		ADD rating int(10) NOT NULL DEFAULT 0 COMMENT 'Рейтинг',
		ADD voteup int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА',
		ADD votedown int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ',
		ADD votecount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во всего',
		ADD votedate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',

		ADD topiccount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков',
		ADD membercount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во подписчиков',
		
		ADD dateline int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата создания',
		ADD upddate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
		ADD deldate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата удаления',
		
		ADD KEY language (language),
		ADD KEY name (name),
		ADD KEY ld (language, deldate),
		ADD KEY domain (domain)
	");

    $db->query_write("
		ALTER TABLE ".$pfx."bg_topic

		ADD rating int(10) NOT NULL DEFAULT 0 COMMENT 'Рейтинг',
		ADD voteup int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ЗА',
		ADD votedown int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'ПРОТИВ',
		ADD votecount int(5) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во всего',
		ADD votedate int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Дата пересчета',

		ADD notcomment tinyint(1) NOT NULL DEFAULT 0 COMMENT '1-запретить комментарии',
		ADD isdraft tinyint(1) NOT NULL DEFAULT 0 COMMENT '1-черновик',
		ADD isban tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Наложить запрет на публикацию (админ, модер)',
		ADD isindex tinyint(1) NOT NULL DEFAULT 0 COMMENT '1-принудительный вывод на главную',
		ADD autoindex tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 - не менять автоматически статус вывода на главную',

		ADD domain varchar(25) NOT NULL default 'Имя домена',

		ADD commentcount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во комментариев',
		ADD viewcount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во просмотров (учет зарег.польз.)',
		
		CHANGE dateedit upddate integer(10) unsigned NOT NULL COMMENT 'Дата редактирования',
		CHANGE datepub pubdate integer(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Дата публикации',
		
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

    // обновить информацию о подписчиках
    BlogTopicQuery::CategoryMemberCountUpdate($db);


    // В предыдущих версиях были дубликаты в регистре, их необходимо удалить
    $db->query_write("RENAME TABLE ".$pfx."bg_tag TO ".$pfx."bg_tag_old");

    $db->query_write("
		CREATE TABLE ".$pfx."bg_tag (
			tagid integer(10) unsigned NOT NULL auto_increment COMMENT 'Идентификатор',
				
			language CHAR(2) NOT NULL DEFAULT '' COMMENT 'Язык',
				
			name varchar(50) NOT NULL DEFAULT '' COMMENT 'Имя в транслите (for URL)',
			title varchar(100) NOT NULL DEFAULT '' COMMENT 'Фраза',
				
			topiccount int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Кол-во топиков с этим тегом',
				
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
			WHERE tagid=".intval($row['tagid'])." AND topicid=".intval($row['topicid'])."
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

    BlogTopicQuery::CategoryTopicCountUpdate($db);

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


?>