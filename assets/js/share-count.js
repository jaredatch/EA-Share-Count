jQuery(document).ready(function($){
	var easc_id;
	var easc_nonce;
	$(document).on('click', '.ea-share-count-button[target="_blank"]:not(.no-js)', function(event) {
		event.preventDefault();
		var window_size = '';
		var url = this.href;
		var domain = url.split("/")[2];
		switch(domain) {
			case "www.facebook.com":
				window_size = "width=585,height=368";
				break;
			case "twitter.com":
				window_size = "width=585,height=261";
				break;
			case "plus.google.com":
				window_size = "width=517,height=511";
				break;
			case "pinterest.com":
				window_size = "width=750,height=550";
				break;
			default:
				window_size = "width=585,height=515";
		}
		window.open(url, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,' + window_size);
		$(this).trigger("ea-share-click");
	});
	$(document).on('click', 'a[href*="#ea-share-count-email"]', function(event) {
		event.preventDefault();
		$('#easc-modal-wrap').fadeIn();
		$('#easc-modal-recipient').focus();
		easc_id = $(this).data('postid');
		easc_nonce = $(this).data('nonce');
	});
	$(document).on('click', '#easc-modal-close', function(event) {
		event.preventDefault();
		$('#easc-modal-wrap').fadeOut();
		$('#easc-modal-sent').hide();
	});
	$(document).on('click', '#easc-modal-submit', function(event) {
		var empty = false;
		var recipient = $('#easc-modal-recipient');
		var name = $('#easc-modal-name');
		var email = $('#easc-modal-email');
		$(recipient,name,email).each(function() {
			if (!$(this).val()) {
				empty = true;
				coso
			}
		});
		if (empty) {
			alert('Please complete out all 3 fields to email this article.');
			return;
		}
		$(this).prop('disabled', true);
		var opts = {
			url: easc.url,
			type: 'post',
			dataType: 'json',
			data: {
				action: 'easc_email',
				postid: easc_id,
				recipient: recipient.val(),
				name: name.val(),
				email: email.val(),
				validation: $('#easc-modal-validation').val(),
				nonce: easc_nonce
			},
			success: function(res){
				if (res.success) {
					console.log('Article successfully shared');
				}
				$('#easc-modal-sent').fadeIn();
				$(recipient,name,email).val('');
				$(this).prop('disabled', false);
				setTimeout(function(){
					$('#easc-modal-wrap,#easc-modal-sent').fadeOut();
				}, 2000);
			},
			error: function(xhr,textStatus,e) {
				console.log(xhr);
			}
		}
		$.ajax(opts);
	});
});