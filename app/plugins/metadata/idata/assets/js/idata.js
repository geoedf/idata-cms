// Fit dialog within viewport
function setHeight() {
    var height = $(window).height();
    var new_height = (85 * height) / 100;
    new_height = parseInt(new_height) + 'px';
    $("#scroll-area").css('height',new_height);
}
$(document).ready(function() {
    setHeight();
    $(window).bind('resize', setHeight);
});

// Expand or collapse metadata section
function openClose(section,show,hide) {
	$('.'+section+'-area').slideToggle();
   debugger;
	if ($('.'+section+'-sign').html() == show) {
		$('.'+section+'-sign').html(hide);
	}
	else {
		$('.'+section+'-sign').html(show);
	}
}


$("#hubForm-ajax").submit(function(e) {

   e.preventDefault();

   var pre;
   var key;
   var post;
   var value;
   var len = $("input[name^=key").length;
   
   for (var i = 0; i < len; i++) {
      pre = $('input[name=\"pre[' + i + ']\"]');
      key = $('input[name=\"key[' + i + ']\"]');
      post = $('input[name=\"post[' + i + ']\"]');
      value = $('input[name=\"value[' + i + ']\"');
      if (pre.val() != "") {
         key.val(pre.val() + '_' + key.val());
      }
      if (post.val() != "") {
         key.val(key.val() + '_' + post.val());
      }
   }
      
   this.submit(); // Submit bypassing the jQuery bound event
});
