(function($) {
  $("[name=postuac_is_public]").click(function() {
    if ($(this).val() == "yes") {
      $(".postuac.user.check").attr("disabled", "disabled").removeAttr("checked");
    } else {
      $(".postuac.user.check").removeAttr("disabled");
    }
  });
  if ($("[name=postuac_is_public]").attr("checked") == "checked") {
    $(".postuac.user.check").attr("disabled", "disabled");
  };

  $("#post").submit(function() {
    if ($("#postuac_is_public_no").attr("checked") == "checked" && $(".postuac.user.check:checked").length == 0) {
      alert("Debe seleccionar al menos un usuario para esta publicación.");
      $("#ajax-loading").hide();
      $("#publish").removeClass('button-primary-disabled');
      return false;
    };
    return true;
  });
})(jQuery);