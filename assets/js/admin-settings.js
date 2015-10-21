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
});