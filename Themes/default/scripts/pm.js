/*!
 * Wedge
 *
 * Helper functions for the personal messages send form
 *
 * @package wedge
 * @copyright 2010-2013 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

@language PersonalMessage

function wePersonalMessageSend(opt)
{
	if (!opt.bBccShowByDefault)
	{
		// Hide the BCC control.
		$('#' + opt.sBccDivId + ', #' + opt.sBccDivId2).hide();

		// Show the link to set the BCC control back.
		$('#' + opt.sBccLinkContainerId).show();

		// Make the link show the BCC control.
		$('#' + opt.sBccLinkId).click(function() {
			$('#' + opt.sBccDivId + ', #' + opt.sBccDivId2).show();
			return false;
		});
	}

	var
		oToAutoSuggest = new weAutoSuggest({
			bItemList: true,
			sControlId: opt.sToControlId,
			sPostName: 'recipient_to',
			aListItems: opt.aToRecipients
		}),
		oBccAutoSuggest = new weAutoSuggest({
			bItemList: true,
			sControlId: opt.sBccControlId,
			sPostName: 'recipient_bcc',
			aListItems: opt.aBccRecipients
		});

	oToAutoSuggest.registerCallback('onBeforeAddItem', function (sSuggestId) {
		// Prevent items to be added twice or to both the 'To' and 'Bcc'.
		oToAutoSuggest.deleteAddedItem(sSuggestId);
		oBccAutoSuggest.deleteAddedItem(sSuggestId);
		return true;
	});
	oBccAutoSuggest.registerCallback('onBeforeAddItem', function (sSuggestId) {
		// Prevent items to be added twice or to both the 'To' and 'Bcc'.
		oToAutoSuggest.deleteAddedItem(sSuggestId);
		oBccAutoSuggest.deleteAddedItem(sSuggestId);
		return true;
	});

	// Is there a contact list? If not, a search on $('# tr') will return an empty set anyway.
	$('#' + opt.sContactList + ' tr').each(function () {
		var that = $(this);
		$('<td/>')
			.append($('<input type="button" class="to">').val($txt['pm_to']).click(function () {
				oToAutoSuggest.addItemLink(that.data('uid'), that.data('name'), false);
			}))
			.append($('<input type="button" class="bcc">').val($txt['pm_bcc']).click(function () {
				$('#' + opt.sBccDivId + ', #' + opt.sBccDivId2).show();
				oBccAutoSuggest.addItemLink(that.data('uid'), that.data('name'), false);
			}))
			.appendTo(this);
	});
}

function expandCollapseLabels()
{
	$('#searchLabelsExpand').toggle(300);
	$('#expandLabelsIcon').toggleClass('fold');
}
