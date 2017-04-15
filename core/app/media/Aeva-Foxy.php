<?php
/**
 * Show playlists, create album topics, deal with gallery feeds, etc.
 * All of these features were previously exclusive to Aeva Media 2.x (commercial version).
 * Licensed exclusively for use in Wedge.
 *
 * @package Wedge
 * @copyright 2010 René-Gilles Deberdt, wedge.org
 * @license http://wedge.org/license/
 * @author see contributors.txt
 */

/*
	Functions found in Foxy!

	aeva_foxy_playlist()
	aeva_foxy_playlists()
	aeva_foxy_my_playlists()
	aeva_foxy_item_page_playlists($item)
	aeva_foxy_show_playlists($id, $pl)
	aeva_foxy_get_board_list($current_board)
	aeva_foxy_latest_topic($id_owner, $current_album = 0)
	aeva_foxy_create_topic($id_album, $album_name, $board, $lock = false, $mark_as_read = false)
	aeva_foxy_notify_items($album, $items)
	aeva_foxy_remote_image($link)
	aeva_foxy_remote_preview(&$my_file, &$local_file, &$dir, &$name, &$width, &$height)
	aeva_foxy_feed()
	aeva_foxy_get_xml_items()
	aeva_foxy_get_xml_comments()
	aeva_foxy_album($id, $type, $wid = 0, $details = '', $sort = 'm.id_media DESC', $field_sort = 0)
	aeva_foxy_fill_player(&$playlist, $type, &$details, $play = 0, $wid = 470, $hei = 430, $thei = 70)
*/

if (!defined('WEDGE'))
	die('Hacking attempt...');

///////////////////////////////////////////////////////////////////////////////
// USER PLAYLISTS
///////////////////////////////////////////////////////////////////////////////

function aeva_foxy_playlist()
{
	global $context, $txt, $galurl;

	$context['page_title'] = '<span class="mg_item_type">' . $txt['media_playlist'] . '</span>';
	$id = empty($_GET['in']) ? 0 : (int) $_GET['in'];

	if (!isset($_GET['new']) && !isset($_GET['edit']) && !isset($_GET['delete']) && !isset($_GET['from'], $_GET['to']) && !isset($_GET['des']))
	{
		wetem::load('aeva_playlist');
		$context['aeva_foxy_rendered_playlist'] = $id ? aeva_foxy_album($id, 'playl') : aeva_foxy_playlists();
		return;
	}

	$context['aeva_form_url'] = '<URL>?action=media;sa=playlists' . ($id ? ';in=' . $id . ';edit' : ';new') . ';' . $context['session_query'];
	$pname = $txt['media_new_playlist'];
	$pdesc = '';

	if (isset($_GET['delete']))
	{
		// Check the session
		checkSession('get');

		wesql::query('
			DELETE FROM {db_prefix}media_playlists
			WHERE id_playlist = {int:pl}',
			array('pl' => $id)
		);
		redirectexit('action=media;sa=playlists');
	}

	loadSource('Class-Editor');

	if ($id)
	{
		$request = wesql::query('
			SELECT name, description
			FROM {db_prefix}media_playlists
			WHERE id_playlist = {int:pl}',
			array('pl' => $id)
		);
		list ($pname, $pdesc) = wesql::fetch_row($request);
		wesql::free_result($request);

		$pname = wedit::un_preparsecode($pname);
		$pdesc = wedit::un_preparsecode($pdesc);

		// My playlist's contents
		$request = wesql::query('
			SELECT p.id_media, p.play_order, p.description, m.title, a.name
			FROM {db_prefix}media_playlist_data AS p
			INNER JOIN {db_prefix}media_playlists AS pl ON (pl.id_playlist = p.id_playlist)
			INNER JOIN {db_prefix}media_items AS m ON (p.id_media = m.id_media)
			INNER JOIN {db_prefix}media_albums AS a ON (m.album_id = a.id_album)
			WHERE (pl.id_member = {int:me} ' . (we::$is_admin ? 'OR pl.id_member = 0' : 'AND pl.id_member != 0') . ')
			AND p.id_playlist = {int:playlist}
			ORDER BY p.play_order ASC',
			array(
				'me' => MID,
				'playlist' => $id,
			)
		);

		$my_playlist_data = $pos = array();
		while ($row = wesql::fetch_assoc($request))
		{
			$my_playlist_data[$row['play_order']] = array(
				'id' => $row['id_media'],
				'title' => westr::htmlspecialchars($row['title']),
				'description' => $row['description'],
				'album_name' => westr::htmlspecialchars($row['name']),
				'play_order' => $row['play_order'],
			);
			$pos[] = $row['play_order'];
		}
		wesql::free_result($request);

		if (isset($_GET['des']))
		{
			checkSession('get');
			foreach ($my_playlist_data as $m)
				if ($m['id'] == $_GET['des'])
					$desid = $m['id'];
			if (isset($desid, $_POST['txt' . $desid]))
			{
				wesql::query('
					UPDATE {db_prefix}media_playlist_data
					SET description = {string:description}
					WHERE id_playlist = {int:pl} AND id_media = {int:media}',
					array(
						'pl' => $id,
						'media' => $desid,
						'description' => westr::htmlspecialchars(aeva_string($_POST['txt' . $desid], false)),
					)
				);
				redirectexit($context['aeva_form_url']);
			}
		}

		if (isset($_GET['from'], $_GET['to']))
		{
			checkSession('get');
			$from = (int) $_GET['from'];
			$to = (int) $_GET['to'];
			if (isset($my_playlist_data[$from], $my_playlist_data[$to]))
			{
				wesql::query('
					UPDATE {db_prefix}media_playlist_data
					SET id_media = {int:media}, description = {string:description}
					WHERE id_playlist = {int:pl} AND play_order = {int:ord}',
					array(
						'pl' => $id,
						'ord' => $to,
						'media' => $my_playlist_data[$from]['id'],
						'description' => $my_playlist_data[$from]['description'],
					)
				);
				wesql::query('
					UPDATE {db_prefix}media_playlist_data
					SET id_media = {int:media}, description = {string:description}
					WHERE id_playlist = {int:pl} AND play_order = {int:ord}',
					array(
						'pl' => $id,
						'ord' => $from,
						'media' => $my_playlist_data[$to]['id'],
						'description' => $my_playlist_data[$to]['description'],
					)
				);
				redirectexit($context['aeva_form_url']);
			}
		}

		add_js('
	function foxyComment(id)
	{
		$("#foxyDescription" + id).hide();
		$("#foxyComment" + id).show();
		return false;
	}');

		$context['aeva_extra_data'] = '
		<table class="cp4 cs0 centered" style="width: 90%; padding-top: 16px">';

		$curpos = $prev = 0;
		foreach ($my_playlist_data as $m)
		{
			$next = $curpos < count($pos)-1 ? $pos[++$curpos] : 0;
			$context['aeva_extra_data'] .= '
			<tr class="windowbg' . ($curpos % 2 == 0 ? '' : '2') . '"><td class="right">' . $m['play_order'] . '.</td>
			<td><strong><a href="' . $galurl . 'sa=item;in=' . $m['id'] . '">' . $m['title'] . '</a></strong> <a href="#" onclick="return foxyComment(' . $m['id'] . ');"><img src="' . ASSETS . '/aeva/user_comment.png"></a>' . (!empty($m['description']) ? '
			<div id="foxyDescription' . $m['id'] . '">' . parse_bbc($m['description'], 'media-description') . '</div>' : '') . '
			<div class="hide" id="foxyComment' . $m['id'] . '">
				<form action="' . $galurl . 'sa=playlists;in=' . $id . ';edit;des=' . $m['id'] . ';' . $context['session_query'] . '" method="post">
					<textarea name="txt' . $m['id'] . '" cols="60" rows="3">' . $m['description'] . '</textarea>
					<input type="submit" value="' . $txt['media_submit'] . '">
				</form>
			</div></td><td class="center">' .
			(empty($prev) ? '' : '<a href="' . $galurl . 'sa=playlists;in=' . $id . ';from=' . $m['play_order'] . ';to=' . $prev . ';' . $context['session_query'] . '"><span class="sort_up"></span></a>') . '</td><td class="center">' .
			(empty($next) ? '' : '<a href="' . $galurl . 'sa=playlists;in=' . $id . ';from=' . $m['play_order'] . ';to=' . $next . ';' . $context['session_query'] . '"><span class="sort_down"></span></a>') . '</td><td class="center">' .
			'<a href="' . $galurl . 'sa=item;in=' . $m['id'] . ';premove=' . $id . ';redirpl;' . $context['session_query'] . '" style="text-decoration: none"><img src="' . ASSETS . '/aeva/delete.png" style="vertical-align: bottom"> ' . $txt['media_delete_this_item'] . '</a>' .
			'</td></tr>';
			$prev = $curpos > 0 ? $pos[$curpos-1] : 0;
		}

		$context['aeva_extra_data'] .= '
		</table>';
	}

	// Construct the form
	$context['aeva_form'] = array(
		'title' => array(
			'label' => $txt['media_add_title'],
			'fieldname' => 'title',
			'type' => 'text',
			'value' => $pname,
		),
		'desc' => array(
			'label' => $txt['media_add_desc'],
			'subtext' => $txt['media_add_desc_desc'],
			'fieldname' => 'desc',
			'type' => 'textbox',
			'custom' => 'cols="50" rows="6"',
			'value' => $pdesc,
		),
	);

	// Submitting?
	if (isset($_POST['submit_aeva']) && aeva_allowedTo('add_playlists'))
	{
		$name = westr::htmlspecialchars($_POST['title']);
		$desc = westr::htmlspecialchars(aeva_string($_POST['desc'], false, 0));
		wedit::preparsecode($name);
		wedit::preparsecode($desc);

		if ($id)
		{
			// Check the session
			checkSession('get');

			wesql::query('
				UPDATE {db_prefix}media_playlists
				SET name = {string:name}, description = {string:description}
				WHERE id_playlist = {int:pl}',
				array(
					'pl' => $id,
					'name' => $name,
					'description' => $desc
				)
			);
		}
		else
		{
			wesql::insert('',
				'{db_prefix}media_playlists',
				array('name' => 'string', 'description' => 'string', 'id_member' => 'int'),
				array($name, $desc, MID)
			);
			$id = wesql::insert_id();
		}
		redirectexit('action=media;sa=playlists' . ($id ? ';done=' . $id : ''));
	}

	wetem::load('aeva_form');
}

function aeva_foxy_playlists()
{
	global $amSettings, $context, $txt, $galurl;

	$context['page_title'] = $txt['media_playlists'];

	// My playlists -- which I can edit or delete.
	$request = wesql::query('
		SELECT
			pl.id_playlist, pl.name, pl.views, i.title,
			COUNT(pld.id_media) AS items, COUNT(DISTINCT a.id_album) AS albums
		FROM {db_prefix}media_playlists AS pl
		LEFT JOIN {db_prefix}media_playlist_data AS pld ON (pld.id_playlist = pl.id_playlist)
		LEFT JOIN {db_prefix}media_items AS i ON (i.id_media = pld.id_media)
		LEFT JOIN {db_prefix}media_albums AS a ON (i.album_id = a.id_album)
		WHERE pl.id_member = {int:me} ' . (we::$is_admin ? 'OR pl.id_member = 0' : 'AND pl.id_member != 0') . '
		GROUP BY pl.id_playlist, i.title
		ORDER BY pl.id_playlist ASC',
		array('me' => MID)
	);

	$my_playlists = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$my_playlists[$row['id_playlist']] = array(
			'id' => $row['id_playlist'],
			'name' => westr::htmlspecialchars($row['name']),
			'views' => $row['views'],
			'num_items' => $row['items'],
			'num_albums' => $row['albums'],
		);
	}
	wesql::free_result($request);

	// Count how many playlists any user can view (based to their permissions)
	$request = wesql::query('
		SELECT COUNT(DISTINCT pld.id_playlist)
		FROM {db_prefix}media_playlist_data AS pld
		INNER JOIN {db_prefix}media_items AS i ON (i.id_media = pld.id_media)
		INNER JOIN {db_prefix}media_albums AS a ON (i.album_id = a.id_album AND {query_see_album_hidden})',
		array()
	);
	list ($num_items) = wesql::fetch_row($request);
	wesql::free_result($request);

	$start = !empty($_GET['start']) ? (int) $_GET['start'] : 0;
	$context['aeva_page_index'] = template_page_index($galurl . 'sa=playlists', $start, $num_items, 20);

	// List current page of playlists
	$request = wesql::query('
		SELECT
			pld.id_playlist, pl.name, pl.id_member, m.real_name AS owner_name, pl.description, pl.views, i.title,
			COUNT(pld.id_media) AS items, COUNT(DISTINCT a.id_album) AS albums
		FROM {db_prefix}media_playlist_data AS pld
		INNER JOIN {db_prefix}media_playlists AS pl ON (pl.id_playlist = pld.id_playlist)
		INNER JOIN {db_prefix}media_items AS i ON (i.id_media = pld.id_media)
		INNER JOIN {db_prefix}media_albums AS a ON (i.album_id = a.id_album AND {query_see_album_hidden})
		LEFT JOIN {db_prefix}members AS m ON (m.id_member = pl.id_member)
		GROUP BY pld.id_playlist, i.title
		ORDER BY pl.id_playlist ASC
		LIMIT {int:start},20',
		array('start' => $start)
	);

	$playlists = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$playlists[$row['id_playlist']] = array(
			'id' => $row['id_playlist'],
			'name' => westr::htmlspecialchars($row['name']),
			'owner_id' => $row['id_member'],
			'owner_name' => $row['owner_name'],
			'description' => empty($row['description']) ? '' : parse_bbc(westr::cut($row['description'], 150, true, false), 'media-playlist-description-preview'),
			'views' => $row['views'],
			'num_items' => $row['items'],
			'num_albums' => $row['albums'],
		);
	}
	wesql::free_result($request);

	// This query could be cached later on...
	$result = wesql::query('
		SELECT id_album
		FROM {db_prefix}media_albums AS a
		WHERE approved = 1
		AND featured = 0
		LIMIT 1',array()
	);
	$context['show_albums_link'] = wesql::num_rows($result) > 0;
	wesql::free_result($result);

	$pi = '
	<div class="pagesection">
		<nav>' . $txt['pages'] . ': ' . $context['aeva_page_index'] . '</nav>
	</div>';

	$o = '
	<div id="aeva_toplinks">
		<we:title>
			<img src="' . ASSETS . '/aeva/house.png"> <a href="' . $galurl . '">' . $txt['media_home'] . '</a>' . ($context['show_albums_link'] ? ' -
			<img src="' . ASSETS . '/aeva/album.png"> <a href="' . $galurl . 'sa=vua">' . $txt['media_albums'] . '</a>' : '') . (empty($amSettings['disable_playlists']) ? ' -
			<img src="' . ASSETS . '/aeva/playlist.png"> ' . $txt['media_playlists'] : '') . '
		</we:title>
	</div>';

	if (aeva_allowedTo('add_playlists'))
	{
		if (isset($_GET['done']) && (int) $_GET['done'] > 0)
			$o .= '
	<div class="notice warn_watch">' . $txt['media_playlist_done'] . '</div>';

		$o .= '
	<we:title2>
		' . $txt['media_my_playlists'] . '
	</we:title2>
	<table class="aeva_my_playlists w100 cp4 cs0">';
		$res = 0;
		foreach ($my_playlists as $p)
		{
			if ($res == 0)
				$o .= '
	<tr>';
			$o .= '
		<td>
			<strong><a href="' . $galurl . 'sa=playlists;in=' . $p['id'] . '">' . $p['name'] . '</a></strong>
			<br><span class="smalltext">' . sprintf($txt['media_items_from_album' . ($p['num_albums'] == 1 ? '' : 's')], $p['num_items'], $p['num_albums']) . '<br>
			<a href="' . $galurl . 'sa=playlists;in=' . $p['id'] . ';edit;' . $context['session_query'] . '" style="text-decoration: none"><img src="' . ASSETS . '/aeva/camera_edit.png" style="vertical-align: bottom"> ' . $txt['media_edit_this_item'] . '</a>
			<a href="' . $galurl . 'sa=playlists;in=' . $p['id'] . ';delete;' . $context['session_query'] . '" style="text-decoration: none" onclick="return ask(we_confirm, e);"><img src="' . ASSETS . '/aeva/delete.png" style="vertical-align: bottom"> ' . $txt['media_delete_this_item'] . '</a></span>
		</td>';
			if ($res == 3)
				$o .= '
	</tr>';
			$res = ($res + 1) % 4;
		}
		$o .= ($res != 0 ? '
	</tr>' : '') . '
	</table>
	<div style="padding: 8px"><img src="' . ASSETS . '/aeva/camera_add.png"> <b><a href="' . $galurl . 'sa=playlists;new">' . $txt['media_new_playlist'] . '</a></b></div>';
	}
	$o .= '
	<we:title2>
		' . $txt['media_playlists'] . '
	</we:title2>' . $pi;

	if (empty($playlists))
		$o .= $txt['media_tag_no_items'];
	else
	{
		$o .= '<div style="overflow: hidden">';
		foreach ($playlists as $p)
			$o .= '
	<div class="aeva_playlist_list">
		<strong><a href="' . $galurl . 'sa=playlists;in=' . $p['id'] . '">' . $p['name'] . '</a></strong> (' . sprintf($txt['media_items_from_album' . ($p['num_albums'] == 1 ? '' : 's')], $p['num_items'], $p['num_albums']) . ') ' . (empty($p['owner_id']) ? '' : '
		' . $txt['media_by'] . ' <a href="<URL>?action=profile;u=' . $p['owner_id'] . ';area=aeva">' . $p['owner_name'] . '</a>') . (empty($p['description']) ? '' : '
		<div class="mg_desc" style="padding-left: 16px">' . $p['description'] . '</div>') . '
	</div>';
		$o .= '</div>';
		$o .= $pi;
	}

	return $o;
}

function aeva_foxy_my_playlists()
{
	$request = wesql::query('
		SELECT
			pl.id_playlist, pl.name, pl.views, i.title,
			COUNT(pld.id_media) AS items, COUNT(DISTINCT a.id_album) AS albums
		FROM {db_prefix}media_playlists AS pl
		LEFT JOIN {db_prefix}media_playlist_data AS pld ON (pld.id_playlist = pl.id_playlist)
		LEFT JOIN {db_prefix}media_items AS i ON (i.id_media = pld.id_media)
		LEFT JOIN {db_prefix}media_albums AS a ON (i.album_id = a.id_album)
		WHERE pl.id_member = {int:me} ' . (we::$is_admin ? 'OR pl.id_member = 0' : 'AND pl.id_member != 0') . '
		GROUP BY pl.id_playlist, i.title
		ORDER BY pl.id_playlist ASC',
		array('me' => MID)
	);

	$my_playlists = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$my_playlists[$row['id_playlist']] = array(
			'id' => $row['id_playlist'],
			'name' => westr::htmlspecialchars($row['name']),
			'views' => $row['views'],
			'num_items' => $row['items'],
			'num_albums' => $row['albums'],
		);
	}
	wesql::free_result($request);
	return $my_playlists;
}

function aeva_foxy_item_page_playlists($item)
{
	global $context, $galurl;

	// Any playlist being deleted?
	if (isset($_GET['premove']))
	{
		checkSession('get');

		wesql::query('
			DELETE FROM {db_prefix}media_playlist_data
			WHERE id_playlist = {int:playlist} AND id_media = {int:media}',
			array(
				'playlist' => (int) $_GET['premove'],
				'media' => $item
			)
		);
		if (isset($_GET['redirpl']))
			redirectexit($galurl . 'sa=playlists;in=' . $_GET['premove'] . ';edit;' . $context['session_query']);
	}

	// Any playlist being added?
	if (isset($_POST['add_to_playlist']))
	{
		$pl = (int) $_POST['add_to_playlist'];
		$items = (array) $item;
		if (!aeva_allowedTo('add_playlists') || $pl <= 0)
			fatal_lang_error('media_edit_denied');

		// Make sure this playlist belongs to self...
		if (!we::$is_admin)
		{
			$request = wesql::query('
				SELECT id_member
				FROM {db_prefix}media_playlists
				WHERE id_playlist = {int:pl}',
				array('pl' => $pl)
			);
			list ($owner) = wesql::fetch_row($request);
			wesql::free_result($request);
		}

		if (we::$is_admin || $owner == MID)
			foreach ($items as $it)
				wesql::insert('ignore',
					'{db_prefix}media_playlist_data',
					array('id_playlist' => 'int', 'id_media' => 'int'),
					array($pl, $it)
				);
		if (is_array($item))
			return;
	}

	// My playlists -- which I can edit or delete.
	$my_playlists = aeva_foxy_my_playlists();

	// All playlists that contain the current item
	$request = wesql::query('
		SELECT pld.id_playlist, pl.name, pl.id_member, m.real_name AS owner_name, COUNT(pld2.id_media) AS items
		FROM {db_prefix}media_playlist_data AS pld
		INNER JOIN {db_prefix}media_playlists AS pl ON (pl.id_playlist = pld.id_playlist)
		INNER JOIN {db_prefix}media_playlist_data AS pld2 ON (pld2.id_playlist = pld.id_playlist)
		INNER JOIN {db_prefix}media_items AS i ON (i.id_media = pld.id_media)
		LEFT JOIN {db_prefix}members AS m ON (m.id_member = pl.id_member)
		WHERE pld.id_media = {int:media}
		GROUP BY pld.id_playlist
		ORDER BY pl.id_playlist ASC',
		array('media' => $item)
	);

	$playlists = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$playlists[$row['id_playlist']] = array(
			'id' => $row['id_playlist'],
			'name' => westr::htmlspecialchars($row['name']),
			'owner_id' => $row['id_member'],
			'owner_name' => $row['owner_name'],
			'num_items' => $row['items'],
		);
		unset($my_playlists[$row['id_playlist']]);
	}
	wesql::free_result($request);

	return array('mine' => $my_playlists, 'current' => $playlists);
}

///////////////////////////////////////////////////////////////////////////////
// LINKED TOPICS / NOTIFICATIONS
///////////////////////////////////////////////////////////////////////////////

function aeva_foxy_get_board_list($current_board)
{
	global $txt;

	$topic_boards = $topic_cats = array();
	$write_boards = boardsAllowedTo('post_new');
	if (!empty($write_boards))
	{
		$request = wesql::query('
			SELECT b.id_board, b.name, b.child_level, c.name AS cat_name, c.id_cat
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
			WHERE {query_see_board}' . (!in_array(0, $write_boards) ? '
				AND b.id_board IN ({array_int:write_boards})' : '') . '
			ORDER BY b.board_order',
			array('write_boards' => $write_boards)
		);

		while ($row = wesql::fetch_assoc($request))
		{
			if (!isset($topic_cats[$row['id_cat']]))
				$topic_cats[$row['id_cat']] = array (
					'name' => strip_tags($row['cat_name']),
					'boards' => array(),
				);

			$topic_cats[$row['id_cat']]['boards'][] = array(
				'id' => $row['id_board'],
				'name' => westr::cut(strip_tags($row['name']), 50) . '&nbsp;',
				'category' => strip_tags($row['cat_name']),
				'child_level' => $row['child_level'],
				'selected' => !empty($current_board) && $current_board == $row['id_board'],
			);
		}
		wesql::free_result($request);

		if (empty($txt['media']))
			loadLanguage('Media');

		$topic_boards[0] = array($txt['media_no_topic_board'], $current_board === 0);
		foreach ($topic_cats as $c => $category)
		{
			$topic_boards['begin_' . $c] = array($category['name'], false, 'begin');
			foreach ($category['boards'] as $board)
				$topic_boards[$board['id']] = array(($board['child_level'] > 0 ? str_repeat('==', $board['child_level']-1) . '=&gt; ' : '') . $board['name'], $board['selected']);
			$topic_boards['end_' . $c] = array($category['name'], false, '');
		}
	}

	unset($topic_cats);
	return $topic_boards;
}

// Get the board ID and locked status for the last linked topic created by Foxy!
// If we're editing the last created album, use linked topic from the album before.
function aeva_foxy_latest_topic($id_owner, $current_album = 0)
{
	$request = wesql::query('
		SELECT t.id_board, t.locked
		FROM {db_prefix}topics AS t
		RIGHT JOIN {db_prefix}media_albums AS a ON a.id_topic = t.id_topic
		WHERE a.album_of = {int:member}' . ($current_album > 0 ? ' AND a.id_album != {int:album}' : '') . '
		ORDER BY a.id_album DESC
		LIMIT 1',
		array(
			'member' => (int) $id_owner,
			'album' => (int) $current_album,
		)
	);

	if (wesql::num_rows($request) == 0)
		return array(0, 0);

	list ($id_board, $locked) = wesql::fetch_row($request);
	wesql::free_result($request);

	return array($id_board, $locked);
}

// Create a linked topic based on requested details. Neat thing: the locked status
// is inherited from your previous linked topic's, if any.
function aeva_foxy_create_topic($id_album, $album_name, $board, $lock = false, $mark_as_read = false)
{
	global $txt;

	loadSource('Subs-Post');

	if (empty($txt['media']))
		loadLanguage('Media');

	// Show an album playlist, with all details except its name (because it's already in the subject.)
	$msgOptions = array(
		'subject' => $txt['media_topic'] . ': ' . $album_name,
		'body' => '[media id=' . $id_album . ' type=media_album details=no_name]',
		'icon' => 'xx',
		'smileys_enabled' => 1,
	);
	$topicOptions = array(
		'board' => $board,
		'lock_mode' => $lock,
		'mark_as_read' => $mark_as_read,
	);
	$posterOptions = array(
		'id' => MID,
		'update_post_count' => true,
	);

	createPost($msgOptions, $topicOptions, $posterOptions);

	$id_topic = isset($topicOptions['id']) ? $topicOptions['id'] : 0;

	wesql::query('
		UPDATE {db_prefix}media_albums
		SET id_topic = {int:topic}
		WHERE id_album = {int:album}',
		array(
			'topic' => (int) $id_topic,
			'album' => (int) $id_album,
		)
	);

	return $id_topic;
}

function aeva_foxy_notify_items($album, $items)
{
	global $txt;

	$request = wesql::query('
		SELECT a.name, a.id_topic, t.id_board
		FROM {db_prefix}media_albums AS a
		INNER JOIN {db_prefix}topics AS t ON (t.id_topic = a.id_topic)
		WHERE a.id_album = {int:album}',
		array('album' => (int) $album)
	);
	list ($name, $linked_topic, $linked_board) = wesql::fetch_row($request);
	wesql::free_result($request);

	if (empty($linked_topic) || empty($linked_board))
		return;

	loadSource('Subs-Post');

	if (empty($txt['media']))
		loadLanguage('Media');

	$msgOptions = array(
		'subject' => $txt['media_topic'] . ': ' . westr::htmlspecialchars(aeva_string($name, false)),
		'body' => '[media id=' . implode(',', $items) . ' type=box]',
		'icon' => 'xx',
		'smileys_enabled' => 1,
	);
	$topicOptions = array(
		'id' => $linked_topic,
		'board' => $linked_board,
	);
	$posterOptions = array(
		'id' => MID,
		'update_post_count' => true,
	);

	createPost($msgOptions, $topicOptions, $posterOptions);
}

///////////////////////////////////////////////////////////////////////////////
// EMBEDDING REMOTELY HOSTED PICTURES
///////////////////////////////////////////////////////////////////////////////

function aeva_foxy_remote_image($link)
{
	global $force_id, $embed_folder, $embed_album;

	$force_id = false;
	$embed_folder = '';
	$embed_album = isset($_REQUEST['album']) ? (int) $_REQUEST['album'] : 0;

	$id = aeva_download_thumb($link, basename(urldecode(rtrim($link, '/'))), true);
	return is_array($id) ? $id : false;
}

function aeva_foxy_remote_preview(&$my_file, &$local_file, &$dir, &$name, &$width, &$height)
{
	global $amSettings, $embed_album;

	if (!($resizedpic = $my_file->createThumbnail($local_file . '1.jpg', min($width, $amSettings['max_preview_width']), min($height, $amSettings['max_preview_height']))))
		return 0;

	list ($pwidth, $pheight) = $resizedpic->getSize();
	$fsize = $resizedpic->getFileSize();
	$resizedpic->close();

	$pwidth = empty($pwidth) ? $amSettings['max_preview_width'] : $pwidth;
	$pheight = empty($pheight) ? $amSettings['max_preview_height'] : $pheight;

	$id_preview = aeva_insertFileID(
		0, $fsize, 'preview_' . $name . '.jpg', $pwidth, $pheight,
		substr($dir, strlen($amSettings['data_dir_path']) + 1), $embed_album
	);

	@rename($local_file . '1.jpg', $dir . '/' . aeva_getEncryptedFilename('preview_' . $name . '.jpg', $id_preview));

	return $id_preview;
}

///////////////////////////////////////////////////////////////////////////////
// MEDIA FEEDS
///////////////////////////////////////////////////////////////////////////////

function aeva_foxy_feed()
{
	global $context, $txt, $settings, $amSettings, $query_this;

	$amSettings['max_feed_items'] = !isset($amSettings['max_feed_items']) ? 10 : $amSettings['max_feed_items'];
	if (empty($amSettings['max_feed_items']))
		return;

	loadSource('Feed');

	// Default to latest 10. No more than 255, please.
	$_GET['limit'] = empty($_GET['limit']) || (int) $_GET['limit'] < 1 ? 10 : min((int) $_GET['limit'], $amSettings['max_feed_items']);
	$type = !isset($_GET['type']) || $_GET['type'] != 'comments' ? 'items' : 'comments';

	// Handle the cases where an album, albums, or other things are asked for.
	$query_this = 1;
	if (isset($_REQUEST['user']))
	{
		$_REQUEST['user'] = explode(',', $_REQUEST['user']);
		foreach ($_REQUEST['user'] as $i => $c)
			$_REQUEST['user'][$i] = (int) $c;

		if (count($_REQUEST['user']) == 1 && !empty($_REQUEST['user'][0]))
		{
			$request = wesql::query('
				SELECT real_name
				FROM {db_prefix}members
				WHERE id_member = {int:mem}
				LIMIT 1',
				array('mem' => (int) $_REQUEST['user'][0])
			);
			list ($feed_title) = wesql::fetch_row($request);
			wesql::free_result($request);

			$feed_title = ' - ' . strip_tags($feed_title);
		}

		$query_this = $type == 'items' ? (isset($_REQUEST['albums']) ? 'a.album_of IN ({array_int:memlist})' : 'm.id_member IN ({array_int:memlist})') : 'c.id_member IN ({array_int:memlist})';
	}
	elseif (!empty($_REQUEST['item']) && $type == 'comments')
	{
		$_REQUEST['item'] = explode(',', $_REQUEST['item']);
		foreach ($_REQUEST['item'] as $i => $b)
			$_REQUEST['item'][$i] = (int) $b;

		$siz = count($_REQUEST['item']);
		$request = wesql::query('
			SELECT m.id_media, m.title
			FROM {db_prefix}media_items AS m
			INNER JOIN {db_prefix}media_albums AS a ON (a.id_album = m.album_id)
			WHERE (m.id_media ' . ($siz == 1 ? '= {int:media}' : 'IN ({array_int:media})') . ')
				AND {query_see_album}
			LIMIT ' . $siz,
			array('media' => $siz == 1 ? $_REQUEST['item'][0] : $_REQUEST['item'])
		);

		// Either the item specified doesn't exist or you have no access.
		if (wesql::num_rows($request) == 0)
			fatal_lang_error('media_accessDenied', false);

		$items = array();
		while ($row = wesql::fetch_assoc($request))
		{
			if ($siz == 1)
				$feed_title = ' - ' . strip_tags($row['title']);
			$items[] = $row['id_media'];
		}
		wesql::free_result($request);

		if (!empty($items))
			$query_this = count($items) == 1 ? 'c.id_media = ' . $items[0] : 'c.id_media IN (' . implode(', ', $items) . ')';
	}
	elseif (!empty($_REQUEST['album']))
	{
		$_REQUEST['album'] = explode(',', $_REQUEST['album']);
		foreach ($_REQUEST['album'] as $i => $b)
			$_REQUEST['album'][$i] = (int) $b;

		$siz = count($_REQUEST['album']);
		$request = wesql::query('
			SELECT a.id_album, a.name
			FROM {db_prefix}media_albums AS a
			WHERE (a.id_album ' . ($siz == 1 ? '= {int:album}' : 'IN ({array_int:album})') . (isset($_REQUEST['children']) ? '
				OR a.parent ' . ($siz == 1 ? '= {int:album}' : 'IN ({array_int:album})') : '') . ')
				AND {query_see_album}' . (isset($_REQUEST['children']) ? '' : '
			LIMIT ' . $siz),
			array('album' => $siz == 1 ? $_REQUEST['album'][0] : $_REQUEST['album'])
		);

		// Either the album specified doesn't exist or you have no access.
		if (wesql::num_rows($request) == 0)
			fatal_lang_error('media_accessDenied', false);

		$albums = array();
		while ($row = wesql::fetch_assoc($request))
		{
			if ($siz == 1 && (!isset($_REQUEST['children']) || $row['id_album'] == $_REQUEST['album'][0]))
				$feed_title = ' - ' . strip_tags($row['name']) . (isset($_REQUEST['children']) ? ' ' . $txt['media_foxy_and_children'] : '');
			$albums[] = $row['id_album'];
		}
		wesql::free_result($request);

		if (!empty($albums))
		{
			if ($type == 'items')
				$query_this = count($albums) == 1 ? 'a.id_album = ' . $albums[0] : 'a.id_album IN (' . implode(', ', $albums) . ')';
			else
				$query_this = count($albums) == 1 ? 'c.id_album = ' . $albums[0] : 'c.id_album IN (' . implode(', ', $albums) . ')';
		}
	}
	else
		$query_this = '{query_see_album}';

	// We only want some information, not all of it.
	$cachekey = array($_GET['limit']);
	foreach (array('album', 'albums', 'user') as $var)
		if (isset($_REQUEST[$var]))
			$cachekey[] = $_REQUEST[$var];
	$cachekey = md5(serialize($cachekey) . (!empty($query_this) ? $query_this : ''));

	// Get the associative array representing the xml.
	if ($cache_it = we::$is_guest && !empty($settings['cache_enable']) && $settings['cache_enable'] >= 3)
		$xml = cache_get_data('aevafeed:' . $cachekey, 240);
	if (empty($xml))
	{
		$xml = call_user_func('aeva_foxy_get_xml_' . $type);
		if ($cache_it)
			cache_put_data('aevafeed:' . $cachekey, $xml, 240);
	}

	$feed_title = westr::htmlspecialchars(strip_tags($context['forum_name'])) . ' - ' . $txt['media_gallery'] . (isset($feed_title) ? $feed_title : '');

	// Support for PrettyURLs rewriting
	if (!empty($settings['pretty_filters']['actions']))
	{
		$insideurl = preg_quote(SCRIPT, '~');
		$context['pretty']['patterns'][] = '~(?<=<link>|<guid>)' . $insideurl . '([?;&](action)=[^#<]+)~';
	}

	clean_output();
	header('Content-Type: application/rss+xml; charset=UTF-8');

	// First, output the xml header.
	echo '<?xml version="1.0" encoding="UTF-8"?' . '>
<rss version="2.0" xml:lang="', strtr($txt['lang_locale'], '_', '-'), '">
	<channel>
		<title><![CDATA[', $feed_title, ']]></title>
		<link><URL>?action=media</link>
		<description><![CDATA[', !empty($txt['media_feed_desc']) ? $txt['media_feed_desc'] : '', ']]></description>';

	// Output all of the associative array, start indenting with 2 tabs, and name everything "item".
	dumpTags($xml, 2, 'item', 'rss2');

	// Output the footer of the XML.
	echo '
	</channel>
</rss>';

	obExit(false);
}

function aeva_foxy_get_xml_items()
{
	global $settings, $galurl, $amSettings, $query_this, $txt;

	$postmod = isset($settings['postmod_active']) ? $settings['postmod_active'] : false;
	$request = wesql::query('
		SELECT
			m.id_media, m.title, m.description, m.type, m.id_member, m.member_name, m.time_added,
			m.album_id, a.name, a.hidden, m.id_thumb, f.filename, f.directory
		FROM {db_prefix}media_items AS m
			INNER JOIN {db_prefix}media_albums AS a ON (a.id_album = m.album_id)
			LEFT JOIN {db_prefix}media_files AS f ON (f.id_file = m.id_thumb)
		WHERE ' . $query_this . ($postmod ? '
			AND m.approved = 1' : '') . '
		ORDER BY m.id_media DESC
		LIMIT {int:limit}',
		array(
			'limit' => $_GET['limit'],
			'memlist' => isset($_REQUEST['user']) ? $_REQUEST['user'] : '',
		)
	);

	$data = array();
	$clearurl = $amSettings['data_dir_url'];

	while ($row = wesql::fetch_assoc($request))
	{
		$thumb_url = isset($row['directory']) && !empty($amSettings['clear_thumbnames']) ? $clearurl . '/' . str_replace('%2F', '/', urlencode($row['directory'])) . '/' . aeva_getEncryptedFilename($row['filename'], $row['id_thumb'], true) : $galurl . 'sa=media;in=' . $row['id_media'] . ';thumb';
		$item = '<p><a href="' . $galurl . 'sa=item;in=' . $row['id_media'] . '"><img src="' . $thumb_url . '" style="padding: 3px; margin: 3px"></a></p>'
			. "\n" . '<p>' . $txt['media_by'] . ' <a href="<URL>?action=profile;u=' . $row['id_member'] . ';area=aeva">' . $row['member_name'] . '</a>'
			. ($row['hidden'] ? '' : ' ' . $txt['media_in_album'] . ' <a href="' . $galurl . 'sa=album;in=' . $row['album_id'] . '">' . $row['name'] . '</a>') . '</p>';
		$data[] = array(
			'title' => cdata_parse($row['title']),
			'link' => '<URL>?action=media;sa=item;in=' . $row['id_media'],
			'description' => cdata_parse($item),
			'author' => cdata_parse($row['member_name']),
			'category' => cdata_parse($row['name']),
			'pubDate' => gmdate('D, d M Y H:i:s \G\M\T', $row['time_added']),
			'guid' => '<URL>?action=media;sa=item;in=' . $row['id_media'],
		);
	}

	wesql::free_result($request);

	return $data;
}

function aeva_foxy_get_xml_comments()
{
	global $settings, $galurl, $amSettings, $query_this, $txt;

	$postmod = isset($settings['postmod_active']) ? $settings['postmod_active'] : false;
	$request = wesql::query('
		SELECT
			c.id_comment, c.id_member, c.id_media, c.id_album, c.message, c.posted_on,
			m.title, mem.member_name, a.name, a.hidden
		FROM {db_prefix}media_comments AS c
			INNER JOIN {db_prefix}media_items AS m ON (m.id_media = c.id_media)
			INNER JOIN {db_prefix}media_albums AS a ON (a.id_album = m.album_id)
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = c.id_member)
			LEFT JOIN {db_prefix}media_files AS f ON (f.id_file = m.id_thumb)
		WHERE ' . $query_this . ($postmod ? '
			AND c.approved = 1' : '') . '
		ORDER BY c.id_comment DESC
		LIMIT {int:limit}',
		array(
			'limit' => $_GET['limit'],
			'memlist' => isset($_REQUEST['user']) ? $_REQUEST['user'] : '',
		)
	);

	$data = array();
	$clearurl = $amSettings['data_dir_url'];
	while ($row = wesql::fetch_assoc($request))
	{
		$item = '<p>' . ($row['hidden'] ? '' : $txt['media_comment_in'] . ' <a href="' . $galurl . 'sa=album;in=' . $row['id_album'] . '">' . $row['name'] . '</a> ') . $txt['media_by']
			. ' <a href="<URL>?action=profile;u=' . $row['id_member'] . ';area=aeva">' . $row['member_name'] . '</a></p>' . "\n" . '<p>'
			. westr::cut($row['message'], 300, true, false, true, true) . '</p>';

		$data[] = array(
			'title' => cdata_parse($row['title']),
			'link' => '<URL>?action=media;sa=item;in=' . $row['id_media'] . '#com' . $row['id_comment'],
			'description' => cdata_parse($item),
			'author' => cdata_parse($row['member_name']),
			'category' => cdata_parse($row['name']),
			'pubDate' => gmdate('D, d M Y H:i:s \G\M\T', $row['posted_on']),
			'guid' => '<URL>?action=media;sa=item;in=' . $row['id_media'] . '#com' . $row['id_comment'],
		);
	}

	wesql::free_result($request);

	return $data;
}

///////////////////////////////////////////////////////////////////////////////
// PLAYLISTS
///////////////////////////////////////////////////////////////////////////////

function aeva_foxy_album($id, $type, $wid = 0, $details = '', $sort = 'm.id_media DESC', $field_sort = 0)
{
	global $context, $amSettings, $txt, $galurl;

	$det = empty($details) || $details[0] == 'all' ? 'all' : ($details[0] == 'no_name' ? 'no_name' : '');
	if ($det == 'all' || $det == 'no_name')
		$details = $det == 'all' ? array('name', 'description', 'playlists', 'votes') : array('description', 'playlists', 'votes');

	if (empty($txt['media']))
		loadLanguage('Media');

	$box = $exts = '';
	$pwid = !empty($wid) ? $wid : (!empty($amSettings['audio_player_width']) ? min($amSettings['max_preview_width'], max(100, (int) $amSettings['audio_player_width'])) : 500);

	// All extensions hopefully supported by most browsers.
	$all_types = $type == 'media' || $type == 'playl' || $type == 'ids';
	if ($type == 'audio' || $all_types)
		$exts .= "'mp3', 'm4a', ";
	if ($type == 'video' || $all_types)
		$exts .= "'mp4', 'm4v', 'f4v', 'flv', '3gp', '3g2', ";
	if ($type == 'photo' || $all_types)
		$exts .= "'jpg', 'jpe', 'peg', 'png', 'gif', ";

	if (empty($exts))
		return;

	if ($type == 'playl')
	{
		wesql::query('
			UPDATE {db_prefix}media_playlists
			SET views = views + 1
			WHERE id_playlist = {int:playlist}',
			array('playlist' => (int) $id)
		);

		$request = wesql::query('
			SELECT
				m.id_media, m.title, m.type, f.meta, m.description, m.album_id, a.name AS album_name, a.hidden, a.id_album,
				rating, voters, m.id_member, f.height, f.filename, i.width AS icon_width, p.height AS preview_height,
				t.filename AS tf, t.id_file AS id_thumb, t.directory AS td, i.transparency
			FROM {db_prefix}media_items AS m
			INNER JOIN {db_prefix}media_playlist_data AS pl ON (pl.id_media = m.id_media AND pl.id_playlist = {int:playlist})
			INNER JOIN {db_prefix}media_albums AS a ON (m.album_id = a.id_album)
			INNER JOIN {db_prefix}media_files AS f ON (f.id_file = m.id_file)
			LEFT JOIN {db_prefix}media_files AS p ON (p.id_file = IF(m.id_preview = 0, IF(m.id_thumb < 5, a.icon, m.id_thumb), m.id_preview))
			LEFT JOIN {db_prefix}media_files AS t ON (t.id_file = IF(m.id_thumb < 5 AND a.icon > 4, a.icon, m.id_thumb))
			LEFT JOIN {db_prefix}media_files AS i ON (i.id_file = a.icon)
			WHERE LOWER(RIGHT(f.filename, 3)) IN ({raw:extensions}) AND {query_see_album_hidden}
			ORDER BY pl.play_order ASC',
			array(
				'playlist' => (int) $id,
				'extensions' => substr($exts, 0, -2),
			)
		);
	}
	elseif ($type == 'ids')
	{
		$request = wesql::query('
			SELECT
				m.id_media, m.title, m.type, f.meta, m.description, m.album_id, a.name AS album_name, a.hidden, a.id_album,
				rating, voters, m.id_member, f.height, a.description AS album_description, f.filename, i.width AS icon_width, p.height AS preview_height,
				t.filename AS tf, t.id_file AS id_thumb, t.directory AS td, i.transparency
			FROM {db_prefix}media_items AS m
			INNER JOIN {db_prefix}media_albums AS a ON (m.album_id = a.id_album)
			INNER JOIN {db_prefix}media_files AS f ON (f.id_file = m.id_file)
			LEFT JOIN {db_prefix}media_files AS p ON (p.id_file = IF(m.id_preview = 0, IF(m.id_thumb < 5, a.icon, m.id_thumb), m.id_preview))
			LEFT JOIN {db_prefix}media_files AS t ON (t.id_file = IF(m.id_thumb < 5 AND a.icon > 4, a.icon, m.id_thumb))
			LEFT JOIN {db_prefix}media_files AS i ON (i.id_file = a.icon)
			WHERE m.id_media IN ({raw:ids}) AND {query_see_album_hidden}
			ORDER BY m.id_media DESC',
			array('ids' => preg_match('~\d+(?:,\d+)*~', $id) ? $id : '0')
		);
	}
	else
	{
		if (strpos($id, ',') === false)
		{
			$request = wesql::query('
				SELECT options FROM {db_prefix}media_albums WHERE id_album = {int:album}',
				array('album' => (int) $id)
			);
			list ($opti) = wesql::fetch_row($request);
			wesql::free_result($request);
			$optio = unserialize($opti);
			if (isset($optio['sort']))
				$sort = $optio['sort'];
		}
		$request = wesql::query('
			SELECT
				m.id_media, m.title, m.type, f.meta, m.description, m.album_id, a.name AS album_name, a.hidden, a.id_album,
				rating, voters, m.id_member, f.height, a.description AS album_description, f.filename, i.width AS icon_width, p.height AS preview_height,
				t.filename AS tf, t.id_file AS id_thumb, t.directory AS td, i.transparency
			FROM {db_prefix}media_items AS m
			INNER JOIN {db_prefix}media_albums AS a ON (m.album_id = a.id_album)
			INNER JOIN {db_prefix}media_files AS f ON (f.id_file = m.id_file)
			LEFT JOIN {db_prefix}media_files AS p ON (p.id_file = IF(m.id_preview = 0, IF(m.id_thumb < 5, a.icon, m.id_thumb), m.id_preview))
			LEFT JOIN {db_prefix}media_files AS t ON (t.id_file = IF(m.id_thumb < 5 AND a.icon > 4, a.icon, m.id_thumb))
			LEFT JOIN {db_prefix}media_files AS i ON (i.id_file = a.icon)
			WHERE m.album_id IN ({raw:album}) AND LOWER(RIGHT(f.filename, 3)) IN ({raw:extensions}) AND {query_see_album_hidden}
			ORDER BY ' . $sort,
			array(
				'album' => preg_match('~\d+(?:,\d+)*~', $id) ? $id : '0',
				'extensions' => substr($exts, 0, -2),
			)
		);
	}

	if (wesql::num_rows($request) == 0)
		return $txt['media_tag_no_items'];

	$total_rating = $nvotes = 0;
	$thei = $amSettings['max_thumb_height'];
	$playlist = array();
	$has_album = array();
	$has_type = array('audio' => 0, 'video' => 0, 'image' => 0);
	$clearurl = str_replace(ROOT_DIR, ROOT, $amSettings['data_dir_path']);
	while ($row = wesql::fetch_assoc($request))
	{
		if (in_array($type, array('audio', 'video', 'media')) && empty($playlist_description))
		{
			$playlist_name = $row['album_name'];
			$playlist_description = $row['album_description'];
		}
		$has_type[$row['type']]++;
		$has_album[$row['album_id']] = isset($has_album[$row['album_id']]) ? $has_album[$row['album_id']] + 1 : 1;
		$filename = SCRIPT . '?action=media;sa=media;in=' . $row['id_media'];
		$titre = $row['title'];
		$ext = strtolower(substr(strrchr($row['filename'], '.'), 1));
		$artist = $row['album_name'];
		$thumb = isset($row['td']) && !empty($amSettings['clear_thumbnames']) ? $clearurl . '/' . str_replace('%2F', '/', urlencode($row['td'])) . '/' . aeva_getEncryptedFilename($row['tf'], $row['id_thumb'], true) : $galurl . 'sa=media;in=' . $row['id_media'] . ';thumba';
		$meta = unserialize($row['meta']);
		$total_rating += (int) $row['rating'];
		$nvotes += (int) $row['voters'];
		$thei = min(400, max($thei, ($row['type'] == 'image' && $row['height'] > $amSettings['max_preview_height']) || empty($row['height']) ? $row['preview_height'] : $row['height']));
		$playlist[$row['id_media']] = array(
			'title' => $titre,
			'id' => $row['id_media'],
			'file' => $filename,
			'thumb' => $thumb,
			'duration' => round(!empty($meta['duration']) ? $meta['duration'] : 5),
			'description' => empty($row['description']) ? '' : parse_bbc($row['description'], 'media-description'),
			'lister_description' => empty($row['pl_description']) ? '' : parse_bbc($row['pl_description'], 'media-playlist-description'),
			'album' => $row['album_name'],
			'album_id' => $row['id_album'],
			'album_hidden' => $row['hidden'],
			'owner' => $row['id_member'],
			'type' => $row['type'] == 'audio' ? 'sound' : $row['type'],
			'icon_width' => $row['icon_width'],
			'icon_transparent' => $row['transparency'] == 'transparent',
			'rating' => (int) $row['rating'],
			'voters' => (int) $row['voters'],
			'ext' => $ext,
			'link' => '',
		);
	}
	wesql::free_result($request);
	$album_id = array_search(max($has_album), $has_album);

	$req = 'SELECT d.id_media, d.id_field, d.value, f.name
		FROM {db_prefix}media_field_data AS d
		INNER JOIN {db_prefix}media_fields AS f ON f.id_field = d.id_field
		WHERE d.id_media IN ({array_int:id})';

	$all_fields = $order_fields = array();
	$request = wesql::query($req, array('id' => array_keys($playlist)));
	while ($row = wesql::fetch_assoc($request))
	{
		$playlist[$row['id_media']]['custom_fields'][$row['name']] = $row;
		$all_fields[$row['name']][$row['value']] = $row['value'];
		if ($field_sort == $row['id_field'])
			$order_fields[$row['value']][] = $row['id_media'];
	}
	wesql::free_result($request);

	// Do we need to make a manual sort by custom field?
	if (count($order_fields) > 0)
	{
		ksort($order_fields);
		$fields = $ordered_playlist = array();
		foreach ($order_fields as $in_order)
			foreach ($in_order as $pl)
				$ordered_playlist[$pl] = $playlist[$pl];
		$playlist = $ordered_playlist;
		unset($ordered_playlist);
	}

	$req = '
		SELECT
			d.id_media, d.id_playlist, p.description, p.name, p.id_member, m.real_name, d.description AS lister_description
		FROM {db_prefix}media_playlist_data AS d
		INNER JOIN {db_prefix}media_playlists AS p ON p.id_playlist = d.id_playlist
		LEFT JOIN {db_prefix}members AS m ON m.id_member = p.id_member
		WHERE d.id_media IN ({array_int:id})';

	$all_playlists = array();
	$request = wesql::query($req, array('id' => array_keys($playlist)));
	while ($row = wesql::fetch_assoc($request))
	{
		$playlist[$row['id_media']]['playlists'][$row['name']] = $row;
		$all_playlists[$row['id_playlist']] = $row;
		if (empty($playlist_name) && $type == 'playl' && $row['id_playlist'] == $id)
		{
			$playlist_name = $row['name'];
			$playlist_description = $row['description'];
			$playlist_owner_id = empty($row['id_member']) ? 0 : $row['id_member'];
			$playlist_owner_name = empty($row['real_name']) ? '' : $row['real_name'];
			$current_url = SCRIPT . '?' . (!empty($context['current_board']) ? 'board=' . $context['current_board'] . ';' : '') . 'action=media;sa=playlists;in=' . $id; // $_SERVER['REQUEST_URL']
			add_linktree($txt['media_playlists'], '<URL>?action=media;sa=playlists');
			add_linktree($playlist_name, $current_url);
		}
	}
	wesql::free_result($request);

	if (in_array('playlists', $details))
	{
		foreach ($playlist as $myp => $p)
		{
			$gn = '';
			if (!empty($p['playlists']))
			{
				foreach ($p['playlists'] as $pp)
					$gn .= $type == 'playl' && $pp['id_playlist'] == $id ? $pp['name'] . ', ' : '<a href="<URL>?' . (!empty($context['current_board']) ?
						'board=' . $context['current_board'] . ';' : '') . 'action=media;sa=playlists;in=' . $pp['id_playlist'] . '" onclick="lnFlag=1;">' . $pp['name'] . '</a>, ';
				$playlist[$myp]['plists'] = substr($gn, 0, -2);
			}
		}
	}

	if (!in_array('none', $details))
	{
		$first_p = reset($playlist);
		$box .= '<table class="foxy_side cp0 cs0 floatright" style="width: ' . max(100, $first_p['icon_width'] + 10) . 'px">';

		if (!empty($all_fields))
		{
			$box .= '<tr><td class="top">';
			foreach ($all_fields as $name => $field)
			{
				$box .= '<b>' . $name . '</b>: ';
				$max_3 = 0;
				foreach ($field as $sf)
				{
					if ($max_3++ < 3)
						$box .= (substr($sf, 0, 7) == 'http://' ? '<a href="' . $sf . '">www</a>' : $sf) . ', ';
					else
					{
						$box = substr($box, 0, -2) . '&hellip;, ';
						break;
					}
				}
				$box = substr($box, 0, -2) . '<br>';
			}
			$box .= '</td></tr>';
		}

		$box .= '<tr>' . ($album_id > 0 ? '<td class="top">
	<img class="aep' . ($first_p['icon_transparent'] ? ' ping' : '') . '" src="<URL>?action=media;sa=media;in=' . $album_id . ';bigicon"></td>' : '');

		if ($nvotes != 0 && in_array('votes', $details))
		{
			$box .= '</tr><tr><td><div class="vote"><div class="vote_header"><b>' . $txt['media_rating'] . ': <span style="color: red">' . sprintf('%.2f', $total_rating / $nvotes) . '/5</span></b> (' . $nvotes . ' ' . $txt['media_vote' . ($nvotes > 1 ? 's' : '') . '_noun'] . ')';
			$box .= '<br>' . aeva_showStars($total_rating / $nvotes);
			$box .= ' <a href="#" onclick="$(this.parentNode.parentNode.lastChild).toggle(); return false;"><img src="' . ASSETS . '/aeva/magnifier.png" width="16" height="16" alt="' . $txt['media_who_rated_what'] . '" title="' . $txt['media_who_rated_what'] . '" class="aevera"></a></div>
			<div class="vote_details hide" style="padding: 12px 0 0 12px">';

			// All votes
			$req = 'SELECT p.id_member, p.rating, m.real_name
				FROM {db_prefix}media_log_ratings AS p
				INNER JOIN {db_prefix}members AS m ON m.id_member = p.id_member
				WHERE p.id_media IN ({array_int:id})';

			$request = wesql::query($req, array('id' => array_keys($playlist)));
			while ($row = wesql::fetch_assoc($request))
				$box .= aeva_showStars($row['rating']) . ' ' . $txt['by'] . ' <a href="<URL>?action=profile;u=' . $row['id_member'] . ';area=aevavotes">' . $row['real_name'] . '</a><br>';
			$box .= '</div></div></td>';
			wesql::free_result($request);
		}

		if (!empty($playlist_name) && in_array('name', $details))
			$context['page_title'] .= ': ' . $playlist_name;

		$box .= '</tr></table>
	<div class="foxy_stats">';

		if ($has_type['audio'] && !$has_type['video'] && !$has_type['image'])
			$box .= $txt['media_foxy_audio_list'];
		elseif (!$has_type['audio'] && $has_type['video'] && !$has_type['image'])
			$box .= $txt['media_foxy_video_list'];
		elseif (!$has_type['audio'] && !$has_type['video'] && $has_type['image'])
			$box .= $txt['media_foxy_image_list'];
		else
			$box .= $txt['media_foxy_media_list'];
		$box .= ' &mdash; '
			. ($has_type['audio'] ? $has_type['audio'] . ' ' . $txt['media_foxy_stats_audio' . ($has_type['audio'] > 1 ? 's' : '')] . ($has_type['image'] != $has_type['video'] && ($has_type['image'] == 0 || $has_type['video'] == 0) ? ' ' . $txt['media_and'] . ' ' : ', ') : '')
			. ($has_type['video'] ? $has_type['video'] . ' ' . $txt['media_foxy_stats_video' . ($has_type['video'] > 1 ? 's' : '')] . ($has_type['image'] ? ' ' . $txt['media_and'] . ' ' : ', ') : '')
			. ($has_type['image'] ? $has_type['image'] . ' ' . $txt['media_foxy_stats_image' . ($has_type['image'] > 1 ? 's' : '')] . ', ' : '');
		$box = substr($box, 0, -2) . ' ' . sprintf($txt['media_from_album' . (count($has_album) > 1 ? 's' : '')], count($has_album))
			. ($type == 'playl' && (we::$is_admin || ($playlist_owner_id == MID && aeva_allowedTo('add_playlists'))) ? ' - <a href="<URL>?action=media;sa=playlists;in=' . $id . ';edit;' . $context['session_query'] . '"><img src="' . ASSETS . '/aeva/camera_edit.png" class="bottom"> ' . $txt['media_edit_this_item'] . '</a>' : '') . '</div>';

		$box .= !empty($playlist_description) && in_array('description', $details) ? parse_bbc($playlist_description, 'media-playlist-description') : '';

		if (!empty($all_playlists) && in_array('playlists', $details))
		{
			$box .= '<br><br>' . $txt['media_related_playlists'] . ': ';
			foreach ($all_playlists as $idi => $list)
				$box .= '<a href="<URL>?' . (!empty($context['current_board']) ? 'board=' . $context['current_board'] . ';' : '') . 'action=media;sa=playlists;in=' . $idi . '">' . $list['name'] . '</a>, ';
			$box = substr($box, 0, -2);
		}
	}

	// On a list of messages, you don't want to show several players at once... Here's a setting to disable them.
	if (!empty($context['aeva_disable_player']))
		return $box;

	if (!in_array('none', $details))
		$box .= '<br><br>';

	return $box . aeva_foxy_fill_player($playlist, $type, $details, 0, $pwid, 430, $thei + 20);
}

function aeva_foxy_fill_player(&$playlist, $type, &$details, $play = 0, $wid = 470, $hei = 430, $thei = 70)
{
	global $amSettings, $context, $txt;
	static $swo = 0;

	$swo++;
	add_css('
	.foxy_playlist {
		height: '. $hei . 'px;
		max-height: '. $hei . 'px;
	}');

	$tx = init_videojs() . (!empty($_REQUEST['sa']) && $_REQUEST['sa'] == 'search' ? '<!-- aeva_page_index -->' : '') . '
<table class="foxy_album w100 centered">
<tr><td style="height: ' . $thei . 'px">' /* <div id="aefoxy' . $swo . '" style="overflow: auto; height: ' . $thei . 'px">&nbsp;</div> */ . '
	<video id="video" class="video-js vjs-default-skin" controls width="640" height="360"></video>
	<a href="#" onclick="return false;" data-action="prev">&lt;&lt;</a>
	<a href="#" onclick="return false;" data-action="next">&gt;&gt;</a>
</td></tr>
<tr><td><div id="foxlist' . $swo . '" class="foxy_playlist" onmousedown="return false;">
	<table class="w100 cp4 cs0">';
	$c = '';
	$num = 0;
	foreach ($playlist as $idi => $i)
	{
		$c = $c == '' ? '2' : '';
		$tx .= '<tr><td ' . (isset($context['aeva_override_altcolor']) ? 'style="background: #' . $context['aeva_override_altcolor' . $c] : 'class="windowbg' . $c) . '">';
		$tx .= '<table class="w100 cp0" id="fxm' . $idi . '" onclick="recreatePlayer(' . $swo . ', ' . $num++ . ');">';
		$tx .= '<tr><td class="top" style="width: 55px"><img src="' . $i['thumb'] . '" width="55" height="55" title="Click to Play"></td>';
		$tx .= '<td class="playlistlo middle" onmouseover="mover(this, ' . $idi . ');" onmouseout="mout(this, ' . $idi . ');" style="padding: 4px">';

		$tx .= $i['title'] . ' <a href="<URL>?action=media;sa=item;in=' . $i['id'] . '" target="_blank" title="" onclick="lnFlag=1;"><img src="' . ASSETS . '/aeva/magnifier.png" width="16" height="16" style="vertical-align: text-bottom"></a>';
		$tx .= ' (' . floor($i['duration'] / 60) . ':' . ($i['duration'] % 60 < 10 ? '0' : '') . ($i['duration'] % 60) . ')';

		$tx .= '<div style="float: right; text-align: right">';
		if (aeva_allowedTo('moderate') || MID == $i['owner'])
			$tx .= '<a href="<URL>?action=media;sa=post;in=' . $i['id'] . '" onclick="lnFlag=1;">' . $txt['modify'] . '</a><div class="foxy_small">';
		$tx .= (in_array('votes', $details) || in_array('none', $details) ? aeva_showStars($i['voters'] > 0 ? $i['rating'] / $i['voters'] : 0)
			. ($i['voters'] > 0 ? '<br>' . ($i['voters'] == 0 ? 0 : sprintf('%.2f', $i['rating'] / $i['voters'])) . '/5 (' . $i['voters']
			. ' ' . $txt['media_vote' . ($i['voters'] > 1 ? 's' : '') . '_noun'] . ')' : '') : '') . '</div></div>';

		$tx .= '<br>';
		$tx .= $i['album_hidden'] ? '<b>' . $i['album'] . '</b>' : '<b><a href="<URL>?action=media;sa=album;in=' . $i['album_id'] . '" target="_blank" onclick="lnFlag=1;">' . $i['album'] . '</a></b>' ;

		if (!empty($i['custom_fields']))
		{
			foreach ($i['custom_fields'] as $name => $field)
			{
				$tx .= ' - ' . $name . ': ';
				if (substr($field['value'], 0, 7) == 'http://')
					$tx .= '<a href="' . $field['value'] . '" target="_blank" onclick="lnFlag=1;">' . $field['value'] . '</a>';
				else
					$tx .= '<a href="<URL>?' . (!empty($context['current_board']) ? 'board=' . $context['current_board'] . ';' : '')
					. 'action=media;sa=search;search=' . urlencode($field['value']) . ';fields[]=' . $field['id_field'] . '" onclick="lnFlag=1;">' . $field['value'] . '</a>';
			}
		}

		if (!empty($i['plists']))
			$tx .= '<div class="foxy_small">' . $i['plists'] . '</div>';
		if ($i['link'])
			$tx .= '<div><a href="' . $i['link'] . '" target="_blank" title="' . $i['link'] . '" onclick="return false;">details</a></div>';
		$tx .= '</td></tr>';
		if ($i['description'])
			$tx .= '<tr><td colspan="2" class="smalltext playlistlo">' . $i['description'] . '</td></tr>';
		if ($i['lister_description'])
			$tx .= '<tr><td colspan="2" class="smalltext playlistlo"><img src="' . ASSETS . '/aeva/user_comment.png" class="left"> ' . $i['lister_description'] . '</td></tr>';
		$tx .= '</table></td></tr>';
	}
	$tx .= '
	</table>
</div>
<div id="info"></div>
</td></tr></table>' . (!empty($_REQUEST['sa']) && $_REQUEST['sa'] == 'search' ? '<!-- aeva_page_index -->' : '');

	add_js_file('player.js');
	add_js_file('playlists.js');

	add_js('
	var swo = ', $swo, ';
	foxp = window.fox || foxp;
	foxp[swo] = [[');

	$arrtypes = array(
		'image' => 0,
		'video' => 1,
		'sound' => 2
	);
	$js = '';
	foreach ($playlist as $i)
		$js .= $i['id'] . ',' . $i['duration'] . ',' . $arrtypes[$i['type']] . ',"' . $i['ext'] . '"], [';
	$first = reset($playlist);
	$js = substr($js, 0, -3) . '];';

	add_js($js . '

	weplay({
		swo: "', $swo, '",
		id: ', $first['id'], ',
		type: "', $first['type'], '",
		height: ', $thei, '
	});');

	return $tx;
}
