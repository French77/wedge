/*!
 * Wedge
 *
 * These are the core JavaScript functions used on most pages generated by Wedge.
 *
 * @package wedge
 * @copyright 2010-2012 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

var
	oThought,
	weEditors = [],
	_formSubmitted = false,

	// Basic browser detection
	ua = navigator.userAgent.toLowerCase(),
	can_ajax = $.support.ajax,

	// If you need support for more versions, just test for $.browser.version yourself...
	is_opera = !!$.browser.opera,
	is_opera95up = is_opera && $.browser.version >= 9.5,

	is_ff = !is_opera && ua.indexOf('gecko/') != -1 && ua.indexOf('like gecko') == -1,
	is_gecko = !is_opera && ua.indexOf('gecko') != -1,

	// The webkit ones. Oh my, that's a long list... Right now we're only supporting iOS and generic Android browsers.
	is_webkit = !!$.browser.webkit,
	is_chrome = ua.indexOf('chrome') != -1,
	is_iphone = is_webkit && (ua.indexOf('iphone') != -1 || ua.indexOf('ipod') != -1),
	is_tablet = is_webkit && ua.indexOf('ipad') != -1,
	is_android = is_webkit && ua.indexOf('android') != -1,
	is_safari = is_webkit && !is_chrome && !is_iphone && !is_android && !is_tablet,

	// This should allow us to catch more touch devices like smartphones and tablets...
	is_touch = 'ontouchstart' in document.documentElement,

	// IE gets version variables as well. Do you have to ask why..?
	is_ie = !!$.browser.msie && !is_opera,
	is_ie6 = is_ie && $.browser.version == 6,
	is_ie7 = is_ie && $.browser.version == 7,
	is_ie8 = is_ie && $.browser.version == 8,
	is_ie8down = is_ie && $.browser.version < 9,
	is_ie9up = is_ie && !is_ie8down;

// Load an XML document using Ajax.
function getXMLDocument(sUrl, funcCallback, undefined)
{
	return $.ajax($.extend({ url: sUrl, context: this }, funcCallback !== undefined ? { success: funcCallback } : { async: false }));
}

// Send a post form to the server using Ajax.
function sendXMLDocument(sUrl, sContent, funcCallback, undefined)
{
	return $.ajax($.extend({ url: sUrl, data: sContent, type: 'POST', context: this }, funcCallback !== undefined ? { success: funcCallback } : {})) || true;
}

String.prototype.php_urlencode = function ()
{
	return encodeURIComponent(this);
};

String.prototype.php_htmlspecialchars = function ()
{
	return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

String.prototype.php_unhtmlspecialchars = function ()
{
	return this.replace(/&quot;/g, '"').replace(/&gt;/g, '>').replace(/&lt;/g, '<').replace(/&amp;/g, '&');
};

String.prototype.wereplace = function (oReplacements)
{
	var sSearch, sResult = this;
	// .replace() uses $ as a meta-character in replacement strings, so we need to convert it to $$$$ first.
	for (sSearch in oReplacements)
		sResult = sResult.replace(new RegExp('%' + sSearch + '%', 'g'), (oReplacements[sSearch] + '').replace(/\$/g, '$$$$'));

	return sResult;
};


// Open a new popup window.
function reqWin(from, alternateWidth, alternateHeight, noScrollbars, noDrag, asWindow)
{
	var
		help_page = from && from.href ? from.href : from,
		vpw = $(window).width() * 0.8, vph = $(window).height() * 0.8, nextSib,
		helf = '#helf', $helf = $(helf), previousTarget = $helf.data('src'), auto = 'auto', title = $(from).text();

	alternateWidth = alternateWidth ? alternateWidth : 480;
	if ((vpw < alternateWidth) || (alternateHeight && vph < alternateHeight))
	{
		noScrollbars = 0;
		alternateWidth = Math.min(alternateWidth, vpw);
		alternateHeight = Math.min(alternateHeight, vph);
	}
	else
		noScrollbars = noScrollbars && (noScrollbars === true);

	if (asWindow)
	{
		window.open(help_page, 'requested_popup', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=' + (noScrollbars ? 'no' : 'yes') + ',width=' + (alternateWidth ? alternateWidth : 480) + ',height=' + (alternateHeight ? alternateHeight : 220) + ',resizable=no');
		return false;
	}

	// Try and get the title for the current link.
	if (!title)
	{
		nextSib = from.nextSibling;
		// Newlines are seen as stand-alone text nodes, so skip these...
		while (nextSib && nextSib.nodeType == 3 && $.trim($(nextSib).text()) === '')
			nextSib = nextSib.nextSibling;
		// Get the final text, remove any dfn (description) tags, and trim the rest.
		title = $.trim($(nextSib).clone().find('dfn').remove().end().text());
	}

	// If the reqWin event was created on the fly, it'll bubble up to the body and cancel itself... Avoid that.
	$.event.fix(window.event || {}).stopPropagation();

	// Clicking the help icon twice should close the popup and remove the global click event.
	if ($('body').unbind('click.h') && $helf.remove().length && previousTarget == help_page)
		return false;

	// We create the popup inside a dummy div to fix positioning in freakin' IE.
	$('<div class="wrc' + (noDrag && (noDrag === true) ? ' nodrag' : '') + '"></div>')
		.hide()
		.load(help_page, function () {
			if (title)
				$(this).first().prepend('<h6>' + title + '</h6>');
			$(this).css({
				overflow: noScrollbars ? 'hidden' : auto,
				width: alternateWidth - 25,
				height: alternateHeight ? alternateHeight - 20 : auto,
				padding: '10px 12px 12px',
				border: '1px solid #999'
			}).fadeIn(300);
			$(helf).dragslide();
		}).appendTo(
			$('<div id="helf"></div>').data('src', help_page).css({
				position: is_ie6 ? 'absolute' : 'fixed',
				width: alternateWidth,
				height: alternateHeight ? alternateHeight : auto,
				bottom: 10,
				right: 10
			}).appendTo('body')
		);

	// Clicking anywhere on the page should close the popup. The namespace is for the earlier unbind().
	$(document).bind('click.h', function (e) {
		// If we clicked somewhere in the popup, don't close it, because we may want to select text.
		if (!$(e.srcElement).closest(helf).length)
		{
			$(helf).remove();
			$(this).unbind(e);
		}
	});

	// Return false so the click won't follow the link ;)
	return false;
}

// Only allow form submission ONCE.
function submitonce()
{
	_formSubmitted = true;

	// If there are any editors warn them submit is coming!
	$.each(weEditors, function () { this.doSubmit(); });
}

function submitThisOnce(oControl)
{
	$('textarea', oControl.form || oControl).attr('readOnly', true);
	return !_formSubmitted;
}

// Checks for variable in an array.
function in_array(variable, theArray)
{
	return $.inArray(variable, theArray) != -1;
}

// Invert all checkboxes at once by clicking a single checkbox.
function invertAll(oInvertCheckbox, oForm, sMask)
{
	$.each(oForm, function () {
		if (this.name && !this.disabled && (!sMask || this.name.substr(0, sMask.length) == sMask || this.id.substr(0, sMask.length) == sMask))
			this.checked = oInvertCheckbox.checked;
	});
}

// Bind all delayed inline events to their respective DOM elements.
function bindEvents(items)
{
	$(items || '*[data-eve]').each(function ()
	{
		var that = $(this);
		$.each(that.attr('data-eve').split(' '), function () {
			that.bind(eves[this][0], eves[this][1]);
		});
	});
}

// Keep the session alive - always!
(function () {
	var lastKeepAliveCheck = +new Date();

	function sessionKeepAlive()
	{
		var curTime = +new Date();

		// Prevent a Firefox bug from hammering the server.
		if (we_script && curTime - lastKeepAliveCheck > 9e5)
		{
			new Image().src = weUrl() + 'action=keepalive;time=' + curTime;
			lastKeepAliveCheck = curTime;
		}
		setTimeout(sessionKeepAlive, 12e5);
	}

	setTimeout(sessionKeepAlive, 12e5);
})();



// Shows the page numbers by clicking the dots.
function expandPages(spanNode, firstPage, lastPage, perPage)
{
	var i = firstPage, pageLimit = 50, baseURL = $(spanNode).data('href');

	// Prevent too many pages from being loaded at once.
	if ((lastPage - firstPage) / perPage > pageLimit)
	{
		var oldLastPage = lastPage;
		lastPage = firstPage + perPage * pageLimit;
	}

	// Calculate the new pages.
	for (; i < lastPage; i += perPage)
		$(spanNode).before('<a href="' + baseURL.replace(/%1\$d/, i).replace(/%%/g, '%') + '">' + (1 + i / perPage) + '</a> ');

	if (oldLastPage)
		$(spanNode).before($(spanNode).clone().click(function () { expandPages(this, lastPage, oldLastPage, perPage); }));

	$(spanNode).remove();
}

// Create the div for the indicator, and add the image, link to turn it off, and loading text.
function show_ajax()
{
	$('<div id="ajax_in_progress"></div>')
		.html('<a href="#" onclick="hide_ajax();" title="' + (we_cancel || '') + '"></a>' + we_loading)
		.css(is_ie6 ? { position: 'absolute', top: $(document).scrollTop() } : {}).appendTo('body');
}

function hide_ajax()
{
	$('#ajax_in_progress').remove();
}

// Rating boxes in Media area.
function ajaxRating()
{
	show_ajax();
	sendXMLDocument(
		$('#ratingF').attr('action') + ';xml',
		'rating=' + $('#rating').val(),
		function (XMLDoc) {
			$('#ratingE').html($('item', XMLDoc).text());
			$('#rating').sb();
			hide_ajax();
		}
	);
}

// This function takes an URL (by default the script URL), and adds a question mark (or semicolon)
// so we can append a query string to it. It also replaces the host name with the current one,
// which is sometimes required for security reasons.
function weUrl(url)
{
	url = url || we_script;
	return (url + (url.indexOf('?') == -1 ? '?' : (url.search(/[?&;]$/) ? '' : ';')))
			.replace(/:\/\/[^\/]+/g, '://' + window.location.host);
}

// Get the text in a code tag.
function weSelectText(oCurElement)
{
	// The place we're looking for is one div up, and next door - if it's auto detect.
	var oCodeArea = oCurElement.parentNode.nextSibling, oCurRange;

	if (!!oCodeArea)
	{
		// Start off with IE
		if ('createTextRange' in document.body)
		{
			oCurRange = document.body.createTextRange();
			oCurRange.moveToElementText(oCodeArea);
			oCurRange.select();
		}
		// Firefox et al.
		else if (window.getSelection)
		{
			var oCurSelection = window.getSelection();
			// Safari is special!
			if (oCurSelection.setBaseAndExtent)
			{
				var oLastChild = oCodeArea.lastChild;
				oCurSelection.setBaseAndExtent(oCodeArea, 0, oLastChild, (oLastChild.innerText || oLastChild.textContent).length);
			}
			else
			{
				oCurRange = document.createRange();
				oCurRange.selectNodeContents(oCodeArea);

				oCurSelection.removeAllRanges();
				oCurSelection.addRange(oCurRange);
			}
		}
	}

	return false;
}


(function ()
{
	var origMouse, currentPos, is_fixed, currentDrag = 0;

	// You may set an area as non-draggable by adding the nodrag class to it.
	// This way, you can drag the element, but still access UI elements within it.
	$.fn.dragslide = function () {
		// Updates the position during the dragging process
		$(document)
			.mousemove(function (e) {
				if (currentDrag)
				{
					// If it's in a fixed position, it's a bottom-right aligned popup.
					$(currentDrag).css(is_fixed ? {
						right: currentPos.X - e.pageX + origMouse.X,
						bottom: currentPos.Y - e.pageY + origMouse.Y
					} : {
						left: currentPos.X + e.pageX - origMouse.X,
						top: currentPos.Y + e.pageY - origMouse.Y
					});
					return false;
				}
			})
			.mouseup(function () {
				if (currentDrag)
					return !!(currentDrag = 0);
			});

		return this
			.css('cursor', 'move')
			// Start the dragging process
			.mousedown(function (e) {
				if ($(e.target).closest('.nodrag').length)
					return true;
				is_fixed = this.style.position == 'fixed';

				// Position it to absolute, except if it's already fixed
				$(this).css({ position: is_fixed ? 'fixed' : 'absolute', zIndex: 999 });

				origMouse = { X: e.pageX, Y: e.pageY };
				currentPos = { X: parseInt(is_fixed ? this.style.right : this.offsetLeft, 10), Y: parseInt(is_fixed ? this.style.bottom : this.offsetTop, 10) };
				currentDrag = this;

				return false;
			})
			.find('.nodrag')
			.css('cursor', 'default');
	};

})();


/**
 * Dropdown menu in JS with CSS fallback, Nao style.
 * May not show, but it took years to refine it.
 */

(function ()
{
	var menu_baseId = 0, menu_delay = [], hove = 'hove',

	// Entering a menu entry?
	menu_show_me = function ()
	{
		var
			hasul = $('ul', this)[0], style = hasul ? hasul.style : {}, is_visible = style.visibility == 'visible',
			id = this.id, parent = this.parentNode, is_top = parent.className == 'menu', d = document.dir, w = parent.clientWidth;

		if (hasul)
		{
			style.visibility = 'visible';
			style.opacity = 1;
			style['margin' + (d && d == 'rtl' ? 'Right' : 'Left')] = (is_top ? $('span', this).width() || 0 : w - 5) + 'px';
		}

		if (!is_top || !$('h4', this).first().addClass(hove).length)
			$(this).addClass(hove).parentsUntil('.menu>li').filter('li').addClass(hove);

		if (!is_visible)
			$('ul', this).first()
				.css(is_top ? { marginTop: is_ie6 || is_ie7 ? 9 : 36 } : { marginLeft: w })
				.animate(is_top ? { marginTop: is_ie6 || is_ie7 ? 6 : 33 } : { marginLeft: w - 5 }, 300);

		clearTimeout(menu_delay[id.substring(2)]);

		$(this).siblings('li').each(function () { menu_hide_children(this.id); });
	},

	// Leaving a menu entry?
	menu_hide_me = function (e)
	{
		// The deepest level should hide the hover class immediately.
		if (!$(this).children('ul').length)
			$(this).children().andSelf().removeClass(hove);

		// Are we leaving the menu entirely, and thus triggering the time
		// threshold, or are we just switching to another menu item?
		var id = this.id;
		$(e.relatedTarget).closest('.menu').length ?
			menu_hide_children(id) :
			menu_delay[id.substring(2)] = setTimeout(function () { menu_hide_children(id); }, 250);
	},

	// Hide all children menus.
	menu_hide_children = function (id)
	{
		$('#' + id).children().andSelf().removeClass(hove).find('ul').css({ visibility: 'hidden' }).css(is_ie8down ? '' : 'opacity', 0);
	};

	// Make sure to only call this on one element...
	$.fn.menu = function ()
	{
		var $elem = this.show();
		this.find('h4+ul').prepend('<li class="menu-top"></li>');
		this.find('li').each(function () {
			$(this).attr('id', 'li' + menu_baseId++)
				.bind('mouseenter focus', menu_show_me)
				.bind('mouseleave blur', menu_hide_me)
				// Disable double clicks...
				.mousedown(false)
				// Clicking a link will immediately close the menu -- giving a feeling of responsiveness.
				.filter(':has(>a,>h4>a)')
				.click(function () {
					$('.' + hove).removeClass(hove);
					$elem.find('ul').css({ visibility: 'hidden' }).css(is_ie8down ? '' : 'opacity', 0);
				});
		});

		// Now that JS is ready to take action... Disable the pure CSS menu!
		$('.css.menu').removeClass('css');
		return this;
	};
})();


// *** weToggle class.
function weToggle(opt)
{
	var
		that = this,
		collapsed = false,
		toggle_me = function () {
			$(this).data('that').toggle();
			this.blur();
			return false;
		};

	// Change State - collapse or expand the section.
	this.cs = function (bCollapse, bInit)
	{
		// Handle custom function hook before collapse.
		if (!bInit && bCollapse && opt.funcOnBeforeCollapse)
			opt.funcOnBeforeCollapse.call(this);

		// Handle custom function hook before expand.
		else if (!bInit && !bCollapse && opt.funcOnBeforeExpand)
			opt.funcOnBeforeExpand.call(this);

		// Loop through all the images that need to be toggled.
		$.each(opt.aSwapImages || [], function () {
			$('#' + this.sId).toggleClass('fold', !bCollapse).attr('title', bCollapse && this.altCollapsed ? this.altCollapsed : this.altExpanded);
		});

		// Loop through all the links that need to be toggled.
		$.each(opt.aSwapLinks || [], function () {
			$('#' + this.sId).html(bCollapse && this.msgCollapsed ? this.msgCollapsed : this.msgExpanded);
		});

		// Now go through all the sections to be collapsed.
		$.each(opt.aSwappableContainers, function () {
			bCollapse ? $('#' + this).slideUp(bInit ? 0 : 300) : $('#' + this).slideDown(bInit ? 0 : 300);
		});

		// Update the new state.
		collapsed = +bCollapse;

		// Update the cookie, if desired.
		if (opt.sCookie)
			document.cookie = opt.sCookie + '=' + collapsed;

		if (!bInit && opt.sOptionName)
			// Set a theme option through javascript.
			new Image().src = weUrl() + 'action=jsoption;var=' + opt.sOptionName + ';val=' + collapsed + ';'
								+ we_sessvar + '=' + we_sessid + (opt.sExtra || '') + ';time=' + +new Date();
	};

	// Reverse the current state.
	this.toggle = function ()
	{
		this.cs(!collapsed);
	};

	// Note that this is only used in stats.js...
	this.opt = opt;

	// If the init state is set to be collapsed, collapse it.
	// If cookies are enabled and our toggler cookie is set to '1', override the initial state.
	// Note: the cookie retrieval code is below, you can turn it into a function by replacing opt.sCookie with a param.
	// It's not used anywhere else in Wedge, which is why we won't bother with a weCookie object.
	if (opt.bCurrentlyCollapsed || (opt.sCookie && document.cookie.search('\\b' + opt.sCookie + '\\s*=\\s*1\\b') != -1))
		this.cs(true, true);

	// Initialize the images to be clickable.
	$.each(opt.aSwapImages || [], function () {
		$('#' + this.sId).show().data('that', that).click(toggle_me).css({ visibility: 'visible' }).css('cursor', 'pointer').mousedown(false);
	});

	// Initialize links.
	$.each(opt.aSwapLinks || [], function () {
		$('#' + this.sId).show().data('that', that).click(toggle_me);
	});
}


// *** JumpTo class.
function JumpTo(control, id)
{
	if (can_ajax)
		$('#' + control)
			.html('<select><option data-hide>=> ' + $('#' + control).text() + '</option></select>')
			.css({ visibility: 'visible' })
			.find('select').sb().focus(function ()
			{
				var sList = '', $val, that, name;

				show_ajax();

				// Fill the select box with entries loaded through Ajax.
				$('we item', getXMLDocument(weUrl() + 'action=ajax;sa=jumpto;xml').responseXML).each(function ()
				{
					that = $(this);
					// This removes entities from the name...
					name = that.text().replace(/&(amp;)?#(\d+);/g, function (sInput, sDummy, sNum) { return String.fromCharCode(+sNum); });

					// Just for the record, we don't NEED to close the optgroup at the end
					// of the list, even if it doesn't feel right. Saves us a few bytes...
					if (that.attr('type') == 'c') // Category?
						sList += '<optgroup label="' + name + '">';
					else
						// Show the board option, with special treatment for the current one.
						sList += '<option value="' + (that.attr('url') || that.attr('id')) + '"'
								+ (that.attr('id') == id ? ' disabled>=> ' + name + ' &lt;=' :
									'>' + new Array(+that.attr('level') + 1).join('&nbsp;&nbsp;&nbsp;&nbsp;') + name)
								+ '</option>';
				});

				// Add the remaining items after the currently selected item.
				$('#' + control).find('select').unbind('focus').append(sList).sb().change(function () {
					window.location.href = parseInt($val = $(this).val()) ? weUrl() + 'board=' + $val + '.0' : $val;
				});

				hide_ajax();
			});
}


// *** Thought class.
function Thought(opt)
{
	var
		ajaxUrl = weUrl() + 'action=ajax;sa=thought;xml;',

		// Make that personal text editable (again)!
		cancel = function () {
			$('#thought_form').siblings().show().end().remove();
		},

		interact_thoughts = function ()
		{
			var thought = $(this), tid = thought.data('tid'), mid = thought.data('mid') || '';
			if (tid)
				thought.after('\
		<div class="thought_actions">' + (thought.data('self') !== '' ? '' : '\
			<input type="button" class="submit" value="' + opt.sEdit + '" onclick="oThought.edit(' + tid + ', \'' + mid + '\');">\
			<input type="button" class="delete" value="' + opt.sDelete + '" onclick="oThought.remove(' + tid + ');">') + '\
			<input type="button" class="new" value="' + opt.sReply + '" onclick="oThought.edit(' + tid + ', \'' + mid + '\', true);">\
		</div>');
		};

	// Show the input after the user has clicked the text.
	this.edit = function (tid, mid, is_new, text, p)
	{
		cancel();

		var
			thought = $('#thought_update' + tid), was_personal = thought.find('span').first().html(),
			privacies = opt.aPrivacy, privacy = (thought.data('prv') + '').split(','),

			cur_text = is_new ? text || '' : (was_personal.toLowerCase() == opt.sNoText.toLowerCase() ? '' : (was_personal.indexOf('<') == -1 ?
			was_personal.php_unhtmlspecialchars() : $('thought', getXMLDocument(ajaxUrl + 'in=' + tid).responseXML).text())),

			pr = '';

		for (p in privacies)
			pr += '<option value="' + privacies[p][0] + '"' + (in_array(privacies[p][0] + '', privacy) ? ' selected' : '') + '>&lt;div class="privacy_' + privacies[p][1] + '"&gt;&lt;/div&gt;' + privacies[p][2] + '</option>';

		// Hide current thought and edit/modify/delete links, and add tools to write new thought.
		thought.toggle(tid && is_new).after('<form id="thought_form"><input type="text" maxlength="255" id="ntho"><select id="npriv">'
			+ pr + '</select><input type="hidden" id="noid" value="' + (is_new ? 0 : thought.data('oid')) + '"><input type="submit" value="'
			+ opt.sSubmit + '" onclick="oThought.submit(\'' + tid + '\', \'' + (mid || tid) + '\'); return false;" class="save"><input type="button" value="'
			+ opt.sCancel + '" onclick="oThought.cancel(); return false;" class="cancel"></form>').siblings('.thought_actions').hide();
		$('#ntho').focus().val(cur_text);
		$('#npriv').sb();
	};

	// Event handler for removal requests.
	this.remove = function (tid)
	{
		var toDelete = $('#thought_update' + tid);

		show_ajax();

		sendXMLDocument(ajaxUrl + 'remove', 'oid=' + toDelete.data('oid'));

		// We'll be assuming Wedge uses table tags to show thought lists.
		toDelete.closest('tr').remove();

		hide_ajax();
	};

	// Event handler for clicking submit.
	this.submit = function (tid, mid)
	{
		show_ajax();

		sendXMLDocument(
			ajaxUrl,
			'parent=' + tid + '&master=' + mid + '&oid=' + $('#noid').val().php_urlencode() + '&privacy=' + $('#npriv').val().php_urlencode() + '&text=' + $('#ntho').val().php_urlencode(),
			function (XMLDoc)
			{
				var thought = $('thought', XMLDoc), nid = tid ? thought.attr('id') : tid, new_thought = $('#new_thought'), new_id = '#thought_update' + nid, user = $('user', XMLDoc);
				if (!$(new_id).length)
					new_thought.after($('<tr class="windowbg">').html(new_thought.html().wereplace({
						date: $('date', XMLDoc).text(),
						uname: user.text(),
						text: thought.text()
					})));
				else
					$(new_id + ' span').html(thought.text());
				$(new_id).each(interact_thoughts);
				cancel();
				hide_ajax();
			}
		);
	};

	this.cancel = cancel;

	if (can_ajax)
	{
		$('#thought_update')
			.attr('title', opt.sLabelThought)
			.click(function () { oThought.edit(''); });
		$('.thought').each(interact_thoughts);
	}
}

/* Optimize:
_formSubmitted = _f
*/
