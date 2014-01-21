<?php
/**
 * Contains the functions that turn on and off notifications to topics or boards.
 *
 * @package Wedge
 * @copyright 2010 René-Gilles Deberdt, wedge.org
 * @license http://wedge.org/license/
 * @author see contributors.txt
 */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*	void Notify()
		- is called to turn off/on notification for a particular topic.
		- must be called with a topic specified in the URL.
		- uses the Notify template (main block.) when called with no sa.
		- the sub action can be 'on', 'off', or nothing for what to do.
		- requires the mark_any_notify permission.
		- upon successful completion of action will direct user back to topic.
		- is accessed via ?action=notify.

	void BoardNotify()
		- is called to turn off/on notification for a particular board.
		- must be called with a board specified in the URL.
		- uses the Notify template. (notify_board block.)
		- only uses the template if no sub action is used. (on/off)
		- requires the mark_notify permission.
		- redirects the user back to the board after it is done.
		- is accessed via ?action=notifyboard.
*/

// Turn on/off notifications...
function Notify()
{
	global $txt, $topic, $context;

	// Make sure they aren't a guest or something - guests can't really receive notifications!
	is_not_guest();
	isAllowedTo('mark_any_notify');

	// Make sure the topic has been specified.
	if (empty($topic))
		fatal_lang_error('not_a_topic', false);

	// What do we do? Better ask if they didn't say..
	if (empty($_GET['sa']))
	{
		// Load the template, but only if it is needed.
		loadTemplate('Notify');

		// Find out if they have notification set for this topic already.
		$request = wesql::query('
			SELECT id_member
			FROM {db_prefix}log_notify
			WHERE id_member = {int:current_member}
				AND id_topic = {int:current_topic}
			LIMIT 1',
			array(
				'current_member' => MID,
				'current_topic' => $topic,
			)
		);
		$context['notification_set'] = wesql::num_rows($request) != 0;
		wesql::free_result($request);

		// Set the template variables...
		$context['topic_href'] = SCRIPT . '?topic=' . $topic . '.' . $_REQUEST['start'];
		$context['start'] = $_REQUEST['start'];
		$context['page_title'] = $txt['notification'];

		return;
	}
	elseif ($_GET['sa'] == 'on')
	{
		checkSession('get');

		// Attempt to turn notifications on.
		wesql::insert('ignore',
			'{db_prefix}log_notify',
			array('id_member' => 'int', 'id_topic' => 'int'),
			array(MID, $topic)
		);
	}
	else
	{
		checkSession('get');

		// Just turn notifications off.
		wesql::query('
			DELETE FROM {db_prefix}log_notify
			WHERE id_member = {int:current_member}
				AND id_topic = {int:current_topic}',
			array(
				'current_member' => MID,
				'current_topic' => $topic,
			)
		);
	}

	// Send them back to the topic.
	redirectexit('topic=' . $topic . '.' . $_REQUEST['start']);
}

function BoardNotify()
{
	global $txt, $board, $context;

	// Permissions are an important part of anything ;).
	is_not_guest();
	isAllowedTo('mark_notify');

	// You have to specify a board to turn notifications on!
	if (empty($board))
		fatal_lang_error('no_board', false);

	// No subaction: find out what to do.
	if (empty($_GET['sa']))
	{
		// We're gonna need the notify template...
		loadTemplate('Notify');

		// Find out if they have notification set for this topic already.
		$request = wesql::query('
			SELECT id_member
			FROM {db_prefix}log_notify
			WHERE id_member = {int:current_member}
				AND id_board = {int:current_board}
			LIMIT 1',
			array(
				'current_board' => $board,
				'current_member' => MID,
			)
		);
		$context['notification_set'] = wesql::num_rows($request) != 0;
		wesql::free_result($request);

		// Set the template variables...
		$context['board_href'] = SCRIPT . '?board=' . $board . '.' . $_REQUEST['start'];
		$context['start'] = $_REQUEST['start'];
		$context['page_title'] = $txt['notification'];
		wetem::load('notify_board');

		return;
	}
	// Turn the board level notification on....
	elseif ($_GET['sa'] == 'on')
	{
		checkSession('get');

		// Turn notification on. (Note this just blows smoke if it's already on.)
		wesql::insert('ignore',
			'{db_prefix}log_notify',
			array('id_member' => 'int', 'id_board' => 'int'),
			array(MID, $board)
		);
	}
	// ...or off?
	else
	{
		checkSession('get');

		// Turn notification off for this board.
		wesql::query('
			DELETE FROM {db_prefix}log_notify
			WHERE id_member = {int:current_member}
				AND id_board = {int:current_board}',
			array(
				'current_board' => $board,
				'current_member' => MID,
			)
		);
	}

	// Back to the board!
	redirectexit('board=' . $board . '.' . $_REQUEST['start']);
}