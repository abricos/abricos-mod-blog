<?php
return array(
	"modtitle" => "Блог",
	"catblocktitle" => "Рубрики",
	"cmtmailernewcom" => "Новый комментарий к топику ",
	"cmtmaileruser" => "Пользователь ",
	"cmtmailerwrotecom" => " написал комментарий к топику ",
	"cmtmailerbestwishes" => "С наилучшими пожеланиями,",
	"cmtmaileranswercom" => "Ответ на ваш комментарий к топику ",
	"cmtmaileranswer" => " ответил на ваш комментарий к топику ",
	"cmtmaileranswertext" => "Текст комментария:",
	"pageauthor" => "автор текста",
	"pagecom" => "комментарии ",
	"pageread" => "Читать дальше",
	"pageor" => " или ",
	"pageopen" => "Открыть продолжение здесь",
	"pagefirstpage" => "На первую страницу",
	"pagebackpage" => "На страницу назад",
	"pageback" => "назад",
	"pageforwardpage" => "На страницу вперед",
	"pageforward" => "вперед",
	"pagelastpage" => "На последнюю страницу",
	"commentlivehd" => "Прямой эфир ",
	"commentliverefresh" => "Обновить",
	"tagblocktags" => "Метки",
	"tagblockalltags" => "Все метки",
	"taglisttags" => "Метки (весь список)",
	"topicauthor" => "автор текста",
	'brick' => array(
		'catblock' => array(
			"1" => "Рубрики"
		)
,
		'lasttopic' => array(
			"1" => "Записи блога в формате RSS",
			"2" => "Записи в блоге",
			"3" => "Все записи"
		)
,
		'tagblock' => array(
			"1" => "Метки",
			"2" => "Все метки"
		)
,
		'templates' => array(
			"1" => "Новый комментарий к топику \"{v#tl}\"",
			"2" => "<p>Пользователь <b>{v#unm}</b> написал комментарий к топику \"<a href='{v#tpclnk}'>{v#tl}</a>\":</p>
	<blockquote>{v#cmt}</blockquote><br />
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>
<p style='font-size:10px;font-family: tahoma, verdana, arial, sans-serif;color:#999999;'>
	Сообщение было отправлено на ваш {v#email} 
</p>
				",
			"3" => "Ответ на ваш комментарий к топику \"{v#tl}\"",
			"4" => "<p>Пользователь <b>{v#unm}</b> ответил на ваш комментарий к топику \"<a href='{v#tpclnk}'>{v#tl}</a>\":</p>
	<blockquote>{v#cmt1}</blockquote><br />
	<p>Текст комментария:</p>
	<blockquote>{v#cmt2}</blockquote><br />
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>
<p style='font-size:10px;font-family: tahoma, verdana, arial, sans-serif;color:#999999;'>
	Сообщение было отправлено на ваш {v#email} 
</p>
				",
			"5" => "Новый топик в блоге «{v#tl}»",
			"6" => "<p>
		Пользователь <b>{v#unm}</b> опубликовал в блоге «{v#blog}» новый топик - <a href='{v#tlnk}'>{v#topic}</a>
	</p>
	
	<p>С наилучшими пожеланиями,<br />
	 {v#sitename}</p>
	 
	 <p style=\"font-size:11px;font-family: tahoma, verdana, arial, sans-serif;color:#999999;\">
	 	Сообщение было отправлено на ваш {v#email}
		<br /> 
		Вы подписаны на рассылку о новых публикациях в блоге «{v#blog}»
		<br /> 
		<a href='{v#unlnkall}' style=\"color:#3b5998;text-decoration:none;\">Отписаться от рассылки всех сообщений</a> 
		<br /> 
		<a href='{v#unlnkallblog}' style=\"color:#3b5998;text-decoration:none;\">Отписаться от блога «{v#blog}»</a> 
	</p>
	 "
		)
,
		'topic' => array(
			"1" => "автор текста"
		)
,
		'topiclist' => array(
			"1" => "автор текста",
			"2" => "комментарии",
			"3" => "Читать дальше",
			"4" => "или",
			"5" => "Открыть продолжение здесь"
		)

	)
);
?>