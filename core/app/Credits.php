<?php
/**
 * The Who's Who of Wedge Wardens. Keeps track of all the credits, and displays them to everyone, or just within the admin panel.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/**
 * Display the credits.
 *
 * - Uses the Who language file.
 * - Builds $context['credits'] to list the different teams behind application development, and the people who contributed.
 * - Adds $context['copyright']['mods'] where plugin developers can add their copyrights without touching the footer or anything else.
 * - Calls the 'place_credit' hook to enable modders to add to this page.
 *
 * @param bool $in_admin If calling from the admin panel, this should be true, to prevent loading the template that is normally loaded where this function would be called as a regular action (action=credits)
 */

function Credits()
{
	global $context, $settings, $txt, $memberContext;

	// Don't blink. Don't even blink. Blink and you're dead.
	loadLanguage('Who');

	add_linktree($txt['site_credits'], '<URL>?action=credits');

	// Load the admin and moderator list for this website.
	$context['site_credits'] = $site_team = array();
	$query = wesql::query('
		SELECT id_member, real_name, id_group, additional_groups
		FROM {db_prefix}members
		WHERE id_group IN (1, 2)
			OR FIND_IN_SET(1, additional_groups)
			OR FIND_IN_SET(2, additional_groups)',
		array()
	);
	while ($row = wesql::fetch_assoc($query))
		$site_team[$row['id_member']] = $row;
	wesql::free_result($query);

	// It's nicer with avatars, really...
	loadMemberData(array_keys($site_team));
	foreach ($site_team as $member => $row)
	{
		if (empty($memberContext[$member]['avatar']))
			loadMemberAvatar($member, true);
		$row['real_name'] = (empty($memberContext[$member]['avatar']['image']) ? '' : $memberContext[$member]['avatar']['image']) . $row['real_name'];
		$context['site_credits'][$row['id_group'] == 1 || (!empty($row['additional_groups']) && in_array(1, explode(',', $row['additional_groups']))) ? 'admins' : 'mods'][] = $row;
	}

	$context['credits'] = array();

	// Give the translators some credit for their hard work.
	if (!empty($txt['translation_credits']))
		$context['credits'][] = array(
			'title' => $txt['credits_groups_language'],
			'members' => $txt['translation_credits'],
		);

	$context['credits']['copyright'] = array(
		'title' => $txt['credits_copyright'],
		'members' => array(
			sprintf(
				$txt['credits_wedge'],
				'René-Gilles Deberdt',
				'//wedge.org/',
				'//wedge.org/profile/Nao/',
				'//wedge.org/license/',
				'2010-' . date('Y')
			),
		),
	);

	if (!empty($settings['embed_enabled']) || !empty($settings['media_enabled']))
		$context['credits']['copyright']['members'][] = sprintf($txt['credits_aeme'], '//aeva.noisen.com/');

	$context['plugin_credits'] = array();

	/*
		To Plugin Authors:
		The best way to credit your plugins in a visible, yet unobtrusive way, is to add a copyright statement to this array.
		Do NOT edit the file, it could get messy. Simply call an add_hook('place_credit', 'my_function', 'my_source_file'), with:
		function my_function() {
			global $context, $txt;
			// e.g. '<a href="link">Plugin42</a> is &copy; Nao and Wedge contributors 2010, MIT license.'
			$context['plugin_credits'][] = $txt['copyright_string_for_my_plugin'];
		}
	*/

	call_hook('place_credit');

	if (!empty($context['plugin_credits']))
		$context['credits']['mods'] = array(
			'title' => $txt['credits_plugins'],
			'members' => $context['plugin_credits'],
		);

	loadTemplate('Who');
	wetem::load('credits');
	$context['robot_no_index'] = true;
	$context['page_title'] = $txt['credits_site'];
}
