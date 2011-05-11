
/*!
 * Zoomedia is the lightbox component for all media Wedge.
 * Developed by Nao.
 * Uses portions by Steve Smith (http://orderedlist.com/)
 *
 * This file is released under the Wedge license.
 * More details at http://wedge.org/license/
 */

(function ($) {

	$.fn.zoomedia = function (options, onComplete) {

		var
			zoom = '#zoom',
			options = options || {},
			lang = options.lang || {},
			outline = options.outline || '',
			outline = 'drop-shadow',
			padding = outline == 'drop-shadow' ? 0 : 11,
			double_padding = outline == 'drop-shadow' ? 0 : 20,
			zooming = active = false,
			original_size = {};

		if (!$zoom)
		{
			$('body').append('<div id="zoom" class="zoom-' + outline + '"><div id="zoom-content"></div><div id="zoom-desc"></div><a href="#" title="Close" id="zoom-close"></a></div>');

			var
				$zoom = $(zoom),
				$zoom_desc = $(zoom + '-desc'),
				$zoom_close = $(zoom + '-close'),
				$zoom_content = $(zoom + '-content');

			$('html').click(function (e) {
				if (active && !$(e.target).parents(zoom + ':visible').length)
					hide();
			});
			$(document).keyup(function (e) {
				if (active && e.keyCode == 27 && $zoom.is(':visible'))
					hide();
			});

			$zoom_close.click(hide);
		}

		this.each(function () {
			$(this).click(show);
		});

		return this;

		function show(e)
		{
			if (zooming)
				return false;
			zooming = true;

			var
				url = this.href,
				$anchor = $(this),
				$ele = $anchor.children().first(),
				offset = $ele.offset();

			original_size = {
				x: offset.left,
				y: offset.top,
				w: $ele.width(),
				h: $ele.height()
			};

			var whenReady = function () {

				var
					img = this,
					win = $(window),
					win_width = win.width(),
					win_height = win.height(),
					width = (options.width || img.width) + double_padding,
					height = (options.height || img.height) + double_padding,
					on_width = win_height < height ? (width - double_padding) * (win_height / height) + double_padding : width,
					on_height = Math.min(win_height, height),
					scrollTop = is_ie8down ? document.documentElement.scrollTop : window.pageYOffset,
					scrollLeft = is_ie8down ? document.documentElement.scrollLeft : window.pageXOffset,
					desc = $anchor.next('.zoom-overlay').html() || '';

				$zoom_close.hide();
				clearTimeout(show_loading);
				$('.zoom-loading').hide();

				$zoom.css({
					top: original_size.y - padding,
					left: original_size.x - padding,
					width: original_size.w + double_padding,
					height: original_size.h + double_padding
				});

				if (options.closeOnClick)
					$zoom.click(hide);

				$zoom_content.html(options.noScale ? '' : $(img).addClass('scale'));

				$zoom_desc
					.html(desc)
					.css({
						width: on_width - double_padding,
						height: 'auto'
					});

				var $fullsize = $zoom_desc.find('.fullsize').attr('href');
				$zoom_content.unbind('dblclick').dblclick(function () {
					if ($fullsize)
					{
						$('img', this).unbind('load').load(function () {
							var
								that = this,
								wt = that.naturalWidth,
								ht = that.naturalHeight,
								rezoom = function (new_width, new_height, elem) {
									$(elem).css({
										maxWidth: new_width,
										maxHeight: new_height
									});
									// Are we already animating the zoom? If yes, the zoom description is probably hidden.
									// (Doing a double check although it's not actually needed.)
									if ($zoom.queue('fx').length > 0 && $zoom_desc.is(':hidden'))
										// Delay our zoom until after the description has finished its own.
										$zoom.delay(400);
									$zoom_close.hide();
									$zoom.animate({
										top: '-=' + (new_height - on_height) / 2,
										left: '-=' + (new_width - on_width) / 2,
										width: '+=' + (new_width - on_width),
										height: '+=' + (new_height - on_height)
									}, 500, null, function () { $zoom_close.show(); });
								};
							if (wt > 0)
								rezoom(wt, ht, that);
							else
							{
								// Stupid IE forces us to emulate natural properties through a hidden img...
								$('<img>').load(function () {
									wt = this.width;
									ht = this.height;
									$(this).remove();
									rezoom(wt, ht, that);
								}).attr('src', that.src);
							}
						}).attr('src', $fullsize);
					}
					return false;
				});

				// Is it a narrow element with a long description? If yes, enlarge its parent to at least 500px.
				if ($zoom_desc.width() > on_width - double_padding || $zoom_desc.height() > 200)
				{
					$zoom_content.find('img').css({
						maxWidth: width,
						maxHeight: height
					});
					on_width = Math.max($zoom_desc.width() + double_padding, 500 + double_padding);
				}

				$zoom_desc.hide().css('width', 'auto');
				$zoom.hide().css('visibility', 'visible').animate(
					{
						top: Math.max((win_height - on_height) / 2 + scrollTop, 0),
						left: (win_width - on_width) / 2 + scrollLeft,
						width: on_width,
						height: on_height,
						opacity: 'show'
					},
					500,
					null,
					function () {
						if (options.noScale)
							$zoom_content.html(img);

						$zoom.css('height', 'auto');
						$zoom_close.show();
						// Alt effect: .animate({ height: 'show', opacity: 'show' });
						$zoom_desc.filter(function () { return desc != ''; }).addClass('nodrag').slideDown();
						zooming = false;
						active = true;
					}
				).dragslide();
			};

			// Add the 'Loading' label. If the item is already cached, it'll hide it immediately,
			// so make sure we only show it if it's really loading something. IE6 needs a longer
			// timeout because it's slower at retrieving data from the cache.
			var show_loading = setTimeout(function () {
				$('<div class="zoom-loading">' + (lang.loading || '') + '</div>').css({
					left: original_size.x,
					top: original_size.y
				}).click(function () {
					$('<img>').unbind('load');
					$(this).remove();
					zooming = active = false;
				}).appendTo('body');
			}, is_ie6 ? 200 : 80);

			$('<img>').unbind('load').load(whenReady).attr('src', url);

			return false;
		}

		function hide()
		{
			if (zooming || !active)
				return false;
			zooming = true;
			$zoom.unbind();
			$zoom_content.unbind();

			if (options.noScale)
				$zoom_content.html('');

			$zoom_close.hide();
			$zoom_desc.animate(
				{
					height: 'hide',
					opacity: 'hide'
				},
				500
			);

			$zoom.animate(
				{
					top: original_size.y - padding,
					left: original_size.x - padding,
					width: original_size.w + double_padding,
					height: original_size.h + double_padding,
					opacity: 'hide'
				},
				500,
				null,
				function () {
					zooming = false;
					active = false;
				}
			);
			return false;
		}
	}

})(jQuery);