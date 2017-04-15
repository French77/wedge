<?php
/**
 * Displays a given image CAPTCHA.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * License: http://wedge.org/license/
 */

// Wedge CAPTCHA: ledicons_anim

class captcha_ledicons_anim extends captcha_ledicons
{
	public $is_available = false;
	protected $font; // the font data will be in here
	protected $anim;
	protected $leds = array(); // where we internally store the leds

	public function __construct()
	{
		global $settings;
		$this->is_available = !empty($settings['use_animated_captcha']);
	}

	public function render($code)
	{
		// Begin by storing the code in the object, setting up sizes (common method), firing up the animator, then making the images we need
		$this->code = $code;
		$this->init();
		loadSource('Class-GifAnimator');
		$this->anim = new GIF_Animator();

		$this->create_led('dull');
		$this->create_led('lit');

		// For the following, 0 indicates dull, 1 indicates lit, -1 indicates transparent. The first frame is all 0, dull ones.
		$framestore = array(0 => $this->create_filled_frame($this->pointwidth, $this->pointheight, 0));

		// Now we figure out and store the letters
		$letterstore = $this->get_lettermap();

		// Now we draw the letters on.
		for ($i = $this->pointwidth - 1; $i >= 0; $i--)
		{
			// Start with a transparent frame
			$frameno = $this->pointwidth - $i;
			$framestore[$frameno] = $this->create_filled_frame($this->pointwidth, $this->pointheight, -1);

			// Now we look at the bits we're drawing. Frame 1, for example, draws 1 line.
			for ($draw_width = 0; $draw_width < $frameno; $draw_width++)
				for ($ypos = 0; $ypos < $this->pointheight; $ypos++)
				{
					// Iterate back through the frames until we hit a color.
					// We're guaranteed to hit a color eventually since frame 0 has a color for every point.
					$prev_col = -1;
					$fpos = $frameno;
					while ($prev_col == -1)
						$prev_col = $framestore[--$fpos][$ypos][$i + $draw_width];

					if ($prev_col != $letterstore[$ypos][$draw_width])
						$framestore[$frameno][$ypos][$i + $draw_width] = $letterstore[$ypos][$draw_width];
				}
		}

		// Now, I guess, we can draw the frames.
		foreach ($framestore as $frameno => $frame)
		{
			list ($image, $colors) = $this->create_image($this->width, $this->height);
			imagefilledrectangle($image, 0, 0, $this->width, $this->height, $frameno == 0 ? $colors['black'] : $colors['purple']);
			imagecolortransparent($image, $colors['purple']);

			foreach ($frame as $ypos => $row)
				foreach ($row as $xpos => $value)
					if ($value == 0)
						$this->paint_led('dull', 2 + 5 * $xpos, ($this->pointheight - $ypos - 1) * 6 + 2, $image);
					elseif ($value == 1)
						$this->paint_led('lit', 2 + 5 * $xpos, ($this->pointheight - $ypos - 1) * 6 + 2, $image);

			$this->anim->AddFrame($image, mt_rand(5,8), $colors['purple']);
			imagedestroy($image);
		}

		$this->anim->AssembleFrames(1); // play through once
		$this->anim->Output();
		exit;
	}

	protected function get_lettermap()
	{
		$letterstore = array();
		$xpos = 0;
		for ($i = 0, $n = strlen($this->code); $i < $n; $i++)
		{
			$thisletter = substr($this->code, $i, 1);
			foreach ($this->font[$thisletter][$this->pointheight] as $column)
			{
				for ($ypos = 0; $ypos < $this->pointheight; $ypos++)
					$letterstore[$ypos][$xpos] = (pow(2, $ypos) & $column) ? 1 : 0;
				$xpos++;
			}
		}
		return $letterstore;
	}

	protected function create_filled_frame($width, $height, $populate)
	{
		$array = array();
		for ($y = 0; $y < $width; $y++)
			for ($x = 0; $x < $width; $x++)
				$array[$y][$x] = $populate;
		return $array;
	}

	protected function create_image($width, $height)
	{
		$image = imagecreate($width, $height);
		$colors = $this->assign_colors($image);
		return array($image, $colors);
	}

	protected function create_led($id, $background = false, $lowlight_col = '', $highlight_col = '')
	{
		if (isset($this->leds[$id]))
			return false;

		list ($this->leds[$id], $colors) = $this->create_image(4, 4);

		imagefilledrectangle($this->leds[$id], 0, 0, 3, 3, $colors[$id . '_lo']);
		imagesetpixel($this->leds[$id], 0, 0, $colors['purple']); // transparent corners
		imagesetpixel($this->leds[$id], 0, 3, $colors['purple']);
		imagesetpixel($this->leds[$id], 3, 0, $colors['purple']);
		imagesetpixel($this->leds[$id], 3, 3, $colors['purple']);
		imagesetpixel($this->leds[$id], 1, 1, $colors[$id . '_hi']); // highlight
		imagecolortransparent($this->leds[$id], $colors['purple']);
		return true;
	}

	protected function paint_led($id, $x, $y, $image = null)
	{
		imagecopy($image, $this->leds[$id], $x, $y, 0, 0, 4, 4);
	}

	protected function assign_colors($image)
	{
		static $lit_color = null, $colors;

		if ($lit_color === null)
			$lit_color = array_rand($this->colors);

		return array(
			'black' => imagecolorallocate($image, 0, 0, 0),
			'purple' => imagecolorallocate($image, 255, 0, 255),
			'lit_lo' => imagecolorallocate($image, $this->colors[$lit_color][0][0], $this->colors[$lit_color][0][1], $this->colors[$lit_color][0][2]),
			'lit_hi' => imagecolorallocate($image, $this->colors[$lit_color][1][0], $this->colors[$lit_color][1][1], $this->colors[$lit_color][1][2]),
			'dull_lo' => imagecolorallocate($image, 63, 63, 63),
			'dull_hi' => imagecolorallocate($image, 100, 100, 100),
		);
	}
}
