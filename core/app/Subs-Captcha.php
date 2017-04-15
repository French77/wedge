<?php
/**
 * Deals with instancing the specific CAPTCHA image class that is to be used, and controlling its output.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * License: http://wedge.org/license/
 */

/* TrueType fonts supplied by www.LarabieFonts.com */

if (!defined('WEDGE'))
	die('Hacking attempt...');

/*	This file deals with producing CAPTCHA images.

	bool showCodeImage(string code)
		- show an image containing the visual verification code for registration.
		- requires the GD extension.
		- returns false if something goes wrong.
*/

// Create the image for the visual verification code.
function showCodeImage($code)
{
	global $context;

	// Determine what types are available.
	$context['captcha_types'] = loadCaptchaTypes();

	if (empty($context['captcha_types']))
		return false;

	// Special case to allow the admin center to show samples.
	$imageType = (we::$is_admin && isset($_GET['type']) && in_array($_GET['type'], $context['captcha_types'])) ? $_GET['type'] : $context['captcha_types'][array_rand($context['captcha_types'])];

	$captcha = new $imageType();

	$image = $captcha->render($code);
	// We already know GIF is available, we checked on install. Display, clean up, good night.
	header('Content-type: image/gif');
	imagegif($image);
	imagedestroy($image);
	exit;
}

function loadCaptchaTypes()
{
	$captcha_types = array();
	if ($dh = scandir(APP_DIR . '/captcha'))
	{
		foreach ($dh as $file)
		{
			if (!is_dir(APP_DIR . '/captcha/' . $file) && preg_match('~captcha-([A-Za-z\d_]+)\.php$~', $file, $matches))
			{
				// Check this is definitely a valid API!
				$fp = fopen(APP_DIR . '/captcha/' . $file, 'rb');
				$header = fread($fp, 4096);
				fclose($fp);

				if (strpos($header, '// Wedge CAPTCHA: ' . $matches[1]) !== false)
				{
					loadSource('captcha/captcha-' . $matches[1]);

					$class_name = 'captcha_' . $matches[1];
					$captcha = new $class_name();

					// No Support? NEXT!
					if (!$captcha->is_available)
						continue;

					$captcha_types[] = $class_name;
				}
			}
		}
	}

	// Maybe a plugin wants to add some CAPTCHA types? If they're doing that, here's a hook. The plugin sources attached to this hook
	// probably should be individual files containing the receiver for this hook, plus the class itself, to minimize loading effort.
	call_hook('add_captcha', array(&$captcha_types));

	return $captcha_types;
}
