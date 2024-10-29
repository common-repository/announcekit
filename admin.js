jQuery(document).on("ready", function() {
  var js_code_input = jQuery("textarea#js_code");
  var warning = jQuery("<span style='display:none;color:red;'>Not valid Announcekit Javascript Code</span>");
  var menu_field = jQuery("table.form-table tr:eq(3)");
  var selector_menu_field = jQuery("table.form-table tr:eq(4)");
  var snippet = js_code_input.val();


  console.log("testing")


  var menu_selector = jQuery("select#selector");

  if (menu_selector.val() != "0") {
    selector_menu_field.hide();
  } else {
    selector_menu_field.show();
  }

  console.log('ok');

  menu_selector.on("change", function(item) {
    var val = jQuery(this).val();

    if (val == "0") {
      selector_menu_field.show();
    } else {
      selector_menu_field.hide();
      selector_menu_field.find("input").val("");
    }
  });

  if (snippet != "") {
    var have_selector = /\"selector\"/.test(js_code_input.val());
    if (!have_selector) {
      menu_field.hide();
    } else {
      menu_field.show();
    }
  }

  warning.insertAfter(js_code_input);

  js_code_input.on("blur", function() {
    var snippet = jQuery(this).val();
    var is_valid_snippet = /\/widgets?(\/v2)?\//.test(snippet);

    if (!is_valid_snippet) {
      warning.show();
    } else {
      warning.hide();
    }

    var have_selector = /\"selector\"/.test(snippet);
    if (!have_selector) {
      menu_field.hide();
    } else {
      menu_field.show();
    }
  });
});
