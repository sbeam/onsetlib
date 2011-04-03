
$( function() {

  $.getScript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js', function() {

        $('span.formexFieldAutocomplete input').each( function() {

            var opts = formex_autocomplete_opts[this.id] || [];

            if (!$(this).hasClass('multiple')) {
                $(this).autocomplete({ source: opts, minlength: $(this).attr('data-minlength') });
            }
            else {
                $(this).autocomplete({ minlength: $(this).attr('data-minlength'),
                                       source: function(request,response) { response( $.ui.autocomplete.filter( opts, request.term.split( /,\s*/ ).pop() )); },
                                       focus: function() { return false; },
                                       select: function(event,ui) { 
                                           var terms=this.value.split( /,\s*/ ); 
                                           terms.pop(); 
                                           terms.push(ui.item.value); 
                                           terms.push(''); 
                                           this.value = terms.join(', '); 
                                           return false; 
                                       }, 
                });
                $(this).parents('form').bind('submit', jQuery.proxy( function() {
                    var terms = this.value.split( /,\s*/ );
                    for (var i in terms) {
                        var hid = $('<input type="hidden" name="'+this.id+'[]" />');
                        $(hid).val( terms[i] );
                        $(this).parent().append(hid);
                    }
                }, this));
            }
        });
   });
});
