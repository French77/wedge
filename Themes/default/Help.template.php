<?php
// Version: 2.0 RC4; Help

function template_popup()
{
	global $context, $settings, $options, $txt;

	// Since this is a popup of its own we need to start the html, etc.
	echo '<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', '>
	<head>
		<meta charset="utf-8" />
		<meta name="robots" content="noindex" />
		<title>', $context['page_title'], '</title>
		<link rel="stylesheet" type="text/css" href="', $settings['theme_url'], '/css/index.css" />
		<script src="', $settings['default_theme_url'], '/scripts/script.js"></script>
	</head>
	<body id="help_popup">
		<div class="windowbg description">
			', $context['help_text'], '<br />
			<br />
			<a href="javascript:parent.document.body.removeChild(parent.window[\'helpFrame\']); parent.window[\'helpFrame\'] = null;">', $txt['close_window'], '</a>
		</div>
	</body>
</html>';
}

function template_find_members()
{
	global $context, $settings, $options, $scripturl, $modSettings, $txt;

	echo '<!DOCTYPE html>
<html', $context['right_to_left'] ? ' dir="rtl"' : '', '>
	<head>
		<meta charset="utf-8" />
		<meta name="robots" content="noindex" />
		<title>', $txt['find_members'], '</title>
		<link rel="stylesheet" type="text/css" href="', $settings['theme_url'], '/css/index.css" />
		<script src="', $settings['default_theme_url'], '/scripts/script.js"></script>
		<script><!-- // --><![CDATA[
			var membersAdded = [];
			function addMember(name)
			{
				var theTextBox = window.opener.document.getElementById("', $context['input_box_name'], '");

				if (name in membersAdded)
					return;

				// If we only accept one name don\'t remember what is there.
				if (', JavaScriptEscape($context['delimiter']), ' != \'null\')
					membersAdded[name] = true;

				if (theTextBox.value.length < 1 || ', JavaScriptEscape($context['delimiter']), ' == \'null\')
					theTextBox.value = ', $context['quote_results'] ? '"\"" + name + "\""' : 'name', ';
				else
					theTextBox.value += ', JavaScriptEscape($context['delimiter']), ' + ', $context['quote_results'] ? '"\"" + name + "\""' : 'name', ';

				window.focus();
			}
		// ]]></script>
	</head>
	<body id="help_popup">
		<form action="', $scripturl, '?action=findmember;', $context['session_var'], '=', $context['session_id'], '" method="post" accept-charset="UTF-8" class="padding description">
			<div class="roundframe">
				<div class="cat_bar">
					<h3>', $txt['find_members'], '</h3>
				</div>
				<div class="padding">
					<strong>', $txt['find_username'], ':</strong><br />
					<input type="text" name="search" id="search" value="', isset($context['last_search']) ? $context['last_search'] : '', '" style="margin-top: 4px; width: 96%;" class="input_text" />
					<div class="smalltext"><em>', $txt['find_wildcards'], '</em></div>';

	// Only offer to search for buddies if we have some!
	if (!empty($context['show_buddies']))
		echo '
					<div class="smalltext"><label for="buddies"><input type="checkbox" class="input_check" name="buddies" id="buddies"', !empty($context['buddy_search']) ? ' checked="checked"' : '', ' /> ', $txt['find_buddies'], '</label></div>';

	echo '
					<div class="padding righttext">
						<input type="submit" value="', $txt['search'], '" class="button_submit" />
						<input type="button" value="', $txt['find_close'], '" onclick="window.close();" class="button_submit" />
					</div>
				</div>
			</div>
			<br />
			<div class="roundframe">
				<div class="cat_bar">
					<h3>', $txt['find_results'], '</h3>
				</div>';

	if (empty($context['results']))
		echo '
				<p class="error">', $txt['find_no_results'], '</p>';
	else
	{
		echo '
				<ul class="reset padding">';

		$alternate = true;
		foreach ($context['results'] as $result)
		{
			echo '
					<li class="', $alternate ? 'windowbg2' : 'windowbg', '">
						<a href="', $result['href'], '" target="_blank" class="new_win"><img src="', $settings['images_url'], '/icons/profile_sm.gif" alt="', $txt['view_profile'], '" title="', $txt['view_profile'], '" /></a>
						<a href="#" onclick="addMember(this.innerHTML); return false;">', $result['name'], '</a>
					</li>';

			$alternate = !$alternate;
		}

		echo '
				</ul>
				<div class="pagesection">
					', $txt['pages'], ': ', $context['page_index'], '
				</div>';
	}

	echo '
			</div>
			<input type="hidden" name="input" value="', $context['input_box_name'], '" />
			<input type="hidden" name="delim" value="', $context['delimiter'], '" />
			<input type="hidden" name="quote" value="', $context['quote_results'] ? '1' : '0', '" />
		</form>';

	if (empty($context['results']))
		echo '
		<script><!-- // --><![CDATA[
			document.getElementById("search").focus();
		// ]]></script>';

	echo '
	</body>
</html>';
}

?>