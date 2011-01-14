//  formex_extras.js - JavaScript functions to create colorpicker and RTE
//  elements for formex-generated fields
//  Copyright (c) 2000-2004 SZ Beam, Onset Corps - sbeam@onsetcorps.net

// +----------------------------------------------------------------------+
//  This file is part of formex.
//
//  formex is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
// 
//  formex is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
// 
//  You should have received a copy of the GNU General Public License
//  along with formex; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
// +----------------------------------------------------------------------+

/**********************************
* function autoDisplayToggle (elem)
* toggles the display of any child DIVs of elem that have a set display and
* className = 'displayWrapper'
**********************************/
function autoDisplayToggle (elem) {
    if (typeof elem == 'object') {
        targElem = elem.currentTarget;
    }
    else {
        targElem = document.getElementById(elem);
    }
    if (! targElem) alert ('did not find any ' + elem) ;

    wrapper = targElem.parentNode;
    if (wrapper.className != 'displayWrapper') {
        wrapper = targElem.parentNode.parentNode;
    }
    sibs = wrapper.childNodes;

    for (var k = 0; k < sibs.length; k++) {
        if (sibs[k].tagName == 'DIV' && sibs[k].style.display) {
            sibs[k].style.display = (sibs[k].style.display == 'none')? 'block' : 'none';
        }
    }
}


/*
 * function check_init()
 * run onload to make sure we init any rte IFRAMES
 */
function check_init() {
    if (window.initRTE) initRTE();
}

/*
 * function for showing help text popup for CAPTCHA elements
 */
function toggleCaptchaHelp() {
    help = document.getElementById('captchaHelp');
    if (! help) { 
        alert ('did not find any element such as captchaHelp'); 
        return false;
    }
    help.style.display = (help.style.display == 'block')? 'none' : 'block';
}



/* 
 * below functions for driving the colorpicker applet
 */
function cp_highlightColor(color, field) {
    document.getElementById('cp_SelectedColor_' + field).style.background = color;
    document.getElementById('cp_SelectedColorValue_'+field).innerHTML = color;
}
function cp_selectColor(color, field) {
    eval('document.forms[0].'+field+'.value = color');
    document.getElementById('swatch_'+field).style.background = color;
    cp_displayToggle('palette_'+field);
}

// function to toggle display of div's by ID
function cp_displayToggle(elem_id) {
    if (!document.getElementById) return;
    elem = document.getElementById(elem_id);
    if (!elem) {
        alert ('not seeing ' + elem_id); 
        return;
    }
    shown = (elem.style.display && elem.style.display != "none");
    if (shown) {
        elem.style.display = "none";
    } else {
        elem.style.display = "block";
    }
}

var colors = new Array("#000000","#000033","#000066","#000099","#0000cc","#0000ff","#330000","#330033","#330066","#330099","#3300cc",
"#3300ff","#660000","#660033","#660066","#660099","#6600cc","#6600ff","#990000","#990033","#990066","#990099",
"#9900cc","#9900ff","#cc0000","#cc0033","#cc0066","#cc0099","#cc00cc","#cc00ff","#ff0000","#ff0033","#ff0066",
"#ff0099","#ff00cc","#ff00ff","#003300","#003333","#003366","#003399","#0033cc","#0033ff","#333300","#333333",
"#333366","#333399","#3333cc","#3333ff","#663300","#663333","#663366","#663399","#6633cc","#6633ff","#993300",
"#993333","#993366","#993399","#9933cc","#9933ff","#cc3300","#cc3333","#cc3366","#cc3399","#cc33cc","#cc33ff",
"#ff3300","#ff3333","#ff3366","#ff3399","#ff33cc","#ff33ff","#006600","#006633","#006666","#006699","#0066cc",
"#0066ff","#336600","#336633","#336666","#336699","#3366cc","#3366ff","#666600","#666633","#666666","#666699",
"#6666cc","#6666ff","#996600","#996633","#996666","#996699","#9966cc","#9966ff","#cc6600","#cc6633","#cc6666",
"#cc6699","#cc66cc","#cc66ff","#ff6600","#ff6633","#ff6666","#ff6699","#ff66cc","#ff66ff","#009900","#009933",
"#009966","#009999","#0099cc","#0099ff","#339900","#339933","#339966","#339999","#3399cc","#3399ff","#669900",
"#669933","#669966","#669999","#6699cc","#6699ff","#999900","#999933","#999966","#999999","#9999cc","#9999ff",
"#cc9900","#cc9933","#cc9966","#cc9999","#cc99cc","#cc99ff","#ff9900","#ff9933","#ff9966","#ff9999","#ff99cc",
"#ff99ff","#00cc00","#00cc33","#00cc66","#00cc99","#00cccc","#00ccff","#33cc00","#33cc33","#33cc66","#33cc99",
"#33cccc","#33ccff","#66cc00","#66cc33","#66cc66","#66cc99","#66cccc","#66ccff","#99cc00","#99cc33","#99cc66",
"#99cc99","#99cccc","#99ccff","#cccc00","#cccc33","#cccc66","#cccc99","#cccccc","#ccccff","#ffcc00","#ffcc33",
"#ffcc66","#ffcc99","#ffcccc","#ffccff","#00ff00","#00ff33","#00ff66","#00ff99","#00ffcc","#00ffff","#33ff00",
"#33ff33","#33ff66","#33ff99","#33ffcc","#33ffff","#66ff00","#66ff33","#66ff66","#66ff99","#66ffcc","#66ffff",
"#99ff00","#99ff33","#99ff66","#99ff99","#99ffcc","#99ffff","#ccff00","#ccff33","#ccff66","#ccff99","#ccffcc",
"#ccffff","#ffff00","#ffff33","#ffff66","#ffff99","#ffffcc","#ffffff");


/****************************************************
 below functions are for expandable fieldset types -
 ****************************************************/

var setCounts = new Array();
var expandableList = new Array();

// create more fields, cloning the block 'readroot'
function moreFields(setName)
{
    if (! document.getElementById('readroot_'+setName)) return;

    setCounts[setName]++;

    var newFields = document.getElementById('readroot_'+setName).cloneNode(true);
    newFields.id = newFields.id + '' + setCounts[setName];
    newFields.style.display = 'block';

    setNamesIncremented(newFields, setName);

    var insertHere = document.getElementById('writeroot_'+setName);
    insertHere.parentNode.insertBefore(newFields,insertHere);

    if (document.forms[0].elements['f_count_'+setName]) {
        document.forms[0].elements['f_count_'+setName].value = setCounts[setName];
    }

    setPrevElemValues(setName);

}

// remove some fields
function lessFields(setName)
{
    if (! document.getElementById('writeroot_'+setName)) return;

    setCounts[setName]--;

    var setMarker = document.getElementById('writeroot_'+setName);

    setMarker.parentNode.removeChild(setMarker.previousSibling);

    if (document.forms[0].elements['f_count_'+setName]) {
        document.forms[0].elements['f_count_'+setName].value = setCounts[setName];
    }
}


function setNamesIncremented(node, setName) {

    var children, i;

    if (node.name) { // its a named element, presume a form thing
        node.name = node.name + '_' + setCounts[setName]; // give it a unique ID
        node.id = node.name;
    }

    children = node.childNodes;

    for (i=0;i<children.length;i++)
    {
        setNamesIncremented(children.item(i), setName);
    }
}


function seekImgSetSrc(myNode, newVal) {

    if (!newVal) return;

    if (myNode.tagName == 'IMG' && myNode.className == 'sampleImgExpander') {
        myNode.src = '/' + newVal;
    }
    else {
        var sibs, s;

        sibs = myNode.childNodes;
        for (s=0; s<sibs.length; s++) {
            seekImgSetSrc(sibs.item(s), newVal);
        }
    }
}

function setPrevElemValues(setName) {
    valObj = eval('vals'+setName+'[setCounts[setName] - 1]');
    if (! valObj) return; 

    for (fieldName in valObj) {
        targElem = document.forms[0].elements[fieldName + '_' + setCounts[setName]];
        if (!targElem) {
            alert ('NOTICE: element ' + fieldName + '_' + setCounts[setName] +' is not defined');
        }
        else if (targElem.type == 'checkbox') 
            targElem.checked = (valObj[fieldName] != '')? true : false;
        else if (targElem.type != 'file') 
            targElem.value = valObj[fieldName];
        else {
            seekImgSetSrc(targElem.parentNode, valObj[fieldName]);
        }
    }
}

function initFields() {

    if (!expandableList) return;

    for (var i=0;i<expandableList.length;i++) {
        setCounts[ expandableList[i] ] = 0;

        numInitially = 0;

        valArrName = 'vals' + expandableList[i];
        if (eval(valArrName)) {
            //alert (eval(valArrName+'.length') + valArrName + ' found');
            if (eval(valArrName+'.length') > 0) {
                numInitially = eval(valArrName+'.length'); // (starts at index [1])
            }
        }
        else if (document.forms[0] && document.forms[0].elements['f_count_'+expandableList[i]]) {
            numInitially = document.forms[0].elements['f_count_'+expandableList[i]].value;
        }

        for (var j=0; j<numInitially; j++) {
            moreFields(expandableList[i]);
        }
    }
}




function formexFieldBicameralSelect(elemName, tog) {
    var picks = document.getElementById(elemName);
    var pool = document.getElementById(elemName+'_pool');
    var seen;

    if (tog) {
        for (i=0; i<pool.options.length; i++) {
            seen = false;

            if (pool.options[i].selected && !pool.options[i].disabled ) {
                for (j=0; j<picks.options.length; j++) {
                    if (picks.options[j].value == pool.options[i].value) { seen = true; }
                }

                if (!seen) {
                    picks.options[picks.options.length] = new Option( pool.options[i].text, pool.options[i].value, pool.options[i].defaultSelected, pool.options[i].selected );
                    pool.options[i].disabled = true;
                    pool.options[i].selected = true;
                }
            }
        }
        //picks.options.sort(compareSortText);
    }
    else {
        for (i=0; i<picks.options.length; i++) {
            if (picks.options[i].selected) {
                val = picks.options[i].value;
                picks.options[i] = null;
                for (j=0; j<pool.options.length; j++) {
                    if (pool.options[j].value == val) {
                        pool.options[j].disabled = false;
                        pool.options[j].selected = false;
                    }
                }
            }
        }
    }
}



function formexBicameralSelectAll() {
    spanColl = document.getElementsByTagName('SPAN');
    for (i=0; i<spanColl.length; i++) {
        if (spanColl[i].className == 'formexFieldSelect_bicameral') {
            selects = spanColl[i].getElementsByTagName('SELECT');
            for (j=0; j<selects[0].options.length; j++) {
                selects[0].options[j].selected = true;
            }
        }
    }
}


// Compare two options within a list by TEXT

function compareSortText(a, b) { 
    // Radix 10: for numeric values
    // Radix 36: for alphanumeric values
    var sA = parseInt( a.text, 36 );  
    var sB = parseInt( b.text, 36 );  
    return sA - sB;
}


