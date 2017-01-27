jQuery(document).ready(function($){
	$(document).on('click', '#ea-share-count-refresh', function(event){
		event.preventDefault();
		var $this   = $(this),
		    post_id = $this.data('postid'),
		    nonce   = $this.data('nonce');

		$this.prop('disabled',true);
		$this.text('Loading share counts...');

		var opts = {
			url: ajaxurl,
			type: 'post',
			async: true,
			dataType: 'json',
			data: {
				post_id: post_id,
				action: 'ea_share_refresh',
				nonce: nonce
			},
			success: function(res){
				console.log(res);
				if ( res.success == true ) {
					$('#ea-share-count-msg, #ea-share-count-list, #ea-share-count-date, #ea-share-count-empty').remove();
					$('#ea-share-count-metabox .inside').prepend(res.data.date).prepend(res.data.list).prepend('<p id="ea-share-count-msg" class="'+res.data.class+'">'+res.data.msg+'</p>');
				} else {
					$('#ea-share-count-msg').remove();
					$('#ea-share-count-metabox .inside').prepend('<p id="ea-share-count-msg" class="'+res.data.class+'">'+res.data.msg+'</p>');
				}
				$this.text('Refresh Share Counts');
				$this.prop('disabled',false);
			},
			error: function(xhr, textStatus ,e) {
				console.log(xhr.responseText);
			}
		}
		$.ajax(opts);
	});
});