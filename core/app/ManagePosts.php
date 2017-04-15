<?php
/**
 * This file contains all the screens that control settings for topics and posts.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * Portions are © 2011 Simple Machines.
 * License: http://wedge.org/license/
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*	void ManagePostSettings()
		- the main entrance point for the 'Posts and topics' screen.
		- accessed from ?action=admin;area=postsettings.
		- calls the right function based on the given sub-action.
		- defaults to sub-action 'posts'.
		- requires (and checks for) the admin_forum permission.

	void SetCensor()
		- shows an interface to set and test word censoring.
		- requires the admin_forum permission.
		- uses the Admin template and the edit_censored block.
		- tests the censored word if one was posted.
		- uses the censor_vulgar, censor_proper, censorWholeWord, and
		  censorIgnoreCase settings.
		- accessed from ?action=admin;area=postsettings;sa=censor.

	void ModifyPostSettings()
		- set any setting related to posts and posting.
		- requires the admin_forum permission
		- uses the edit_post_settings block of the Admin template.
		- accessed from ?action=admin;area=postsettings;sa=posts.

	void ModifyBBCSettings()
		- set a few Bulletin Board Code settings.
		- requires the admin_forum permission
		- uses the edit_bbc_settings block of the Admin template.
		- accessed from ?action=admin;area=postsettings;sa=bbc.
		- loads a list of Bulletin Board Code tags to allow disabling tags.

	void ModifyTopicSettings()
		- set any setting related to topics.
		- requires the admin_forum permission
		- uses the edit_topic_settings block of the Admin template.
		- accessed from ?action=admin;area=postsettings;sa=topics.

	void ModifyDraftSettings()
		- set any setting related to drafts.
		- requires the admin_forum permission
		- accessed from ?action=admin;area=postsettings;sa=drafts.
*/

function ManagePostSettings()
{
	global $context, $txt;

	// Make sure you can be here.
	isAllowedTo('admin_forum');

	loadLanguage('ManagePosts');

	$subActions = array(
		'posts' => 'ModifyPostSettings',
		'topics' => 'ModifyTopicSettings',
		'bbc' => 'ModifyBBCSettings',
		'editor' => 'ModifyPostEditorSettings',
		'censor' => 'SetCensor',
		'drafts' => 'ModifyDraftSettings',
		'merge' => 'ModifyMergeSettings',
	);

	// Default the sub-action to 'posts'.
	$_REQUEST['sa'] = isset($_REQUEST['sa'], $subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'posts';

	$context['page_title'] = $txt['manageposts_title'];

	// Tabs for browsing the different post functions.
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['manageposts_title'],
		'help' => 'posts_and_topics',
		'description' => $txt['manageposts_description'],
		'tabs' => array(
			'posts' => array(
				'description' => $txt['manageposts_settings_description'],
			),
			'topics' => array(
				'description' => $txt['manageposts_topic_settings_description'],
			),
			'bbc' => array(
				'description' => $txt['manageposts_bbc_settings_description'],
			),
			'editor' => array(
				'description' => $txt['manageposts_editor_settings_description'],
			),
			'censor' => array(
				'description' => $txt['admin_censored_desc'],
			),
			'drafts' => array(
				'description' => $txt['manageposts_draft_settings_description'],
			),
			'merge' => array(
				// !!! @todo: Add description
			),
		),
	);

	// Call the right function for this sub-action.
	$subActions[$_REQUEST['sa']]();
}

// Set the censored words.
function SetCensor()
{
	global $txt, $settings, $context;

	if (!empty($_POST['save_censor']))
	{
		// Make sure censoring is something they can do.
		checkSession();

		$censored_vulgar = array();
		$censored_proper = array();

		// Rip it apart, then split it into two arrays.
		if (isset($_POST['censortext']))
		{
			$_POST['censortext'] = explode("\n", strtr($_POST['censortext'], array("\r" => '')));

			foreach ($_POST['censortext'] as $c)
				list ($censored_vulgar[], $censored_proper[]) = array_pad(explode('=', trim($c)), 2, '');
		}
		elseif (isset($_POST['censor_vulgar'], $_POST['censor_proper']))
		{
			if (is_array($_POST['censor_vulgar']))
			{
				foreach ($_POST['censor_vulgar'] as $i => $value)
					if (trim(strtr($value, '*', ' ')) == '')
						unset($_POST['censor_vulgar'][$i], $_POST['censor_proper'][$i]);

				$censored_vulgar = $_POST['censor_vulgar'];
				$censored_proper = $_POST['censor_proper'];
			}
			else
			{
				$censored_vulgar = explode("\n", strtr($_POST['censor_vulgar'], array("\r" => '')));
				$censored_proper = explode("\n", strtr($_POST['censor_proper'], array("\r" => '')));
			}
		}

		// Set the new arrays and settings in the database.
		$updates = array(
			'censor_vulgar' => implode("\n", $censored_vulgar),
			'censor_proper' => implode("\n", $censored_proper),
			'censorWholeWord' => empty($_POST['censorWholeWord']) ? '0' : '1',
			'censorIgnoreCase' => empty($_POST['censorIgnoreCase']) ? '0' : '1',
			'allow_no_censored' => empty($_POST['allow_no_censored']) ? '0' : '1',
		);

		updateSettings($updates);
	}

	if (isset($_POST['censortest']))
	{
		$censorText = htmlspecialchars($_POST['censortest'], ENT_QUOTES);
		$context['censor_test'] = strtr(censorText($censorText), array('"' => '&quot;'));
	}

	// Set everything up for the template to do its thang.
	$censor_vulgar = explode("\n", $settings['censor_vulgar']);
	$censor_proper = explode("\n", $settings['censor_proper']);

	$context['censored_words'] = array();
	for ($i = 0, $n = count($censor_vulgar); $i < $n; $i++)
	{
		if (empty($censor_vulgar[$i]))
			continue;

		// Skip it, it's either spaces or stars only.
		if (trim(strtr($censor_vulgar[$i], '*', ' ')) == '')
			continue;

		$context['censored_words'][htmlspecialchars(trim($censor_vulgar[$i]))] = isset($censor_proper[$i]) ? htmlspecialchars($censor_proper[$i]) : '';
	}

	wetem::load('edit_censored');
	$context['page_title'] = $txt['admin_censored_words'];
}

// Modify all settings related to posts and posting.
function ModifyPostSettings($return_config = false)
{
	global $context, $txt, $settings;

	$context['page_title'] = $context['settings_title'] = $txt['manageposts_settings'];

	// A small hack to save us a lot of allowedTo tests in other places.
	if (isset($settings['real_edit_disable_time']))
		$settings['edit_disable_time'] = $settings['real_edit_disable_time'];

	// All the settings...
	$config_vars = array(
			// Simple post options...
			array('check', 'removeNestedQuotes'),
			array('check', 'enableEmbeddedFlash', 'subtext' => $txt['enableEmbeddedFlash_warning']),
			array('check', 'additional_options_collapsable'),
		'',
			// Posting limits...
			array('int', 'max_subjectLength', 'subtext' => $txt['max_subjectLength_zero'], 'postinput' => $txt['manageposts_characters']),
			array('int', 'max_messageLength', 'subtext' => $txt['max_messageLength_zero'], 'postinput' => $txt['manageposts_characters']),
			array('int', 'topicSummaryPosts', 'postinput' => $txt['manageposts_posts']),
		'',
			// Automatic image resizing.
			array('int', 'max_image_width'),
			array('int', 'max_image_height'),
		'',
			// Posting time limits...
			array('int', 'spamWaitTime', 'postinput' => $txt['manageposts_seconds']),
			array('int', 'edit_wait_time', 'postinput' => $txt['manageposts_seconds']),
			array('int', 'edit_disable_time', 'subtext' => $txt['edit_disable_time_zero'], 'postinput' => $txt['manageposts_minutes']),
			array('check', 'allow_non_mod_edit'),
	);

	if ($return_config)
		return $config_vars;

	// We'll want this for our easy save.
	loadSource('ManageServer');

	// Setup the template.
	wetem::load('show_settings');

	// Are we saving them - are we??
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=postsettings;sa=posts');
	}

	// Final settings...
	$context['post_url'] = '<URL>?action=admin;area=postsettings;save;sa=posts';

	// Prepare the settings...
	prepareDBSettingContext($config_vars);
}

// Bulletin Board Code...a lot of Bulletin Board Code.
function ModifyBBCSettings($return_config = false)
{
	global $context, $txt, $settings;

	$config_vars = array(
			// Main tweaks
			array('check', 'enableBBC'),
			array('check', 'enablePostHTML'),
			array('check', 'autoLinkUrls'),
		'',
			array('bbc', 'disabledBBC'),
	);

	if ($return_config)
		return $config_vars;

	// Setup the template.
	loadSource('ManageServer');
	wetem::load('show_settings');
	$context['page_title'] = $txt['manageposts_bbc_settings_title'];

	// Make sure we check the right tags!
	$settings['bbc_disabled_disabledBBC'] = empty($settings['disabledBBC']) ? array() : explode(',', $settings['disabledBBC']);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// Clean up the tags.
		$bbcTags = array();
		foreach (parse_bbc(false) as $tag)
			$bbcTags[] = $tag['tag'];

		if (!isset($_POST['disabledBBC_enabledTags']))
			$_POST['disabledBBC_enabledTags'] = array();
		elseif (!is_array($_POST['disabledBBC_enabledTags']))
			$_POST['disabledBBC_enabledTags'] = array($_POST['disabledBBC_enabledTags']);
		// Work out what is actually disabled!
		$_POST['disabledBBC'] = implode(',', array_diff($bbcTags, $_POST['disabledBBC_enabledTags']));

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=postsettings;sa=bbc');
	}

	$context['post_url'] = '<URL>?action=admin;area=postsettings;save;sa=bbc';
	$context['settings_title'] = $txt['manageposts_bbc_settings_title'];

	prepareDBSettingContext($config_vars);
}

// Options for the editor
function ModifyPostEditorSettings($return_config = false)
{
	global $context, $txt;

	$context['page_title'] = $context['settings_title'] = $txt['manageposts_editor_settings_title'];

	// All the settings...
	$config_vars = array(
			array('check', 'disable_wysiwyg'),
			array('large_text', 'editorSizes', 'subtext' => $txt['editorSizes_subtext']),
			array('large_text', 'editorFonts', 'subtext' => $txt['editorFonts_subtext']),
	);

	if ($return_config)
		return $config_vars;

	// We'll want this for our easy save.
	loadSource('ManageServer');

	// Setup the template.
	wetem::load('show_settings');

	// Are we saving them - are we??
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=postsettings;sa=editor');
	}

	// Final settings...
	$context['post_url'] = '<URL>?action=admin;area=postsettings;save;sa=editor';

	// Prepare the settings...
	prepareDBSettingContext($config_vars);
}

// Function for modifying topic settings. Not very exciting.
function ModifyTopicSettings($return_config = false)
{
	global $context, $txt;

	$context['page_title'] = $context['settings_title'] = $txt['manageposts_topic_settings'];

	// Here are all the topic settings.
	$config_vars = array(
			// Some simple bools...
			array('check', 'enableParticipation'),
		'',
			array('check', 'correctExclamations'),
			array('percent', 'correctShouting'),
		'',
			// Pagination etc...
			array('int', 'oldTopicDays', 'postinput' => $txt['manageposts_days'], 'subtext' => $txt['oldTopicDays_zero']),
			array('int', 'defaultMaxTopics', 'postinput' => $txt['manageposts_topics']),
			array('int', 'defaultMaxMessages', 'postinput' => $txt['manageposts_posts']),
		'',
			// All, next/prev...
			array('int', 'enableAllMessages', 'postinput' => $txt['manageposts_posts'], 'subtext' => $txt['enableAllMessages_zero']),
			array('check', 'disableCustomPerPage'),
			array('check', 'enablePreviousNext'),
		'',
			// Moving of topics
			array('check', 'ignoreMoveVsNew'),
	);

	if ($return_config)
		return $config_vars;

	// Get the settings template ready.
	loadSource('ManageServer');

	// Setup the template.
	wetem::load('show_settings');

	// Are we saving them - are we??
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=postsettings;sa=topics');
	}

	// Final settings...
	$context['post_url'] = '<URL>?action=admin;area=postsettings;save;sa=topics';

	// Prepare the settings...
	prepareDBSettingContext($config_vars);
}

// Function for modifying drafts settings. Not very exciting.
function ModifyDraftSettings($return_config = false)
{
	global $context, $txt;

	$context['page_title'] = $context['settings_title'] = $txt['manageposts_draft_settings'];

	// Here are all the topic settings.
	$config_vars = array(
			array('check', 'masterSavePostDrafts', 'subtext' => $txt['draftsave_subnote']),
			array('check', 'masterAutoSavePostDrafts', 'subtext' => $txt['draftautosave_subnote']),
			array('int', 'masterAutoSaveDraftsDelay', 'postinput' => $txt['manageposts_seconds']),
			array('int', 'pruneSaveDrafts', 'subtext' => $txt['oldTopicDays_zero']),
	);

	if ($return_config)
		return $config_vars;

	// Get the settings template ready.
	loadSource('ManageServer');

	// Setup the template.
	wetem::load('show_settings');

	// Are we saving them - are we??
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		redirectexit('action=admin;area=postsettings;sa=drafts');
	}

	// Final settings...
	$context['post_url'] = '<URL>?action=admin;area=postsettings;save;sa=drafts';

	// Prepare the settings...
	prepareDBSettingContext($config_vars);
}

function ModifyMergeSettings($return_config = false)
{
	global $txt, $context;

	$context['page_title'] = $context['settings_title'] = $txt['merge_post_header'];

	$config_vars = array(
			// Automatic merge options
			array('check', 'merge_post_auto'),
			array('int', 'merge_post_auto_time', 'subtext' => $txt['merge_post_auto_time_subtext']),
		'',
			// Admins can make double posts
			array('check', 'merge_post_admin_double_post'),
		'',
			// Merging options
			array('check', 'merge_post_no_time'),
			array('check', 'merge_post_no_sep'),
			array('check', 'merge_post_custom_separator', 'subtext' => $txt['merge_post_custom_separator_subtext']),
			array('large_text', 'merge_post_separator', 5, 'subtext' => $txt['merge_post_separator_subtext']),
		'',
			array('check', 'merge_post_ignore_length'),
	);

	if ($return_config)
		return $config_vars;

	// We'll want this for our easy save.
	loadSource('ManageServer');

	// Setup the template.
	$context['page_title'] = $txt['merge_post_header'];
	wetem::load('show_settings');

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		saveDBSettings($config_vars);
		writeLog();

		redirectexit('action=admin;area=postsettings;sa=merge');
	}

	$context['post_url'] = '<URL>?action=admin;area=postsettings;save;sa=merge';

	prepareDBSettingContext($config_vars);
}
