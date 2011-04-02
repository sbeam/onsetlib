/*  
 *  formex/js/grouped_fieldset.js
 *  
 *  provides functionality for formex "grouped_fieldset" fields. If this script
 *  is included on the applicable pages, should attach to the necessary elements
 *  and allow you to add/remove fieldsets as part of the object.
 *
 *
 *
 *******************************************************************************
 * Copyright 2000-2005 SBeam, Onset Corps - sbeam@onsetcorps.net
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

var fex_grouped_fieldset = {
    templates : [],

    init : function(elem) {
		var setname = $(elem).attr('data-setname');

		var numsets = $(elem).attr('data-numsets');

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
    },

    showhide : function() {

        if ($(this).hasClass('enable_showhide')) {
            var container = $(this).parents('.formexFieldGrouped_fieldset');
            var fsets = $(container).find('fieldset.formex_grouped_fieldset');

            if (fsets.length && $(fsets[0]).css('display') != 'none') {
                $(fsets).slideUp(200, jQuery.proxy( function() { $(this).siblings('a').hide() }, this ));
                $(container).removeClass('open');
            }
            else {
                $(fsets).slideDown(200, jQuery.proxy( function() { $(this).siblings('a').show() }, this ));
                $(container).addClass('open');
            }
        }
    }
};


$( function() {
	
	$('.formex_group').each( function() {
        fex_grouped_fieldset.init(this);
	});

    $('span.formexFieldsetControllers a').bind('click', function() {
        var setname = $(this).parent().attr('data-setname');
        if ($(this).hasClass('formex_group_addfields')) {
            fex_grouped_fieldset.add(setname);
        } else {
            fex_grouped_fieldset.remove(setname);
        }
        $(this).blur();
        return false;
    });

    $('span.formexFieldsetControllers label').bind('click', fex_grouped_fieldset.showhide);

});
