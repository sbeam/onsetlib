
$( function() {

  $.getScript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/jquery-ui.min.js', function() {

        $('span.formexFieldAutocomplete input').each( function() {
            var opts = formex_autocomplete_opts[this.id] || [];
            if (!$(this).hasClass('multiple')) {
                $(this).autocomplete({ source: opts, minlength: $(this).attr('data-minlength') });
            }
            else {
                $(this).autocomplete({ source: opts,
                                       minlength: $(this).attr('data-minlength'),
                                       source: function(request,response) { response( $.ui.autocomplete.filter( opts, request.term.split( /,\s*/ ).pop() )); },
                                       focus: function() { return false; },
                                       select: function(event,ui) { var terms=this.value.split( /,\s*/ ); terms.pop(); terms.push(ui.item.value); terms.push(''); this.value = terms.join(', '); return false; }, 
                });
            }
        });
   });
});
