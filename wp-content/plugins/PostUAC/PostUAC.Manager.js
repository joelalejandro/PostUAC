(function($) {
  $(".post.not.allowed").each(function() { $("#post-" + $(this).data("post-id")).remove(); });
  $(".post.not.allowed").remove();
})(jQuery);