<?php
/**
 * Outputs the "info center" information suitable for the sidebar.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

// Show statistical style information...
function template_info_center_statistics()
{
	global $context, $txt, $settings;

	if (empty($settings['show_stats_index']))
		return;

	echo '
	<section class="ic">
		<we:title>';

	if (empty($settings['trackStats']))
		echo '
			<img src="', ASSETS, '/icons/info.gif" alt="', $txt['global_stats'], '">';
	else
		echo '
			<a href="<URL>?action=stats"><img src="', ASSETS, '/icons/info.gif" alt="', $txt['global_stats'], '"></a>';

	echo '
			', $txt['global_stats'], '
		</we:title>
		<ul class="stats">
			<li>', $context['common_stats']['total_posts'], ' ', $txt['posts_made'], ' ', $txt['in'], ' ', $context['common_stats']['total_topics'], ' ', $txt['topics'], ' ', $txt['by'], ' ', $context['common_stats']['total_members'], ' ', $txt['members'], '.</li>', !empty($settings['show_latest_member']) ? '
			<li>' . $txt['latest_member'] . ': <strong> ' . $context['common_stats']['latest_member']['link'] . '</strong></li>' : '', !empty($context['latest_post']) ? '
			<li>' . $txt['latest_post'] . ': <strong>&quot;' . $context['latest_post']['link'] . '&quot;</strong> (' . $context['latest_post']['on_time'] . ')</li>' : '', '
			<li><a href="<URL>?action=recent">', $txt['recent_view'], '</a></li>', $context['show_stats'] ? '
			<li><a href="<URL>?action=stats">' . $txt['more_stats'] . '</a></li>' : '', '
		</ul>
	</section>';
}

function template_info_center_usersonline()
{
	global $context, $txt, $settings;

	// "Users online" - in order of activity.
	echo '
	<section class="ic">
		<we:title>
			', $context['show_who'] ? '<a href="<URL>?action=who">' : '', '<img src="', ASSETS, '/icons/online.gif', '" alt="', $txt['online_users'], '">', $context['show_who'] ? '</a>' : '', '
			', $txt['online_users'], '
		</we:title>
		<p class="inline stats">
			', $context['show_who'] ? '<a href="<URL>?action=who">' : '', comma_format($context['num_guests']), ' ', $context['num_guests'] == 1 ? $txt['guest'] : $txt['guests'], ', ' . comma_format($context['num_users_online']), ' ', $context['num_users_online'] == 1 ? $txt['user'] : $txt['users'];

	// Handle hidden users and buddies.
	$bracketList = array();
	if ($context['show_buddies'])
		$bracketList[] = comma_format($context['num_buddies']) . ' ' . ($context['num_buddies'] == 1 ? $txt['buddy'] : $txt['buddies']);
	if (!empty($context['num_spiders']))
		$bracketList[] = comma_format($context['num_spiders']) . ' ' . ($context['num_spiders'] == 1 ? $txt['spider'] : $txt['spiders']);
	if (!empty($context['num_users_hidden']))
		$bracketList[] = comma_format($context['num_users_hidden']) . ' ' . $txt['hidden'];

	if (!empty($bracketList))
		echo ' (' . implode(', ', $bracketList) . ')';

	echo $context['show_who'] ? '</a>' : '', '
		</p>
		<p class="inline onlineinfo">';

	// Assuming there ARE users online... each user in users_online has an id, username, name, group, href, and link.
	if (!empty($context['users_online']))
	{
		echo '
			', sprintf($txt['users_active'], $settings['lastActive']), ':<br>', implode(', ', $context['list_users_online']);

		// Showing membergroups?
		if (!empty($settings['show_group_key']) && !empty($context['membergroups']))
			echo '
			<br>[' . implode(']&nbsp; [', $context['membergroups']) . ']';
	}

	echo '
		</p>';

	if (allowedTo('moderate_forum'))
		echo '
		<p class="last">
			', $txt['most_online_today'], ': <strong>', comma_format($settings['mostOnlineToday']), '</strong>.
			', $txt['most_online_ever'], ': ', comma_format($settings['mostOnline']), ' (', timeformat($settings['mostDate']), ')
		</p>';

	echo '
	</section>';
}

// If user is logged in but stats are off, show them a PM bar.
function template_info_center_personalmsg()
{
	global $context, $txt, $settings;

	if (we::$is_guest || !empty($settings['show_stats_index']) || empty($context['allow_pm']))
		return;

	echo '
	<section class="ic">
		<we:title>
			<a href="<URL>?action=pm"><img src="', ASSETS, '/message_sm.gif" alt="', $txt['personal_message'], '"></a>
			', $txt['personal_messages'], '
		</we:title>
		<p class="pminfo">
			', number_context('youve_got_pms', we::$user['messages']), '
			', sprintf($txt['click_to_view_them'], '<URL>?action=pm'), '
		</p>
	</section>';
}
