jQuery(document).ready(function($){
	$(".share-count-services").select2({
		placeholder: "Select service(s)",
		allowClear: true
	});
	$(".share-count-services").on('change', function(){
		var data = $(this).select2('data');
		var array = [];
		$.each(data, function(index, val) {
			array[index]=val.id;
		});
		array.join(',');
		$(".share-count-services-raw").val(array);
	});
	$('.ea-share-count-services-check').change(function(event) {
		var $this = $(this),
			key   = $this.data('key');
		if ( $this.is(':checked') ) {
			$('#ea-service-note-'+key).show();
		} else {
			$('#ea-service-note-'+key).hide();
		}
	});
});