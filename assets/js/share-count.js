jQuery(document).ready(function($){
	$(document).on('click', '.ea-share-count-button[target="_blank"]', function(event) {
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
				window_size = "width=700,height=300";
				break;
			default:
				window_size = "width=585,height=515";
		}
		window.open(url, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,' + window_size);
		$(this).trigger("ea-share-click");
	});
});