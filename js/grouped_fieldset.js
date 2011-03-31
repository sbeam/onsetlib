var fex_fieldset_templates = [];

$( function() {
	
	$('.formex_group').each( function() {
		var setname = $(this).attr('data-setname');

		var numsets = $('#count_'+setname).val();

		fex_fieldset_templates[setname] = $('fieldset', this).remove();

		for (var i=0; i<numsets; i++) {
			var set = $(fex_fieldset_templates[setname]).clone();

			$(set).attr('id', 'set_'+setname+'_'+i) // mosh ids and names to create an accessible arry
				  .find('input, select, textarea').each( function () { 
																var new_id = $(this).attr('id') + '_' + i; 
																var new_name = setname + '['+i+'][' + $(this).attr('name') + ']';
																$(this).attr('id', new_id)
																       .attr('name', new_name); 
														});

			if (formex_groupvalues[setname] && formex_groupvalues[setname][i] ) {
				for (var key in formex_groupvalues[setname][i]) {
					$('#'+key+'_'+i, set).val(formex_groupvalues[setname][i][key]); 
				}
			}

			$(this).parent().append(set);
		}

	});

});
