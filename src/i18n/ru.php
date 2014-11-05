<?php
return array(
    "bosmenu" => array(
        "blog" => "Блог"
    ),
	'pagetitle' => array(
		'index' => 'Лучше записи в блогах',
		'indexpage' => 'Страница {v#page} / Лучше записи в блогах',
		'indexnew' => 'Новые записи в блогах',
		'indexnewpage' => 'Страница {v#page} / Новые записи в блогах',
		'pub' => 'Лучше записи в коллективных блогах',
		'pers' => 'Лучше записи в персональных блогах',
		'pubpage' => 'Страница {v#page} / Лучше записи в коллективных блогах',
		'perspage' => 'Страница {v#page} / Лучше записи в персональных блогах',
		'pubnew' => 'Новые записи в коллективных блогах',
		'persnew' => 'Новые записи в персональных блогах',
		'pubnewpage' => 'Страница {v#page} / Новые записи в коллективных блогах',
		'persnewpage' => 'Страница {v#page} / Новые записи в персональных блогах',
		'tag' => '{v#name}',
		'authors' => 'Авторы',
		'author' => 'Автор {v#name}',
		'cat' => '{v#name}',
		'catpage' => 'Страница {v#page} / {v#name}'
	),
	'catperson'=> 'Блог им. {v#unm}',
	'brick' => array(
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
	
	{v#topicintro}
				
	<p>
		<a href='{v#tlnk}'>Читать дальше</a>
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
	)
);
?>