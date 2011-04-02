var fex_grouped_fieldset = {
    templates : [],

    init : function(elem) {
		var setname = $(elem).attr('data-setname');

		var numsets = $('#count_'+setname).val();

		this.templates[setname] = $('fieldset', elem).remove();

        for (var i=0; i<numsets; i++) {
            this.add(setname);
        }
    },


    add : function(setname) {
        var set = $(this.templates[setname]).clone();
        var container = $('#proto_'+setname).parent();
        var index = $(container).children('fieldset').length;

        $(set).attr('id', 'set_'+setname+'_'+index) // mosh ids and names to create an accessible arry
              .find('input, select, textarea').each( function () { 
                                                            var new_id = $(this).attr('id') + '_' + index; 
                                                            var new_name = setname + '['+index+'][' + $(this).attr('name') + ']';
                                                            $(this).attr('id', new_id)
                                                                   .attr('name', new_name); 
                                                    });

        if (formex_groupvalues[setname] && formex_groupvalues[setname][index] ) {
            for (var key in formex_groupvalues[setname][index]) {
                $('#'+key+'_'+index, set).val(formex_groupvalues[setname][index][key]); 
            }
        }

        $(container).append(set);
        return container;
    },

    remove : function(setname) {
        var set = $(this.templates[setname]).clone();
        var container = $('#proto_'+setname).parent();
        $(container).children(':last').remove();
    }
};


$( function() {
	
	$('.formex_group').each( function() {
        fex_grouped_fieldset.init(this);
	});

    $('.formexFieldsetControllers a').bind('click', function() {
        var setname = $(this).parent().attr('data-setname');
        if ($(this).hasClass('formex_group_addfields')) {
            fex_grouped_fieldset.add(setname);
        } else {
            fex_grouped_fieldset.remove(setname);
        }
        $(this).blur();
        return false;
    });

});
