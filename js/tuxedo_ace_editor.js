(function($, exports){

	$(document).ready(function(){

		var tux_css_compress = wp.customize.instance('tux_css_editor[compress]').get() == '1' ? true : false;
		var tux_css_compiler = wp.customize.instance('tux_css_editor_compiler').get();
		function tux_css_compile(css){
			if (tux_css_compiler == 'less'){
				less.render(css, {compress: tux_css_compress}, function(error, output){
					if(error == null){
						wp.customize.instance('tux_css_editor_compiled').set(output.css);
						$('#tux_css_download_button').attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(output.css));
						$('#customize-control-tux_ace_editor_control .customize-control-title').html('<span style="color:green;">Less Compile Successful</span>');
					} else {
						$('#customize-control-tux_ace_editor_control .customize-control-title').html('<span style="color:red;">Less Error Line: ' + error.line + '</span>');
					}
				});
			} else {
				if (tux_css_compress) {
					Sass.options({style: Sass.style.compressed});
				} else {
					Sass.options({style: Sass.style.expanded});
				}
				Sass.compile(css, function(result){
					if(result.status == 0){
						wp.customize.instance('tux_css_editor_compiled').set(result.text);
						$('#tux_css_download_button').attr('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(result.text));
						$('#customize-control-tux_ace_editor_control .customize-control-title').html('<span style="color:green;">Sass Compile Successful</span>');
					} else {
						$('#customize-control-tux_ace_editor_control .customize-control-title').html('<span style="color:red;">Sass Error Line: ' + result.line + '</span>');
					}
				});
			}
		}

		$('#customize-control-tux_css_editor_compiled_control .customize-control-title').after('<a href="javascript:void(0);" id="tux_css_download_button" download="tuxedo.css" class="button" style="float:right;margin:-29px 0 6px 0;">Download</a>');
		$('#customize-control-tux_css_editor_compiled_control textarea').attr('readonly', 'readonly');
		$('#customize-control-tux_ace_editor_control .customize-control-title').after('<div id="tux_css_resize" style="display:inline-block;position:fixed;left:250px;top:126px;padding:10px 20px 15px 10px;font-size:28px;cursor:col-resize;">&#8596;</div>');
		var tux_css_timeout = null;
		var tux_css_editor = ace.edit('tux_ace_editor_control');
		tux_css_editor.$blockScrolling = Infinity;
		tux_css_editor.getSession().setTabSize(2);

		var tux_css_textarea = $('#tux_ace_editor_control_textarea').hide();
		tux_css_editor.getSession().setValue(tux_css_textarea.val());

		tux_css_editor.getSession().on('change', function(e){
			if (tux_css_timeout !== null){
				clearTimeout(tux_css_timeout);
			}
			tux_css_timeout = setTimeout(function(){
				tux_css_textarea.val(tux_css_editor.getSession().getValue());
				tux_css_textarea.trigger('change');
			},1000);
		});

		tux_css_editor.setTheme('ace/theme/' + wp.customize.instance('tux_css_editor[theme]').get());
		wp.customize('tux_css_editor[theme]', function(value){
			value.bind(function(newval){
				tux_css_editor.setTheme('ace/theme/' + newval);
			});
		});

		$('#tux_ace_editor_control').css('font-size', wp.customize.instance('tux_css_editor[font_size]').get());
		wp.customize('tux_css_editor[font_size]', function(value){
			value.bind(function(newval){
				$('#tux_ace_editor_control').css('font-size', newval);
			});
		});

		wp.customize('tux_css_editor[compress]', function(value){
			value.bind(function(newval){
				tux_css_compress = newval == '1' ? true : false;
				tux_css_compile(wp.customize.instance('tux_css_editor_code').get());
			});
		});

		tux_css_editor.getSession().setMode('ace/mode/' + wp.customize.instance('tux_css_editor_compiler').get());
		wp.customize('tux_css_editor_compiler', function(value){
			value.bind(function(newval){
				tux_css_compiler = newval;
				tux_css_compile(wp.customize.instance('tux_css_editor_code').get())
				tux_css_editor.getSession().setMode('ace/mode/' + newval);
			});
		});

		wp.customize('tux_css_editor_code', function(value){
			value.bind(function(newval){
				tux_css_compile(newval);
			});
		});

		tux_css_compile(wp.customize.instance('tux_css_editor_code').get());

		var tuxcustomizerwidth = 300;

		$('#accordion-section-tux_css_editor_section .accordion-section-title').click(function(e){
			$('#customize-controls').css('transition-duration', '0s');
			$('#customize-controls').css('-webkit-transition-duration', '0s');
			$('.wp-full-overlay').css('transition-duration', '0s');
			$('.wp-full-overlay').css('-webkit-transition-duration', '0s');
			$('#customize-controls,#customize-footer-actions').css('width', tuxcustomizerwidth + 'px');
			$('.wp-full-overlay.expanded').css('margin-left', tuxcustomizerwidth + 'px');
			$('#tux_css_resize').css('left', $('#tux_css_resize').position().left + (tuxcustomizerwidth - 300) + 'px');
		});

		$('#tux_css_resize').draggable({
			axis: 'x',
			containment: [250, 0, $(window).width()*10, $(window).height()*10],
			cursor: 'col-resize',
			drag: function() {
				var tuxcssresizeleft = $('#tux_css_resize').position().left;
				if(tuxcssresizeleft < 250){ tuxcssresizeleft = 250; }
				$('#tux_css_resize').css('left', tuxcssresizeleft + 'px');
				tuxcustomizerwidth = 300 + (tuxcssresizeleft - 250);
				if(tuxcustomizerwidth < 300){ tuxcustomizerwidth = 300; }
				$('#customize-controls,#customize-footer-actions').css('width', tuxcustomizerwidth + 'px');
				$('.wp-full-overlay.expanded').css('margin-left', tuxcustomizerwidth + 'px');
				tux_css_editor.resize();
			}
		});

		$('#accordion-section-tux_css_editor_section .customize-section-back').click(function(e){
			$('#customize-controls').css('transition-duration', '');
			$('#customize-controls').css('-webkit-transition-duration', '');
			$('.wp-full-overlay').css('transition-duration', '');
			$('.wp-full-overlay').css('-webkit-transition-duration', '');
			$('#customize-controls,#customize-footer-actions').css('width', '');
			$('.wp-full-overlay.expanded').css('margin-left', '');
			$('#tux_css_resize').css('left', $('#tux_css_resize').position().left - (tuxcustomizerwidth - 300) + 'px');
			if($('#tux_css_resize').position().left < 250){ $('#tux_css_resize').css('left', '250px'); }
		});

		$('.collapse-sidebar').click(function(e){
			if($('#accordion-section-tux_css_editor_section').hasClass('open')){
				if($('.collapse-sidebar').attr('aria-expanded') == 'false') {
					$('#customize-controls').css('transition-duration', '');
					$('#customize-controls').css('-webkit-transition-duration', '');
					$('.wp-full-overlay').css('transition-duration', '');
					$('.wp-full-overlay').css('-webkit-transition-duration', '');
					$('#customize-controls,#customize-footer-actions').css('width', '');
					$('.wp-full-overlay.expanded').css('margin-left', '');
					$('#tux_css_resize').css('left', $('#tux_css_resize').position().left - (tuxcustomizerwidth - 300) + 'px');
					if($('#tux_css_resize').position().left < 250){ $('#tux_css_resize').css('left', '250px'); }
					$('#tux_css_resize').hide();
				} else {
					$('#customize-controls').css('transition-duration', '0s');
					$('#customize-controls').css('-webkit-transition-duration', '0s');
					$('.wp-full-overlay').css('transition-duration', '0s');
					$('.wp-full-overlay').css('-webkit-transition-duration', '0s');
					$('#customize-controls,#customize-footer-actions').css('width', tuxcustomizerwidth + 'px');
					$('.wp-full-overlay.expanded').css('margin-left', tuxcustomizerwidth + 'px');
					$('#tux_css_resize').show();
					$('#tux_css_resize').css('left', $('#tux_css_resize').position().left + (tuxcustomizerwidth - 300) + 'px');
				}
			}
		});

	/*	var tuxmousex = null;
		var tuxcustomizerwidth = 300;

		$('#accordion-section-tux_css_editor_section .accordion-section-title').click(function(e){
			tuxmousex = null;
			$('#customize-controls').css('transition-duration', '0s');
			$('#customize-controls').css('-webkit-transition-duration', '0s');
			$('.wp-full-overlay').css('transition-duration', '0s');
			$('.wp-full-overlay').css('-webkit-transition-duration', '0s');
			$('#customize-controls').css('width', tuxcustomizerwidth + 'px');
			$('.wp-full-overlay.expanded').css('margin-left', tuxcustomizerwidth + 'px');
			$('#tux_css_resize').css('left', $('#tux_css_resize').position().left + (tuxcustomizerwidth - 300) + 'px');
			$('#tux_css_resize').mousedown(function(e){
				tuxmousex = e.pageX;
			});
			$('#tux_css_resize').mouseup(function(e){
				tuxmousex = null;
			});
			$('#tux_css_resize').mouseout(function(e){
				tuxmousex = null;
			});
			$('#tux_css_resize').mousemove(function(e){
				if(tuxmousex == null){ return; }
				var moveamountx = e.pageX - tuxmousex;
				var tuxcssresizeleft = $('#tux_css_resize').position().left + moveamountx;
				if(tuxcssresizeleft < 180){ tuxcssresizeleft = 180; }
				$('#tux_css_resize').css('left', tuxcssresizeleft + 'px');
				tuxcustomizerwidth = 300 + (tuxcssresizeleft - 180);
				if(tuxcustomizerwidth < 300){ tuxcustomizerwidth = 300; }
				$('#customize-controls').css('width', tuxcustomizerwidth + 'px');
				$('.wp-full-overlay.expanded').css('margin-left', tuxcustomizerwidth + 'px');
				tuxmousex = e.pageX;
				tux_css_editor.resize();
				e.preventDefault();
			});
		});

		$('#accordion-section-tux_css_editor_section .customize-section-back').click(function(e){
			$('#customize-controls').css('transition-duration', '');
			$('#customize-controls').css('-webkit-transition-duration', '');
			$('.wp-full-overlay').css('transition-duration', '');
			$('.wp-full-overlay').css('-webkit-transition-duration', '');
			$('#customize-controls').css('width', '');
			$('.wp-full-overlay.expanded').css('margin-left', '');
			$('#tux_css_resize').css('left', $('#tux_css_resize').position().left - (tuxcustomizerwidth - 300) + 'px');
			if($('#tux_css_resize').position().left < 180){ $('#tux_css_resize').css('left', '180px'); }
		});*/

	});
})(jQuery, window);