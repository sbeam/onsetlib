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
    // plugins : "inlinepopups,advlink",

    plugins : "safari,spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,filemanager",
     
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    theme_advanced_resize_horizontal : false,
    theme_advanced_buttons1 : "formatselect,|,bold,italic,underline,|,justifyleft,justifycenter,justifyright,|,bullist,numlist,|,outdent,indent,blockquote,|,forecolor,backcolor",
    theme_advanced_buttons2 : "link,unlink,anchor,|,charmap,hr,|,visualaid,removeformat,cleanup,code,|,undo,redo,|,image,media",

    theme_advanced_buttons3 : "tablecontrols,|,fullscreen",

    convert_fonts_to_spans : true,
    relative_urls : false,
    remove_script_host : true,
    convert_urls : false
});


