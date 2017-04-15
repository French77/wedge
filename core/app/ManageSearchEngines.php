<?php
/**
 * All functionality related to configuring detectable search engines and their hit logs.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

// Entry point for this section.
function SearchEngines()
{
	global $context, $txt, $settings;

	isAllowedTo('admin_forum');

	loadLanguage('Search');
	loadTemplate('ManageSearch');

	if (!empty($settings['spider_mode']))
		$subActions = array(
			'editspiders' => 'EditSpider',
			'logs' => 'SpiderLog',
			'settings' => 'ManageSearchEngineSettings',
			'spiders' => 'ViewSpiders',
			'stats' => 'SpiderStats',
		);
	else
		$subActions = array(
			'settings' => 'ManageSearchEngineSettings',
		);

	// Ensure we have a valid subaction.
	$context['sub_action'] = isset($_REQUEST['sa'], $subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : (empty($settings['spider_mode']) ? 'settings' : 'stats');

	$context['page_title'] = $txt['search_engines'];

	// Some more tab data.
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['search_engines'],
		'description' => $txt['search_engines_description'],
	);

	// Call the function!
	$subActions[$context['sub_action']]();
}

// This is really just the settings page.
function ManageSearchEngineSettings($return_config = false)
{
	global $context, $txt;

	$context['page_title'] = $txt['search_engines'] . ' - ' . $txt['settings'];

	$config_vars = array(
		// How much detail?
		array('select', 'spider_mode', array($txt['spider_mode_off'], $txt['spider_mode_standard'], $txt['spider_mode_high'], $txt['spider_mode_vhigh']), 'onchange' => 'disableFields();', 'subtext' => $txt['spider_mode_subtext']),
		'spider_group' => array('select', 'spider_group', array($txt['spider_group_none'], $txt['membergroups_members']), 'subtext' => $txt['spider_group_subtext']),
		array('select', 'show_spider_online', array($txt['show_spider_online_no'], $txt['show_spider_online_summary'], $txt['show_spider_online_detail'], $txt['show_spider_online_detail_admin'])),
	);

	// Set up a message.
	$context['settings_message'] = '<span class="smalltext">' . sprintf($txt['spider_settings_desc'], '<URL>?action=admin;area=logs;sa=settings;' . $context['session_query']) . '</span>';

	// Do some javascript.
	$javascript_function = '
		function disableFields()
		{
			disabledState = $("#spider_mode").val() == 0;';

	foreach ($config_vars as $variable)
		if ($variable[1] != 'spider_mode')
			$javascript_function .= '
			$("#' . $variable[1] . '").prop("disabled", disabledState);';

	$javascript_function .= '
		}
		disableFields();';

	if ($return_config)
		return $config_vars;

	// We need to load the groups for the spider group thingy.
	$request = wesql::query('
		SELECT id_group, group_name
		FROM {db_prefix}membergroups
		WHERE id_group != {int:admin_group}
			AND id_group != {int:moderator_group}',
		array(
			'admin_group' => 1,
			'moderator_group' => 3,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$config_vars['spider_group'][2][$row['id_group']] = $row['group_name'];
	wesql::free_result($request);

	// Make sure it's valid - note that regular members are given id_group = 1 which is reversed in Load.php - no admins here!
	if (isset($_POST['spider_group']) && !isset($config_vars['spider_group'][2][$_POST['spider_group']]))
		$_POST['spider_group'] = 0;

	// We'll want this for our easy save.
	loadSource('ManageServer');

	// Setup the template.
	wetem::load('show_settings');

	// Are we saving them - are we??
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		recacheSpiderNames();
		redirectexit('action=admin;area=sengines;sa=settings');
	}

	// Final settings...
	$context['post_url'] = '<URL>?action=admin;area=sengines;save;sa=settings';
	$context['settings_title'] = $txt['settings'];
	add_js($javascript_function);

	// Prepare the settings...
	prepareDBSettingContext($config_vars);
}

// View a list of all the spiders we know about.
function ViewSpiders()
{
	global $context, $txt;

	if (!isset($_SESSION['spider_stat']) || $_SESSION['spider_stat'] < time() - 60)
	{
		consolidateSpiderStats();
		$_SESSION['spider_stat'] = time();
	}

	// Are we adding a new one?
	if (!empty($_POST['addSpider']))
		return EditSpider();
	// User pressed the 'remove selection button'.
	elseif (!empty($_POST['removeSpiders']) && !empty($_POST['remove']) && is_array($_POST['remove']))
	{
		checkSession();

		// Make sure every entry is a proper integer.
		foreach ($_POST['remove'] as $index => $spider_id)
			$_POST['remove'][(int) $index] = (int) $spider_id;

		// Delete them all!
		wesql::query('
			DELETE FROM {db_prefix}spiders
			WHERE id_spider IN ({array_int:remove_list})',
			array(
				'remove_list' => $_POST['remove'],
			)
		);
		wesql::query('
			DELETE FROM {db_prefix}log_spider_hits
			WHERE id_spider IN ({array_int:remove_list})',
			array(
				'remove_list' => $_POST['remove'],
			)
		);
		wesql::query('
			DELETE FROM {db_prefix}log_spider_stats
			WHERE id_spider IN ({array_int:remove_list})',
			array(
				'remove_list' => $_POST['remove'],
			)
		);

		cache_put_data('spider_search', null, 300);
		recacheSpiderNames();
	}

	// Get the last seens.
	$request = wesql::query('
		SELECT id_spider, MAX(last_seen) AS last_seen_time
		FROM {db_prefix}log_spider_stats
		GROUP BY id_spider',
		array(
		)
	);

	$context['spider_last_seen'] = array();
	while ($row = wesql::fetch_assoc($request))
		$context['spider_last_seen'][$row['id_spider']] = $row['last_seen_time'];
	wesql::free_result($request);

	$listOptions = array(
		'id' => 'spider_list',
		'items_per_page' => 20,
		'base_href' => '<URL>?action=admin;area=sengines;sa=spiders',
		'default_sort_col' => 'name',
		'get_items' => array(
			'function' => 'list_getSpiders',
		),
		'get_count' => array(
			'function' => 'list_getNumSpiders',
		),
		'no_items_label' => $txt['spiders_no_entries'],
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['spider_name'],
				),
				'data' => array(
					'function' => function ($rowData) {
						return sprintf('<a href="<URL>?action=admin;area=sengines;sa=editspiders;sid=%1$d">%2$s</a>', $rowData['id_spider'], htmlspecialchars($rowData['spider_name']));
					},
				),
				'sort' => array(
					'default' => 'spider_name',
					'reverse' => 'spider_name DESC',
				),
			),
			'last_seen' => array(
				'header' => array(
					'value' => $txt['spider_last_seen'],
				),
				'data' => array(
					'function' => function ($rowData) {
						global $context, $txt;

						return isset($context['spider_last_seen'][$rowData['id_spider']]) ? timeformat($context['spider_last_seen'][$rowData['id_spider']]) : $txt['spider_last_never'];
					},
				),
			),
			'user_agent' => array(
				'header' => array(
					'value' => $txt['spider_agent'],
				),
				'data' => array(
					'db_htmlsafe' => 'user_agent',
				),
				'sort' => array(
					'default' => 'user_agent',
					'reverse' => 'user_agent DESC',
				),
			),
			'ip_info' => array(
				'header' => array(
					'value' => $txt['spider_ip_info'],
				),
				'data' => array(
					'db_htmlsafe' => 'ip_info',
					'class' => 'smalltext',
				),
				'sort' => array(
					'default' => 'ip_info',
					'reverse' => 'ip_info DESC',
				),
			),
			'check' => array(
				'header' => array(
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="remove[]" value="%1$d">',
						'params' => array(
							'id_spider' => false,
						),
					),
					'style' => 'text-align: center',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=sengines;sa=spiders',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '
					<input type="submit" name="addSpider" value="' . $txt['spiders_add'] . '" class="new">
					<input type="submit" name="removeSpiders" value="' . $txt['spiders_remove_selected'] . '" onclick="return ask(' . JavaScriptEscape($txt['spider_remove_selected_confirm']) . ', e);" class="delete">
				',
				'style' => 'text-align: right;',
			),
		),
	);

	loadSource('Subs-List');
	createList($listOptions);

	wetem::load('show_list');
	$context['default_list'] = 'spider_list';
}

function list_getSpiders($start, $items_per_page, $sort)
{
	$request = wesql::query('
		SELECT id_spider, spider_name, user_agent, ip_info
		FROM {db_prefix}spiders
		ORDER BY ' . $sort . '
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
		)
	);
	$spiders = array();
	while ($row = wesql::fetch_assoc($request))
		$spiders[$row['id_spider']] = $row;
	wesql::free_result($request);

	return $spiders;
}

function list_getNumSpiders()
{
	$request = wesql::query('
		SELECT COUNT(*) AS num_spiders
		FROM {db_prefix}spiders',
		array(
		)
	);
	list ($numSpiders) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numSpiders;
}

// Here we can add, and edit, spider info!
function EditSpider()
{
	global $context, $txt;

	// Some standard stuff.
	$context['id_spider'] = !empty($_GET['sid']) ? (int) $_GET['sid'] : 0;
	$context['page_title'] = $context['id_spider'] ? $txt['spiders_edit'] : $txt['spiders_add'];
	wetem::load('spider_edit');

	// Are we saving?
	if (!empty($_POST['save']))
	{
		checkSession();

		$ips = array();
		// Check the IP range is valid.
		$ip_sets = explode(',', $_POST['spider_ip']);
		foreach ($ip_sets as $set)
		{
			$test = ip2range(trim($set));
			if (!empty($test))
				$ips[] = $set;
		}
		$ips = implode(',', $ips);

		// Goes in as it is...
		if ($context['id_spider'])
			wesql::query('
				UPDATE {db_prefix}spiders
				SET spider_name = {string:spider_name}, user_agent = {string:spider_agent},
					ip_info = {string:ip_info}
				WHERE id_spider = {int:current_spider}',
				array(
					'current_spider' => $context['id_spider'],
					'spider_name' => $_POST['spider_name'],
					'spider_agent' => $_POST['spider_agent'],
					'ip_info' => $ips,
				)
			);
		else
			wesql::insert('',
				'{db_prefix}spiders',
				array(
					'spider_name' => 'string', 'user_agent' => 'string', 'ip_info' => 'string',
				),
				array(
					$_POST['spider_name'], $_POST['spider_agent'], $ips,
				)
			);

		// Order by user agent length.
		sortSpiderTable();

		cache_put_data('spider_search', null, 300);
		recacheSpiderNames();

		redirectexit('action=admin;area=sengines;sa=spiders');
	}

	// The default is new.
	$context['spider'] = array(
		'id' => 0,
		'name' => '',
		'agent' => '',
		'ip_info' => '',
	);

	// An edit?
	if ($context['id_spider'])
	{
		$request = wesql::query('
			SELECT id_spider, spider_name, user_agent, ip_info
			FROM {db_prefix}spiders
			WHERE id_spider = {int:current_spider}',
			array(
				'current_spider' => $context['id_spider'],
			)
		);
		if ($row = wesql::fetch_assoc($request))
			$context['spider'] = array(
				'id' => $row['id_spider'],
				'name' => $row['spider_name'],
				'agent' => $row['user_agent'],
				'ip_info' => $row['ip_info'],
			);
		wesql::free_result($request);
	}

}

//!!! Should this not be... you know... in a different file?
// Do we think the current user is a spider?
function SpiderCheck()
{
	global $settings;

	unset($_SESSION['id_robot']);
	$_SESSION['robot_check'] = time();

	// We cache the spider data for five minutes if we can.
	if (!empty($settings['cache_enable']))
		$spider_data = cache_get_data('spider_search', 300);

	if (!isset($spider_data) || $spider_data === null)
	{
		$request = wesql::query('
			SELECT id_spider, user_agent, ip_info
			FROM {db_prefix}spiders',
			array(
			)
		);
		$spider_data = array();
		while ($row = wesql::fetch_assoc($request))
			$spider_data[] = $row;
		wesql::free_result($request);

		if (!empty($settings['cache_enable']))
			cache_put_data('spider_search', $spider_data, 300);
	}

	if (empty($spider_data))
		return false;

	// Only do these bits once.
	$ci_user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $_SERVER['REMOTE_ADDR'], $ip_parts);

	foreach ($spider_data as $spider)
	{
		// User agent is easy.
		if (!empty($spider['user_agent']) && strpos($ci_user_agent, strtolower($spider['user_agent'])) !== false)
			$_SESSION['id_robot'] = $spider['id_spider'];
		// IP stuff is harder.
		elseif (!empty($ip_parts))
		{
			$ips = explode(',', $spider['ip_info']);
			foreach ($ips as $ip)
			{
				$ip = ip2range($ip);
				if (!empty($ip))
				{
					foreach ($ip as $key => $value)
					{
						if ($value['low'] > $ip_parts[$key + 1] || $value['high'] < $ip_parts[$key + 1])
							break;
						elseif ($key == 3)
							$_SESSION['id_robot'] = $spider['id_spider'];
					}
				}
			}
		}

		if (isset($_SESSION['id_robot']))
			break;
	}

	// If this is low server tracking then log the spider here as opposed to the main logging function.
	if (!empty($settings['spider_mode']) && $settings['spider_mode'] == 1 && !empty($_SESSION['id_robot']))
		logSpider();

	return !empty($_SESSION['id_robot']) ? $_SESSION['id_robot'] : 0;
}

// Log the spider presence online.
// !!! Different file?
function logSpider()
{
	global $settings, $context;

	if (empty($settings['spider_mode']) || empty($_SESSION['id_robot']))
		return;

	// Attempt to update today's entry.
	if ($settings['spider_mode'] == 1)
	{
		$date = strftime('%Y-%m-%d', forum_time(false));
		wesql::query('
			UPDATE {db_prefix}log_spider_stats
			SET last_seen = {int:current_time}, page_hits = page_hits + 1
			WHERE id_spider = {int:current_spider}
				AND stat_date = {date:current_date}',
			array(
				'current_date' => $date,
				'current_time' => time(),
				'current_spider' => $_SESSION['id_robot'],
			)
		);

		// Nothing updated?
		if (wesql::affected_rows() == 0)
		{
			wesql::insert('ignore',
				'{db_prefix}log_spider_stats',
				array(
					'id_spider' => 'int', 'last_seen' => 'int', 'stat_date' => 'date', 'page_hits' => 'int',
				),
				array(
					$_SESSION['id_robot'], time(), $date, 1,
				)
			);
		}
	}
	// If we're tracking better stats than track, better stats - we sort out the today thing later.
	else
	{
		if ($settings['spider_mode'] > 2)
		{
			$url = $_GET + array('USER_AGENT' => $_SERVER['HTTP_USER_AGENT']);
			unset($url[$context['session_var']]);
			$url = serialize($url);
		}
		else
			$url = '';

		wesql::insert('',
			'{db_prefix}log_spider_hits',
			array('id_spider' => 'int', 'log_time' => 'int', 'url' => 'string'),
			array($_SESSION['id_robot'], time(), $url)
		);
	}
}

// This function takes any unprocessed hits and turns them into stats.
function consolidateSpiderStats()
{
	$request = wesql::query('
		SELECT id_spider, MAX(log_time) AS last_seen, COUNT(*) AS num_hits
		FROM {db_prefix}log_spider_hits
		WHERE processed = {int:not_processed}
		GROUP BY id_spider, MONTH(log_time), DAYOFMONTH(log_time)',
		array(
			'not_processed' => 0,
		)
	);
	$spider_hits = array();
	while ($row = wesql::fetch_assoc($request))
		$spider_hits[] = $row;
	wesql::free_result($request);

	if (empty($spider_hits))
		return;

	// Attempt to update the master data.
	$stat_inserts = array();
	foreach ($spider_hits as $stat)
	{
		// We assume the max date is within the right day.
		$date = strftime('%Y-%m-%d', $stat['last_seen']);
		wesql::query('
			UPDATE {db_prefix}log_spider_stats
			SET page_hits = page_hits + ' . $stat['num_hits'] . ',
				last_seen = CASE WHEN last_seen > {int:last_seen} THEN last_seen ELSE {int:last_seen} END
			WHERE id_spider = {int:current_spider}
				AND stat_date = {date:last_seen_date}',
			array(
				'last_seen_date' => $date,
				'last_seen' => $stat['last_seen'],
				'current_spider' => $stat['id_spider'],
			)
		);
		if (wesql::affected_rows() == 0)
			$stat_inserts[] = array($date, $stat['id_spider'], $stat['num_hits'], $stat['last_seen']);
	}

	// New stats?
	if (!empty($stat_inserts))
		wesql::insert('ignore',
			'{db_prefix}log_spider_stats',
			array('stat_date' => 'date', 'id_spider' => 'int', 'page_hits' => 'int', 'last_seen' => 'int'),
			$stat_inserts
		);

	// All processed.
	wesql::query('
		UPDATE {db_prefix}log_spider_hits
		SET processed = {int:is_processed}
		WHERE processed = {int:not_processed}',
		array(
			'is_processed' => 1,
			'not_processed' => 0,
		)
	);
}

// See what spiders have been up to.
function SpiderLog()
{
	global $context, $txt, $settings;

	// Load the template and language just incase.
	loadLanguage('Search');
	loadTemplate('ManageSearch');

	// Did they want to delete some entries?
	if (!empty($_POST['delete_entries']) && isset($_POST['older']) && $_POST['older'] > 0)
	{
		checkSession();

		$deleteTime = time() - (((int) $_POST['older']) * 24 * 60 * 60);

		// Delete the entires.
		wesql::query('
			DELETE FROM {db_prefix}log_spider_hits
			WHERE log_time < {int:delete_period}',
			array(
				'delete_period' => $deleteTime,
			)
		);
	}

	$listOptions = array(
		'id' => 'spider_log',
		'items_per_page' => 20,
		'title' => $txt['spider_log'],
		'no_items_label' => $txt['spider_log_empty'],
		'base_href' => $context['admin_area'] == 'sengines' ? '<URL>?action=admin;area=sengines;sa=logs' : '<URL>?action=admin;area=logs;sa=spiderlog',
		'default_sort_col' => 'log_time',
		'get_items' => array(
			'function' => 'list_getSpiderLog',
		),
		'get_count' => array(
			'function' => 'list_getNumSpiderLogs',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['spider'],
				),
				'data' => array(
					'db' => 'spider_name',
				),
				'sort' => array(
					'default' => 's.spider_name',
					'reverse' => 's.spider_name DESC',
				),
			),
			'log_time' => array(
				'header' => array(
					'value' => $txt['spider_time'],
				),
				'data' => array(
					'function' => function ($rowData) {
						return timeformat($rowData['log_time']);
					},
				),
				'sort' => array(
					'default' => 'sl.id_hit DESC',
					'reverse' => 'sl.id_hit',
				),
			),
			'viewing' => array(
				'header' => array(
					'value' => $txt['spider_viewing'],
				),
				'data' => array(
					'db' => 'url',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'after_title',
				'value' => $txt['spider_log_info'],
				'class' => 'smalltext',
			),
		),
	);

	loadSource('Subs-List');
	createList($listOptions);

	// Now determine the actions of the URLs.
	if (!empty($context['spider_log']['rows']))
	{
		$urls = array();
		// Grab the current /url.
		foreach ($context['spider_log']['rows'] as $k => $row)
		{
			// Feature disabled?
			if (empty($row['viewing']['value']) && isset($settings['spider_mode']) && $settings['spider_mode'] < 3)
				$context['spider_log']['rows'][$k]['viewing']['value'] = '<em>' . $txt['spider_disabled'] . '</em>';
			else
				$urls[$k] = array($row['viewing']['value'], -1);
		}

		// Now stick in the new URLs.
		loadSource('Who');
		$urls = determineActions($urls, 'whospider_');
		foreach ($urls as $k => $new_url)
			$context['spider_log']['rows'][$k]['viewing']['value'] = $new_url;
	}

	$context['page_title'] = $txt['spider_log'];
	wetem::load('show_spider_log');
	$context['default_list'] = 'spider_log';
}

function list_getSpiderLog($start, $items_per_page, $sort)
{
	$request = wesql::query('
		SELECT sl.id_spider, sl.url, sl.log_time, s.spider_name
		FROM {db_prefix}log_spider_hits AS sl
			INNER JOIN {db_prefix}spiders AS s ON (s.id_spider = sl.id_spider)
		ORDER BY ' . $sort . '
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
		)
	);
	$spider_log = array();
	while ($row = wesql::fetch_assoc($request))
		$spider_log[] = $row;
	wesql::free_result($request);

	return $spider_log;
}

function list_getNumSpiderLogs()
{
	$request = wesql::query('
		SELECT COUNT(*) AS num_logs
		FROM {db_prefix}log_spider_hits',
		array(
		)
	);
	list ($numLogs) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numLogs;
}

// Show the spider statistics.
function SpiderStats()
{
	global $txt;

	loadLanguage('Search');
	loadTemplate('ManageSearch');

	// Force an update of the stats every 60 seconds.
	if (!isset($_SESSION['spider_stat']) || $_SESSION['spider_stat'] < time() - 60)
	{
		consolidateSpiderStats();
		$_SESSION['spider_stat'] = time();
	}

	if (!empty($_POST['delete_entries']) && isset($_POST['older']) && $_POST['older'] > 0)
	{
		checkSession();

		$deleteTime = time() - (((int) $_POST['older']) * 24 * 60 * 60);

		// Delete the entires.
		wesql::query('
			DELETE FROM {db_prefix}log_spider_stats
			WHERE last_seen < {int:delete_period}',
			array(
				'delete_period' => $deleteTime,
			)
		);
	}

	// Get the earliest and latest dates.
	$request = wesql::query('
		SELECT MIN(stat_date) AS first_date, MAX(stat_date) AS last_date
		FROM {db_prefix}log_spider_stats',
		array(
		)
	);

	list ($min_date, $max_date) = wesql::fetch_row($request);
	wesql::free_result($request);

	$min_year = (int) substr($min_date, 0, 4);
	$max_year = (int) substr($max_date, 0, 4);
	$min_month = (int) substr($min_date, 5, 2);
	$max_month = (int) substr($max_date, 5, 2);

	// Prepare the dates for the drop down.
	$date_choices = array();
	for ($y = $min_year; $y <= $max_year; $y++)
		for ($m = 1; $m <= 12; $m++)
		{
			// This doesn't count?
			if ($y == $min_year && $m < $min_month)
				continue;
			if ($y == $max_year && $m > $max_month)
				break;

			$date_choices[$y . $m] = $txt['months_short'][$m] . ' ' . $y;
		}

	// What are we currently viewing?
	$current_date = isset($_REQUEST['new_date'], $date_choices[$_REQUEST['new_date']]) ? $_REQUEST['new_date'] : $max_date;

	// Prepare the HTML.
	$date_select = '
		' . $txt['spider_stats_select_month'] . ':
		<select name="new_date" onchange="document.spider_stat_list.submit();">';

	if (empty($date_choices))
		$date_select .= '
			<option></option>';
	else
		foreach ($date_choices as $id => $text)
			$date_select .= '
			<option value="' . $id . '"' . ($current_date == $id ? ' selected' : '') . '>' . $text . '</option>';

	$date_select .= '
		</select>
		<noscript>
			<input type="submit" name="go" value="' . $txt['go'] . '">
		</noscript>';

	// If we manually jumped to a date work out the offset.
	if (isset($_REQUEST['new_date']))
	{
		$date_query = sprintf('%04d-%02d-01', substr($current_date, 0, 4), substr($current_date, 4));

		$request = wesql::query('
			SELECT COUNT(*) AS offset
			FROM {db_prefix}log_spider_stats
			WHERE stat_date < {date:date_being_viewed}',
			array(
				'date_being_viewed' => $date_query,
			)
		);
		list ($_REQUEST['start']) = wesql::fetch_row($request);
		wesql::free_result($request);
	}

	$listOptions = array(
		'id' => 'spider_stat_list',
		'items_per_page' => 20,
		'base_href' => '<URL>?action=admin;area=sengines;sa=stats',
		'default_sort_col' => 'stat_date',
		'get_items' => array(
			'function' => 'list_getSpiderStats',
		),
		'get_count' => array(
			'function' => 'list_getNumSpiderStats',
		),
		'no_items_label' => $txt['spider_stats_no_entries'],
		'columns' => array(
			'stat_date' => array(
				'header' => array(
					'value' => $txt['date'],
				),
				'data' => array(
					'db' => 'stat_date',
				),
				'sort' => array(
					'default' => 'stat_date',
					'reverse' => 'stat_date DESC',
				),
			),
			'name' => array(
				'header' => array(
					'value' => $txt['spider_name'],
				),
				'data' => array(
					'db' => 'spider_name',
				),
				'sort' => array(
					'default' => 's.spider_name',
					'reverse' => 's.spider_name DESC',
				),
			),
			'page_hits' => array(
				'header' => array(
					'value' => $txt['spider_stats_page_hits'],
				),
				'data' => array(
					'db' => 'page_hits',
				),
				'sort' => array(
					'default' => 'ss.page_hits',
					'reverse' => 'ss.page_hits DESC',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=sengines;sa=stats',
			'name' => 'spider_stat_list',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => $date_select,
				'style' => 'text-align: right;',
			),
		),
	);

	loadSource('Subs-List');
	createList($listOptions);

	wetem::load('show_spider_stats');
}

function list_getSpiderStats($start, $items_per_page, $sort)
{
	$request = wesql::query('
		SELECT ss.id_spider, ss.stat_date, ss.page_hits, s.spider_name
		FROM {db_prefix}log_spider_stats AS ss
			INNER JOIN {db_prefix}spiders AS s ON (s.id_spider = ss.id_spider)
		ORDER BY ' . $sort . '
		LIMIT ' . $start . ', ' . $items_per_page,
		array(
		)
	);
	$spider_stats = array();
	while ($row = wesql::fetch_assoc($request))
		$spider_stats[] = $row;
	wesql::free_result($request);

	return $spider_stats;
}

function list_getNumSpiderStats()
{
	$request = wesql::query('
		SELECT COUNT(*) AS num_stats
		FROM {db_prefix}log_spider_stats',
		array(
		)
	);
	list ($numStats) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numStats;
}

// Recache spider names?
function recacheSpiderNames()
{
	$request = wesql::query('
		SELECT id_spider, spider_name
		FROM {db_prefix}spiders',
		array(
		)
	);
	$spiders = array();
	while ($row = wesql::fetch_assoc($request))
		$spiders[$row['id_spider']] = $row['spider_name'];
	wesql::free_result($request);

	updateSettings(array('spider_name_cache' => serialize($spiders)));
}

// Sort the search engine table by user agent name to avoid misidentification of engine.
function sortSpiderTable()
{
	loadSource('Class-DBHelper');

	// Add a sorting column.
	wedb::add_column('{db_prefix}spiders', array('name' => 'temp_order', 'size' => 8, 'type' => 'mediumint', 'null' => false));

	// Set the contents of this column.
	wesql::query('
		UPDATE {db_prefix}spiders
		SET temp_order = LENGTH(user_agent)',
		array(
		)
	);

	// Order the table by this column.
	wesql::query('
		ALTER TABLE {db_prefix}spiders
		ORDER BY temp_order DESC',
		array(
			'db_error_skip' => true,
		)
	);

	// Remove the sorting column.
	wedb::remove_column('{db_prefix}spiders', 'temp_order');
}
