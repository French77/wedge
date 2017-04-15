<?php
/**
 * Lists thought threads and recent thoughts.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

// The infamous thought system from Noisen.com...
// Not exactly optimized for speed, but we can always rewrite it later.
function Thoughts()
{
	global $context, $txt, $settings;

	// If we're not in a specific thought thread, we're asking for the latest thoughts.
	$master = isset($_REQUEST['in']) ? (int) $_REQUEST['in'] : 0;
	if (!$master)
		return latestThoughts();

	// Some initial context.
	loadLanguage('Profile');
	loadTemplate('Thoughts');
	wetem::load('showThoughts');
	$context['page_title'] = $txt['showThoughts'];
	$context['thought_context'] = $master;

	$context['thoughts'] = $thoughts = array();
	$request = wesql::query('
		SELECT id_thought
		FROM {db_prefix}thoughts
		WHERE id_master = {int:id_master}
		OR id_thought = {int:id_master}',
		array(
			'id_master' => $master,
		)
	);
	$think = array();
	while ($row = wesql::fetch_row($request))
		$think[] = $row[0];
	wesql::free_result($request);

	if (!empty($think))
	{
		$request = wesql::query('
			SELECT
				h.updated, h.thought, h.id_thought, h.id_parent, h.id_member,
				h.privacy, h.id_master, h2.id_member AS id_parent_owner,
				m.real_name AS owner_name, m2.real_name AS parent_name
			FROM
				{db_prefix}thoughts AS h
			LEFT JOIN
				{db_prefix}members AS m ON (h.id_member = m.id_member)
			LEFT JOIN
				{db_prefix}thoughts AS h2 ON (h.id_parent = h2.id_thought)
			LEFT JOIN
				{db_prefix}members AS m2 ON (h2.id_member = m2.id_member)
			WHERE (
				h.id_thought IN ({array_int:think})
				OR h.id_master IN ({array_int:think})
				OR h.id_parent IN ({array_int:think})
			)
			AND {query_see_thought}
			ORDER BY h.id_thought',
			array(
				'think' => $think,
			)
		);
		while ($row = wesql::fetch_assoc($request))
		{
			$row['thought'] = parse_bbc_inline($row['thought'], 'thought', array('user' => $row['id_parent_owner']));
			$thought = array(
				'id' => $row['id_thought'],
				'id_member' => $row['id_member'],
				'id_parent' => $row['id_parent'],
				'id_master' => $row['id_master'],
				'id_parent_owner' => $row['id_parent_owner'],
				'owner_name' => $row['owner_name'],
				'updated' => timeformat($row['updated']),
				'privacy' => $row['privacy'],
				'text' => $row['thought'],
				'can_like' => we::$is_member && !empty($settings['likes_enabled']) && (!empty($settings['likes_own_posts']) || $row['id_member'] != MID),
			);

			if (empty($row['id_parent_owner']) && !empty($thoughts))
			{
				if (!isset($txt['deleted_thought']))
					loadLanguage('Post');
				$row['id_parent_owner'] = 0;
				$thoughts[$row['id_master']]['sub'][$row['id_parent']] = array(
					'id' => $row['id_parent'],
					'id_member' => 0,
					'id_parent' => $row['id_master'],
					'id_master' => $row['id_master'],
					'id_parent_owner' => $thoughts[$row['id_master']]['id_member'],
					'owner_name' => '',
					'updated' => timeformat($row['updated']),
					'privacy' => PRIVACY_DEFAULT,
					'text' => $txt['deleted_thought'],
				);
			}

			$thought['text'] = '<span class="thought" id="thought' . $row['id_thought'] . '" data-oid="' . $row['id_thought'] . '" data-prv="' . $row['privacy'] . '"><span>' . $thought['text'] . '</span></span>';
			$context['thoughts'][$thought['id']] = $thought; // Mini-menus need a flat version of the thought list.
			if (empty($thought['id_master'])) // !! Alternatively, add: || $row['id_master'] != $row['id_member']
				$thoughts[$thought['id']] = $thought;
			else
			{
				if (!isset($thoughts[$row['id_master']]))
				{
					if (empty($row['parent_name']) && !isset($txt['deleted_thought']))
						loadLanguage('Post');
					$thought['text'] = (empty($row['parent_name']) ? '@' . $txt['deleted_thought'] : '@<a href="<URL>?action=profile;u=' . $row['id_parent_owner'] . '">' . $row['parent_name'] . '</a>') . '&gt; ' . $row['thought'];
					$thoughts[$row['id_master']] = $thought;
				}
				elseif ($row['id_master'] === $row['id_parent'] || !isset($thoughts[$row['id_master']]['sub']))
					$thoughts[$row['id_master']]['sub'][$row['id_thought']] = $thought;
				else
					populate_sub_thoughts($thoughts[$row['id_master']]['sub'], $thought);
			}
		}
		wesql::free_result($request);

		// Mini-menu.
		setupThoughtMenu();

		$context['thoughts'] = array();
		foreach (array_reverse(array_keys($thoughts)) as $nb)
			$context['thoughts'][$nb] = $thoughts[$nb];
	}
}

function populate_sub_thoughts(&$here, &$thought)
{
	foreach ($here as &$tho)
	{
		if ($tho['id'] === $thought['id_parent'])
			$tho['sub'][$thought['id']] = $thought;
		elseif (isset($tho['sub']))
			populate_sub_thoughts($tho['sub'], $thought);
	}
}

function embedThoughts($to_show = 10)
{
	global $context, $txt, $settings;

	// Some initial context.
	loadTemplate('Thoughts');
	wetem::add('thoughts');
	$context['thought_context'] = $to_show;

	$request = wesql::query('
		SELECT
			h.updated, h.thought, h.id_thought, h.id_parent, h.privacy,
			h.id_member, h.id_master, h2.id_member AS id_parent_owner,
			m.real_name AS owner_name, mp.real_name AS parent_name, m.posts
		FROM {db_prefix}thoughts AS h
		LEFT JOIN {db_prefix}thoughts AS h2 ON (h.id_parent = h2.id_thought)
		LEFT JOIN {db_prefix}members AS m ON (h.id_member = m.id_member)
		LEFT JOIN {db_prefix}members AS mp ON (h2.id_member = mp.id_member)
		WHERE {query_see_thought}
		ORDER BY h.id_thought DESC
		LIMIT {int:per_page}',
		array(
			'per_page' => $to_show,
		)
	);

	$thoughts = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$id = $row['id_thought'];
		$mid = $row['id_master'];
		$row['thought'] = parse_bbc_inline($row['thought'], 'thought', array('user' => $row['id_member']));
		$thoughts[$row['id_thought']] = array(
			'id' => $row['id_thought'],
			'id_member' => $row['id_member'],
			'id_parent' => $row['id_parent'],
			'id_master' => $mid,
			'id_parent_owner' => $row['id_parent_owner'],
			'owner_name' => $row['owner_name'],
			'privacy' => $row['privacy'],
			'updated' => timeformat($row['updated']),
			'text' => $row['posts'] < 10 ? preg_replace('~\</?a(?:\s[^>]+)?\>(?:https?://)?~', '', $row['thought']) : $row['thought'],
			'can_like' => we::$is_member && !empty($settings['likes_enabled']) && (!empty($settings['likes_own_posts']) || $row['id_member'] != MID),
		);

		$thought =& $thoughts[$row['id_thought']];
		$thought['text'] = '<span class="thought" id="thought' . $id . '" data-oid="' . $id . '" data-prv="' . $row['privacy'] . '"><span>' . $thought['text'] . '</span></span>';

		if (!empty($row['id_parent_owner']))
		{
			if (empty($row['parent_name']) && !isset($txt['deleted_thought']))
				loadLanguage('Post');
			$thought['text'] = '@<a href="<URL>?action=profile;u=' . $row['id_parent_owner'] . '">' . (empty($row['parent_name']) ? $txt['deleted_thought'] : $row['parent_name']) . '</a>&gt; ' . $thought['text'];
		}
	}
	wesql::free_result($request);

	$context['thoughts'] =& $thoughts;

	// Mini-menu.
	if (!empty($thoughts))
		setupThoughtMenu();
}

function latestThoughts($memID = 0)
{
	global $context, $txt, $settings;

	// Some initial context.
	loadLanguage('Profile');
	loadTemplate('Thoughts');
	wetem::load('showLatestThoughts');
	$context['start'] = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
	$context['thought_context'] = $memID;
	$thoughts_per_page = 20;

	$request = wesql::query('
		SELECT COUNT(h.id_thought)
		FROM {db_prefix}thoughts AS h
		WHERE ' . (!$memID ? '1=1' : 'h.id_member = {int:id_member}') . ($memID && (MID == $memID) ? '' : '
		AND {query_see_thought}') . '
		LIMIT 1',
		array(
			'id_member' => $memID,
		)
	);
	list ($total_thoughts) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Because I'm too lazy to show this properly...
	$context['page_title'] = $txt['showThoughts'] . (empty($context['member']) ? '' : ' - ' . $context['member']['name']) . ' (' . $total_thoughts . ')';
	$context['page_index'] = template_page_index($memID ? '<URL>?action=profile;u=' . $memID . ';area=thoughts' : '<URL>?action=thoughts', $context['start'], $total_thoughts, $thoughts_per_page);
	$context['total_thoughts'] = $total_thoughts;

	// Now we get our thought list from this member.
	$context['thoughts'] = $thoughts = array();
	$request = wesql::query('
		SELECT h.id_thought
		FROM {db_prefix}thoughts AS h
		WHERE ' . (!$memID ? '1=1' : 'h.id_member = {int:id_member}') . ($memID && (MID == $memID) ? '' : '
		AND {query_see_thought}') . '
		ORDER BY h.id_thought DESC
		LIMIT {int:start}, {int:per_page}',
		array(
			'id_member' => $memID,
			'start' => floor($context['start'] / $thoughts_per_page) * $thoughts_per_page,
			'per_page' => $thoughts_per_page,
		)
	);
	$think = array();
	while ($row = wesql::fetch_row($request))
		$think[] = $row[0];
	wesql::free_result($request);

	// We'll need to get data for: this user's thoughts, its parents, and one or no children.
	if (!empty($think))
	{
		// h is the thought's parent here.
		$request = wesql::query('
			SELECT
				ho.updated, ho.thought, ho.id_thought, ho.id_parent, ho.id_member,
				ho.id_master, h.id_member AS id_parent_owner, ho.privacy,
				m.real_name AS owner_name, m_parent.real_name AS parent_name,
				m.posts, MIN(h_child.id_thought) > 0 AS has_children
			FROM
				{db_prefix}thoughts AS ho
			LEFT JOIN
				{db_prefix}members AS m ON (ho.id_member = m.id_member)
			LEFT JOIN
				{db_prefix}thoughts AS h ON (h.id_thought = ho.id_parent AND {query_see_thought})
			LEFT JOIN
				{db_prefix}thoughts AS h_child ON (h_child.id_parent = ho.id_thought)
			LEFT JOIN
				{db_prefix}members AS m_parent ON (h.id_member = m_parent.id_member)
			WHERE
				ho.id_thought IN ({array_int:think})
			GROUP BY ho.id_thought, ho.updated, ho.thought, ho.id_parent, ho.id_member, ho.id_master, h.id_member, ho.privacy
			ORDER BY ho.id_thought DESC',
			array(
				'think' => $think,
			)
		);
		while ($row = wesql::fetch_assoc($request))
		{
			$row['thought'] = parse_bbc_inline($row['thought'], 'thought', array('user' => $row['id_member']));
			$thought = array(
				'id' => $row['id_thought'],
				'id_member' => $row['id_member'],
				'id_parent' => $row['id_parent'],
				'id_master' => $row['id_master'],
				'id_parent_owner' => $row['id_parent_owner'],
				'owner_name' => $row['owner_name'],
				'updated' => timeformat($row['updated']),
				'text' => $row['posts'] < 10 ? preg_replace('~\</?a(?:\s[^>]+)?\>(?:https?://)?~', '', $row['thought']) : $row['thought'],
				'privacy' => $row['privacy'],
				'has_children' => $row['has_children'],
				'can_like' => we::$is_member && !empty($settings['likes_enabled']) && (!empty($settings['likes_own_posts']) || $row['id_member'] != MID),
			);

			$thought['text'] = '<span class="thought" id="thought' . $row['id_thought'] . '" data-oid="' . $row['id_thought'] . '" data-prv="' . $row['privacy'] . '"><span>' . $thought['text'] . '</span></span>';

			if (!empty($thought['id_master']))
			{
				if (empty($thought['parent_name']) && !isset($txt['deleted_thought']))
					loadLanguage('Post');
				$thought['text'] = '@' . (empty($row['parent_name']) ? $txt['deleted_thought'] : '<a href="<URL>?action=profile;u=' . $row['id_parent_owner'] . '">' . $row['parent_name'] . '</a>') . '&gt; ' . $thought['text'];
			}
			if ($row['has_children'])
				$thought['text'] .= ' <em>(&hellip;)</em>';
			$context['thoughts'][$thought['id']] = $thought;
		}
		wesql::free_result($request);

		// Mini-menu.
		setupThoughtMenu();
	}
}

function setupThoughtMenu()
{
	global $context, $settings;

	$thoughts =& $context['thoughts'];

	if (!empty($settings['likes_enabled']) && !empty($context['thoughts']))
	{
		$ids = array_keys($thoughts);
		loadSource('Display'); // Might as well reuse this, but of course no doubt we'll hive this off somewhere else in the future.
		prepareLikeContext($ids, 'think');
	}

	$context['mini_menu']['thought'] = array();
	$context['mini_menu_items_show']['thought'] = array();
	$context['mini_menu_items']['thought'] = array(
		'lk' => array(
			'caption' => 'acme_like',
			'action' => '',
			'class' => 'like_button',
			'click' => 'return oThought.like(%1%)',
		),
		'uk' => array(
			'caption' => 'acme_unlike',
			'action' => '',
			'class' => 'unlike_button',
			'click' => 'return oThought.like(%1%)',
		),
		'cx' => array(
			'caption' => 'thome_context',
			'action' => '<URL>?action=thoughts;in=%2%#t%1%',
			'class' => 'context_button',
		),
		're' => array(
			'caption' => 'thome_reply',
			'action' => '',
			'class' => 'quote_button',
			'click' => 'return oThought.edit(%1%, %2%, true)',
		),
		'mo' => array(
			'caption' => 'thome_edit',
			'action' => '',
			'class' => 'edit_button',
			'click' => 'return oThought.edit(%1%, %2%)',
		),
		'de' => array(
			'caption' => 'thome_remove',
			'action' => '',
			'class' => 'remove_button',
			'click' => 'return ask(we_confirm, e, function (go) { if (go) return oThought.remove(%1%); })',
		),
		'bl' => array(
			'caption' => 'thome_personal',
			'action' => '',
			'class' => 'like_button', // Anything better...? A 'favorite' icon, maybe..?
			'click' => 'return oThought.personal(%1%)',
		),
	);

	foreach ($thoughts as $tho)
	{
		$menu = array();

		if (!empty($tho['can_like']))
			$menu[] = empty($context['liked_posts'][$tho['id']]['you']) ? 'lk' : 'uk';

		$menu[] = 'cx/' . ($tho['id_master'] ? $tho['id_master'] : $tho['id']);

		if (we::$is_member)
			$menu[] = 're/' . $tho['id_master'];

		// Can we edit, delete and blurbify?
		if ($tho['id_member'] == MID || we::$is_admin)
		{
			$menu[] = 'mo/' . $tho['id_master'];
			$menu[] = 'de';
			// Users can only select their *own*, public thoughts for posterity...
			if ($tho['id_member'] == MID && $tho['privacy'] == PRIVACY_DEFAULT)
				$menu[] = 'bl';
		}

		// If we can't do anything, it's not even worth recording it...
		if (!empty($menu))
		{
			$context['mini_menu']['thought'][$tho['id']] = $menu;
			$amenu = array();
			foreach ($menu as $mid => $name)
				$amenu[substr($name, 0, 2)] = true;
			$context['mini_menu_items_show']['thought'] += $amenu;
		}
	}

	if (function_exists('template_mini_menu'))
		template_mini_menu('thought', 'thome');
}
