tinyMCE.init({
    mode : "textareas",
    theme : "simple",
    editor_selector : "mceSimple"
});

tinyMCE.init({
    mode : "textareas",
    theme : "advanced",
    editor_selector : "mceAdvanced",
    apply_source_formatting : true,
    plugins : "inlinepopups,advlink",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    theme_advanced_resize_horizontal : false,
    theme_advanced_buttons1 : "formatselect,separator,bold,italic,underline,separator,justifyleft,justifycenter,justifyright,separator,bullist,numlist,separator,outdent,indent,separator,forecolor,backcolor",
    theme_advanced_buttons2 : "link,unlink,anchor,separator,image,charmap,hr,separator,visualaid,removeformat,cleanup,code,separator,undo,redo",
    theme_advanced_buttons3 : "",
    convert_fonts_to_spans : true,
    relative_urls : false,
    remove_script_host : true,
    convert_urls : false
});
