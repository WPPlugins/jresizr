/**
 * script for JPanel
 * by Jegtheme
 * since 1.0.0
 */

(function($) {
	
	var jadmin = {
			init : function () {
				console.log("hit here");
				if($('.jad').length){
					jadmin.savednotif();
					jadmin.childHeading();
					jadmin.setSwitchToogle();
					jadmin.colorSetup();
				}
			},
			savednotif : function () {
				if($('.savedinfo').length) {			
					setTimeout(function(){
						$('.savedinfo').slideUp();
					}, 2000);
				}
			}, 
			childHeading : function () {
				$(".jad-child-heading").click(function() {
					var parent 	= $(this).parent();
					var idx 	= $(parent).children('div').index(this);
					for(var i = idx + 1; i < $(parent).children('div').length ; i++) {
						var now = $(parent).children('div').get(i);
						if($(now).hasClass('jad-child-heading')) {
							break;						
						} else {						
							if($(now).is(':visible')) {
								$(now).hide();
							} else {
								$(now).show();
							}
						}					
					}
				});
			},
			setSwitchToogle : function() {
				if($(".switchtoogle").length) {
					$(".switchtoogle").iButton();
				}
			},
			colorSetup : function() {
				if($('.setting-colorpicker').length) {
					$('.setting-colorpicker').each(function(idx, val){
						var $this = $(this).find('.pickcolor');
						var $text = $(this).find('.pickcolor-text');
						var $thiscolor = $text.val();
						$this.ColorPicker({
							color: '#' + $thiscolor ,
							onShow: function (colpkr) {						 	
								$(colpkr).fadeIn(500);
								return false;
							},
							onHide: function (colpkr) {
								$(colpkr).fadeOut(500);
								return false;
							},
							onChange: function (hsb, hex, rgb) {							
								$this.find('div').css('backgroundColor', '#' + hex);
								$text.val(hex);
							}
						});
					});
				}
			}
	};
	
	var ads = {
		init : function(){
			console.log("hit here!");
			$(".jad-ads-close").click(function(){
				console.log("come and hit me");
				$(".jad-ads").hide();
				$.cookie('jadclose', 1, { expires: 30, path: '/' });
			});		
		}
	};
	
	$(document).ready(function(){
		jadmin.init();
		ads.init();
	});
	
})(jQuery);