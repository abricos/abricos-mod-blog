<?php
return array(
	"modtitle" => "Blog",
	"catblocktitle" => "Categories",
	"cmtmailernewcom" => "New comment on post ",
	"cmtmaileruser" => "User ",
	"cmtmailerwrotecom" => " wrote a commentary on post ",
	"cmtmailerbestwishes" => "All the best,",
	"cmtmaileranswercom" => "The answer to your comment on post ",
	"cmtmaileranswer" => " replied to your comment to post ",
	"cmtmaileranswertext" => "Comment:",
	"pageauthor" => "author",
	"pagecom" => "comments ",
	"pageread" => "Read more",
	"pageor" => " or ",
	"pageopen" => "Open continued here",
	"pagefirstpage" => "On the first page",
	"pagebackpage" => "On the previous page",
	"pageback" => "back",
	"pageforwardpage" => "On the next page",
	"pageforward" => "next",
	"pagelastpage" => "On the last page",
	"commentlivehd" => "Live ",
	"commentliverefresh" => "Refresh",
	"tagblocktags" => "Tags",
	"tagblockalltags" => "All tags",
	"taglisttags" => "Tags (all list)",
	"topicauthor" => "author",
	'brick' => array(
		'catblock' => array(
			"1" => "Categories"
		)
,
		'lasttopic' => array(
			"1" => "Blog in RSS",
			"2" => "Blog posts",
			"3" => "All posts"
		)
,
		'tagblock' => array(
			"1" => "Tags",
			"2" => "All tags"
		)
,
		'templates' => array(
			"1" => "New comment on post \"{v#tl}\"",
			"2" => "<p><b>{v#unm}</b> wrote a commentary on post \"<a href='{v#tpclnk}'>{v#tl}</a>\":</p>
	<blockquote>{v#cmt}</blockquote><br />
	<p>All the best,<br />{v#sitename}</p>
<p style='font-size:10px;font-family: tahoma, verdana, arial, sans-serif;color:#999999;'>
	The message was sent to your {v#email} 
</p>
",
			
			"3" => "Answer to your comment on post \"{v#tl}\"",
			"4" => "<p><b>{v#unm}</b> replied to your comment to post \"<a href='{v#tpclnk}'>{v#tl}</a>\":</p>
	<blockquote>{v#cmt1}</blockquote><br />
	<p>Comment:</p>
	<blockquote>{v#cmt2}</blockquote><br />
	<p>All the best,<br /> {v#sitename}</p>
<p style='font-size:10px;font-family: tahoma, verdana, arial, sans-serif;color:#999999;'>
	The message was sent to your {v#email} 
</p>
				"
		)
,
		'topic' => array(
			"1" => "author"
		)
,
		'topiclist' => array(
			"1" => "author",
			"2" => "comments",
			"3" => "Read more",
			"4" => "or",
			"5" => "Open continued here"
		)

	)
);
?>