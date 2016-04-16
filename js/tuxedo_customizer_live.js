(function($){

	wp.customize('tux_css_editor_compiled', function(value){
		value.bind(function(newval){
			$('#tuxedo-css').html(newval);
		});
	});

})(jQuery);