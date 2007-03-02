// Cross-Browser Rich Text Editor
// http://www.kevinroth.com/rte/demo.htm
// Written by Kevin Roth (kevin@NOSPAMkevinroth.com - remove NOSPAM)
// Visit the support forums at http://www.kevinroth.com/forums/index.php?c=2
// This code is public domain. Redistribution and use of this code, with or
// without modification, is permitted.

// modified and hacked to work with Sicoma CMS and have some old functionality
// by S Beam sbeam@onsetcorps.net
// $Id: richTextEditor.js,v 1.1.1.1 2006/06/02 20:08:01 sbeam Exp $

//init variables
var isRichText = false;
var rng;
var currentRTE;
var allRTEs = "";

var isIE;
var isGecko;
var isSafari;
var isKonqueror;

var imagesPath = rteRootUrl + '/images/';
var includesPath = rteRootUrl;
var cssFile;
var generateXHTML = true;

var lang = "en";
var encoding = "iso-8859-1";

if (!colors) { // for the colorpicker

    var colors = new Array("#000000","#000033","#000066","#000099","#0000cc","#0000ff","#330000","#330033","#330066","#330099","#3300cc", "#3300ff","#660000","#660033","#660066","#660099","#6600cc","#6600ff","#990000","#990033","#990066","#990099", "#9900cc","#9900ff","#cc0000","#cc0033","#cc0066","#cc0099","#cc00cc","#cc00ff","#ff0000","#ff0033","#ff0066", "#ff0099","#ff00cc","#ff00ff","#003300","#003333","#003366","#003399","#0033cc","#0033ff","#333300","#333333", "#333366","#333399","#3333cc","#3333ff","#663300","#663333","#663366","#663399","#6633cc","#6633ff","#993300", "#993333","#993366","#993399","#9933cc","#9933ff","#cc3300","#cc3333","#cc3366","#cc3399","#cc33cc","#cc33ff", "#ff3300","#ff3333","#ff3366","#ff3399","#ff33cc","#ff33ff","#006600","#006633","#006666","#006699","#0066cc", "#0066ff","#336600","#336633","#336666","#336699","#3366cc","#3366ff","#666600","#666633","#666666","#666699", "#6666cc","#6666ff","#996600","#996633","#996666","#996699","#9966cc","#9966ff","#cc6600","#cc6633","#cc6666", "#cc6699","#cc66cc","#cc66ff","#ff6600","#ff6633","#ff6666","#ff6699","#ff66cc","#ff66ff","#009900","#009933", "#009966","#009999","#0099cc","#0099ff","#339900","#339933","#339966","#339999","#3399cc","#3399ff","#669900", "#669933","#669966","#669999","#6699cc","#6699ff","#999900","#999933","#999966","#999999","#9999cc","#9999ff", "#cc9900","#cc9933","#cc9966","#cc9999","#cc99cc","#cc99ff","#ff9900","#ff9933","#ff9966","#ff9999","#ff99cc", "#ff99ff","#00cc00","#00cc33","#00cc66","#00cc99","#00cccc","#00ccff","#33cc00","#33cc33","#33cc66","#33cc99", "#33cccc","#33ccff","#66cc00","#66cc33","#66cc66","#66cc99","#66cccc","#66ccff","#99cc00","#99cc33","#99cc66", "#99cc99","#99cccc","#99ccff","#cccc00","#cccc33","#cccc66","#cccc99","#cccccc","#ccccff","#ffcc00","#ffcc33", "#ffcc66","#ffcc99","#ffcccc","#ffccff","#00ff00","#00ff33","#00ff66","#00ff99","#00ffcc","#00ffff","#33ff00", "#33ff33","#33ff66","#33ff99","#33ffcc","#33ffff","#66ff00","#66ff33","#66ff66","#66ff99","#66ffcc","#66ffff", "#99ff00","#99ff33","#99ff66","#99ff99","#99ffcc","#99ffff","#ccff00","#ccff33","#ccff66","#ccff99","#ccffcc", "#ccffff","#ffff00","#ffff33","#ffff66","#ffff99","#ffffcc","#ffffff");  

}


function initRTE(imgPath, incPath, css, genXHTML) {
	//set browser vars
	var ua = navigator.userAgent.toLowerCase();
	isIE = ((ua.indexOf("msie") != -1) && (ua.indexOf("opera") == -1) && (ua.indexOf("webtv") == -1)); 
	isGecko = (ua.indexOf("gecko") != -1);
	isSafari = (ua.indexOf("safari") != -1);
	isKonqueror = (ua.indexOf("konqueror") != -1);
	
	//generateXHTML = genXHTML;
	
	//check to see if designMode mode is available
	//Safari/Konqueror think they are designMode capable even though they are not
	if (document.getElementById && document.designMode && !isSafari && !isKonqueror) {
		isRichText = true;
	}
	
	if (isIE) {
		document.onmouseover = raiseButton;
		document.onmouseout  = normalButton;
		document.onmousedown = lowerButton;
		document.onmouseup   = raiseButton;
	}
	
	//set paths vars
	//imagesPath = imgPath;
	//includesPath = incPath;
	cssFile = css;
	
	if (isRichText) document.writeln('<style type="text/css">@import "' + includesPath + 'richTextEditor.css";</style>');
	
	//for testing standard textarea, uncomment the following line
	//isRichText = false;
}

function writeRichText(rte, html, width, height, buttons, readOnly, localCSS) {
	if (isRichText) {
		if (allRTEs.length > 0) allRTEs += ";";
		allRTEs += rte;

        if (localCSS) {
            cssFile = localCSS;
        }
		
		if (readOnly) buttons = false;
		
		//adjust minimum table widths
		if (isIE) {
			//if (buttons && (width < 540)) width = 540;
			var tablewidth = width;
		} else {
			//if (buttons && (width < 540)) width = 540;
			var tablewidth = width + 4;
		}
		
        document.writeln('<div style="position: relative">');
        document.writeln('<div class="rteWrapper" id="'+rte+'Wrapper">');

		if (buttons == true) {
			document.writeln('<table class="rteBack" id="Buttons1_' + rte + '">');
			document.writeln('	<tr>');
            document.writeln('		<td><img class="rteButtonImg" src="'+imagesPath+'post_button_reset.gif" width="17" height="17" alt="Clear Text" title="Clear Content" onClick="ResetForm(\''+rte+'\')"></td>');
			document.writeln('		<td>');
			document.writeln('			<select class="rteForm" id="formatblock_' + rte + '" onchange="selectFont(\'' + rte + '\', this.id);">');
			document.writeln('				<option value="">[Style]</option>');
			document.writeln('				<option value="<p>">Paragraph</option>');
			document.writeln('				<option value="<h1>">Heading 1</option>');
			document.writeln('				<option value="<h2>">Heading 2</option>');
			document.writeln('				<option value="<h3>">Heading 3</option>');
			document.writeln('				<option value="<h4>">Heading 4</option>');
			document.writeln('				<option value="<h5>">Heading 5</option>');
			document.writeln('				<option value="<h6>">Heading 6</option>');
			document.writeln('				<option value="<address>">Address</option>');
			document.writeln('				<option value="<pre>">Formatted</option>');
			document.writeln('				<option value="div">Div</option>');
			document.writeln('			</select>');
			document.writeln('		</td>');
			document.writeln('		<td>');
			document.writeln('			<select class="rteForm" id="fontname_' + rte + '" onchange="selectFont(\'' + rte + '\', this.id)">');
			document.writeln('				<option value="Font" selected>[Font]</option>');
			document.writeln('				<option value="Arial, Helvetica, sans-serif">Arial</option>');
			document.writeln('				<option value="Courier New, Courier, mono">Courier New</option>');
			document.writeln('				<option value="Times New Roman, Times, serif">Times New Roman</option>');
			document.writeln('				<option value="Verdana, Arial, Helvetica, sans-serif">Verdana</option>');
			document.writeln('			</select>');
			document.writeln('		</td>');
			document.writeln('		<td>');
			document.writeln('			<select class="rteForm" unselectable="on" id="fontsize_' + rte + '" onchange="selectFont(\'' + rte + '\', this.id);">');
			document.writeln('				<option value="Size">[Size]</option>');
			document.writeln('				<option value="1">1</option>');
			document.writeln('				<option value="2">2</option>');
			document.writeln('				<option value="3">3</option>');
			document.writeln('				<option value="4">4</option>');
			document.writeln('				<option value="5">5</option>');
			document.writeln('				<option value="6">6</option>');
			document.writeln('				<option value="7">7</option>');
			document.writeln('			</select>');
			document.writeln('		</td>');
			document.writeln('		<td width="100%">');
			document.writeln('		</td>');
			document.writeln('	</tr>');
			document.writeln('</table>');

			document.writeln('<table class="rteBack" cellpadding="1" cellspacing="0" id="Buttons2_' + rte + '" width="' + tablewidth + '">');
			document.writeln('	<tr>');
			document.writeln('		<td><img id="bold" class="rteButtonImg" src="' + imagesPath + 'post_button_bold.gif" width="14" height="13" alt="Bold" title="Bold" onClick="rteCommand(\'' + rte + '\', \'bold\', \'\')"></td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_italic.gif" width="12" height="13" alt="Italic" title="Italic" onClick="rteCommand(\'' + rte + '\', \'italic\', \'\')"></td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_underline.gif" width="14" height="13" alt="Underline" title="Underline" onClick="rteCommand(\'' + rte + '\', \'underline\', \'\')"></td>');
			document.writeln('		<td>&nbsp;</td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_left_just.gif" width="17" height="13" alt="Align Left" title="Align Left" onClick="rteCommand(\'' + rte + '\', \'justifyleft\', \'\')"></td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_centre.gif" width="17" height="13" alt="Center" title="Center" onClick="rteCommand(\'' + rte + '\', \'justifycenter\', \'\')"></td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_right_just.gif" width="17" height="13" alt="Align Right" title="Align Right" onClick="rteCommand(\'' + rte + '\', \'justifyright\', \'\')"></td>');
			//document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_justifyfull.gif" width="17" height="13" alt="Justify Full" title="Justify Full" onclick="rteCommand(\'' + rte + '\', \'justifyfull\', \'\')"></td>');
			//document.writeln('		<td>&nbsp;</td>');
			//document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_hr.gif" width="17" height="13" alt="Horizontal Rule" title="Horizontal Rule" onClick="rteCommand(\'' + rte + '\', \'inserthorizontalrule\', \'\')"></td>');
			document.writeln('		<td>&nbsp;</td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_numbered_list.gif" width="13" height="13" alt="Ordered List" title="Ordered List" onClick="rteCommand(\'' + rte + '\', \'insertorderedlist\', \'\')"></td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_list.gif" width="13" height="13" alt="Unordered List" title="Unordered List" onClick="rteCommand(\'' + rte + '\', \'insertunorderedlist\', \'\')"></td>');
			document.writeln('		<td>&nbsp;</td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_outdent.gif" width="17" height="13" alt="Outdent" title="Outdent" onClick="rteCommand(\'' + rte + '\', \'outdent\', \'\')"></td>');

			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_indent.gif" width="17" height="13" alt="Indent" title="Indent" onClick="rteCommand(\'' + rte + '\', \'indent\', \'\')"></td>');
			document.writeln('		<td>&nbsp;</td>');
			document.writeln('		<td><div id="forecolor_' + rte + '"><img class="rteButtonImg" src="' + imagesPath + 'post_button_textcolor.gif" width="17" height="13" alt="Text Color" title="Text Color" onClick="sicomaColorPalette(\'' + rte + '\', \'forecolor\', \'\')"></div></td>');

            document.writeln('              <td><div id="hilitecolor_'+rte+'"><img class="rteButtonImg" src="'+rteRootUrl+'/images/post_button_bgcolor.gif" width="17" height="13" alt="Background Color" title="Background Color" onClick="sicomaColorPalette(\''+rte+'\',\'hilitecolor\', \'\')"></div></td>');
            document.writeln('              <td><img class="rteButtonImg" src="'+rteRootUrl+'/images/post_button_unformat.gif" width="17" height="13" alt="Remove Formatting" title="Remove Formatting from selected text" onClick="rteCommand(\''+rte+'\',\'RemoveFormat\')"></td>');

			document.writeln('		<td>&nbsp;</td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_hyperlink.gif" width="17" height="13" alt="Insert Link" title="Insert Link" onClick="dlgInsertLink(\'' + rte + '\', \'link\')"></td>');
			document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_image.gif" width="17" height="13" alt="Add Image" title="Add Image" onClick="addImage(\'' + rte + '\')"></td>');
			//document.writeln('		<td><div id="table_' + rte + '"><img class="rteButtonImg" src="' + imagesPath + 'post_button_insert_table.gif" width="17" height="13" alt="Insert Table" title="Insert Table" onClick="dlgInsertTable(\'' + rte + '\', \'table\', \'\')"></div></td>');
			if (isIE) {
				//document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_spellcheck.gif" width="17" height="13" alt="Spell Check" title="Spell Check" onClick="checkspell()"></td>');
			}
	//		document.writeln('		<td>&nbsp;</td>');
	//		document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_cut.gif" width="17" height="13" alt="Cut" title="Cut" onClick="rteCommand(\'' + rte + '\', \'cut\')"></td>');
	//		document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_copy.gif" width="17" height="13" alt="Copy" title="Copy" onClick="rteCommand(\'' + rte + '\', \'copy\')"></td>');
	//		document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_paste.gif" width="17" height="13" alt="Paste" title="Paste" onClick="rteCommand(\'' + rte + '\', \'paste\')"></td>');
	//		document.writeln('		<td>&nbsp;</td>');
	//		document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_undo.gif" width="17" height="13" alt="Undo" title="Undo" onClick="rteCommand(\'' + rte + '\', \'undo\')"></td>');
	//		document.writeln('		<td><img class="rteButtonImg" src="' + imagesPath + 'post_button_redo.gif" width="17" height="13" alt="Redo" title="Redo" onClick="rteCommand(\'' + rte + '\', \'redo\')"></td>');
			document.writeln('		<td width="100%"></td>');
			document.writeln('	</tr>');
			document.writeln('</table>');

            document.writeln('<div id="palette_'+rte+'" style="display:none; position: absolute; z-index: 11; border: 1px solid #444; background-color: #ddd;">');
            document.writeln(colorPickerDivContents(rte));
            document.writeln('</div>');


            document.writeln('<div id="frameSizingCont'+rte+'" style="padding: 2px; text-align: right; width: 420px">');
            document.writeln('<img class="rteButtonImg" src="'+rteRootUrl+'/images/area_expand.gif" width="9" height="9" alt="expand" title="Expand Editing Frame" onclick="expandRTEFrame(\''+rte+'\')" border="0" id="imgRTEExpand'+rte+'">&nbsp;');
            document.writeln('<img class="rteButtonImg" src="'+rteRootUrl+'/images/area_contract.gif" width="9" height="9" alt="contract" title="Contract Editing Frame" onclick="expandRTEFrame(\''+rte+'\', 1)" border="0" id="imgRTEContract'+rte+'">');
            document.writeln('</div>');
        }
		document.writeln('<iframe id="editFrame' + rte + '" name="' + rte + '" width="' + width + 'px" height="' + height + 'px"></iframe>');

	    document.writeln('</div>');
	    document.writeln('<div class="rteSwitchTab" id="'+rte+'SwitchTab" >');
	    document.writeln('<span class="rteSwitchTabOn" id="'+rte+'rteTab">RTE View</span><span class="rteSwitchTabOff" id="'+rte+'htmlTab"><a href="#" onclick="toggleHTMLView(\''+rte+'\'); return false">HTML View</a></span>');

		//if (!readOnly) document.writeln('<br /><input type="checkbox" id="chkSrc' + rte + '" onclick="toggleHTMLSrc(\'' + rte + '\',' + buttons + ');" />&nbsp;<label for="chkSrc' + rte + '">View Source</label>');
		//document.writeln('<iframe width="154" height="104" id="cp' + rte + '" src="' + includesPath + 'palette.htm" marginwidth="0" marginheight="0" scrolling="no" style="visibility:hidden; position: absolute;"></iframe>');
		//document.writeln('<input type="hidden" id="hdn' + rte + '" name="' + rte + '" value="">');
		document.writeln('</div></div>');
		
		//document.getElementById('hdn' + rte).value = html;
		enableDesignMode(rte, html, readOnly);
	} else {
		if (!readOnly) {
			document.writeln('<textarea name="' + rte + '" id="' + rte + '" style="width: ' + width + 'px; height: ' + height + 'px;">' + getDefaultEditorValue(rte) + '</textarea>');
		} else {
			document.writeln('<textarea name="' + rte + '" id="' + rte + '" style="width: ' + width + 'px; height: ' + height + 'px;" readonly>' + getDefaultEditorValue(rte) + '</textarea>');
		}
	}
}



function enableDesignMode(rte, html, readOnly) {
    /* 
	var frameHtml = "<html id=\"" + rte + "\">\n";
	frameHtml += "<head>\n";
	//to reference your stylesheet, set href property below to your stylesheet path and uncomment
	if (cssFile && cssFile.length > 0) {
		frameHtml += "<link media=\"all\" type=\"text/css\" href=\"" + cssFile + "\" rel=\"stylesheet\">\n";
	} else {
		frameHtml += "<style>\n";
		frameHtml += "body {\n";
		frameHtml += "	background: #FFFFFF;\n";
		frameHtml += "	margin: 0px;\n";
		frameHtml += "	padding: 0px;\n";
		frameHtml += "}\n";
		frameHtml += "</style>\n";
	}
	frameHtml += "</head>\n";
	frameHtml += "<body>\n";
	frameHtml += html + "\n";
	frameHtml += "</body>\n";
	frameHtml += "</html>";
    */
    setDefaultRTEValue(rte);
    rteFrame = 'editFrame' + rte;

	if (document.all) {
		var oRTE = frames[rteFrame].document;
		if (!readOnly) {
			oRTE.designMode = "On";
            // following ln causes a parse error in safari, and is not needed even by IE
			//frames[rteFrame].document.attachEvent("onkeypress", function evt_ie_keypress(event) {ieKeyPress(event, rteFrame);});
		}
    } 
    else {
        if (!readOnly) document.getElementById(rteFrame).contentDocument.designMode = "on";
    }
}

function updateRTE(rte) {
	if (!isRichText) return;
	
	//check for readOnly mode
	var readOnly = false;
	if (document.all) {
		if (frames[rte].document.designMode != "On") readOnly = true;
	} else {
		if (document.getElementById(rte).contentDocument.designMode != "on") readOnly = true;
	}
	
	if (isRichText && !readOnly) {
		//if viewing source, switch back to design view
		if (document.getElementById("chkSrc" + rte).checked) document.getElementById("chkSrc" + rte).click();
		setHiddenVal(rte);
	}
}

function setHiddenVal(rte) {
	//set hidden form field value for current rte
	var oHdnField = document.getElementById('hdn' + rte);
	
	//convert html output to xhtml (thanks Timothy Bell and Vyacheslav Smolin!)
	if (oHdnField.value == null) oHdnField.value = "";
	if (document.all) {
		if (generateXHTML) {
			oHdnField.value = get_xhtml(frames[rte].document.body, lang, encoding);
		} else {
			oHdnField.value = frames[rte].document.body.innerHTML;
		}
	} else {
		if (generateXHTML) {
			oHdnField.value = get_xhtml(document.getElementById(rte).contentWindow.document.body, lang, encoding);
		} else {
			oHdnField.value = document.getElementById(rte).contentWindow.document.body.innerHTML;
		}
	}
	
	//if there is no content (other than formatting) set value to nothing
	if (stripHTML(oHdnField.value.replace("&nbsp;", " ")) == "" &&
		oHdnField.value.toLowerCase().search("<hr") == -1 &&
		oHdnField.value.toLowerCase().search("<img") == -1) oHdnField.value = "";
}

function updateRTEs() {
	var vRTEs = allRTEs.split(";");
	for (var i = 0; i < vRTEs.length; i++) {
		updateRTE(vRTEs[i]);
	}
}

function rteCommand(rte, command, option) {
	//function to perform command
    rte = 'editFrame' + rte;
	var oRTE;
	if (document.all) {
		oRTE = frames[rte];
	} else {
		oRTE = document.getElementById(rte).contentWindow;
	}
	
	try {
		oRTE.focus();
	  	oRTE.document.execCommand(command, false, option);
		oRTE.focus();
	} catch (e) {
		alert(e);
		//setTimeout("rteCommand('" + rte + "', '" + command + "', '" + option + "');", 10);
	}
}

function toggleHTMLSrc(rte, buttons) {
	//contributed by Bob Hutzel (thanks Bob!)
	var oHdnField = document.getElementById('hdn' + rte);
	
	if (document.getElementById("chkSrc" + rte).checked) {
		//we are checking the box
		if (buttons) {
			showHideElement("Buttons1_" + rte, "hide");
			showHideElement("Buttons2_" + rte, "hide");
		}
		setHiddenVal(rte);
		if (document.all) {
			frames[rte].document.body.innerText = oHdnField.value;
		} else {
			var oRTE = document.getElementById(rte).contentWindow.document;
			var htmlSrc = oRTE.createTextNode(oHdnField.value);
			oRTE.body.innerHTML = "";
			oRTE.body.appendChild(htmlSrc);
		}
	} else {
		//we are unchecking the box
		if (buttons) {
			showHideElement("Buttons1_" + rte, "show");
			showHideElement("Buttons2_" + rte, "show");
		}
		if (document.all) {
			//fix for IE
			var output = escape(frames[rte].document.body.innerText);
			output = output.replace("%3CP%3E%0D%0A%3CHR%3E", "%3CHR%3E");
			output = output.replace("%3CHR%3E%0D%0A%3C/P%3E", "%3CHR%3E");
			frames[rte].document.body.innerHTML = unescape(output);
		} else {
			var oRTE = document.getElementById(rte).contentWindow.document;
			var htmlSrc = oRTE.body.ownerDocument.createRange();
			htmlSrc.selectNodeContents(oRTE.body);
			oRTE.body.innerHTML = htmlSrc.toString();
		}
	}
}


function dlgInsertTable(rte, command) {
	//function to open/close insert table dialog
	//save current values
	parent.command = command;
	currentRTE = rte;
	InsertTable = popUpWin(includesPath + 'insert_table.htm', 'InsertTable', 360, 180, '');
}

function dlgInsertLink(rte, command) {
    var szURL = prompt("Enter a URL:", "");
    if (szURL && szURL != 'http://') {
        document.getElementById('editFrame'+rte).contentWindow.document.execCommand("CreateLink",false,
szURL);
    }
}

/*
	//function to open/close insert table dialog
	//save current values
	parent.command = command;
	currentRTE = rte;
	InsertLink = popUpWin(includesPath + 'insert_link.htm', 'InsertLink', 360, 180, '');
	
	//get currently highlighted text and set link text value
	setRange(rte);
	var linkText = '';
	if (isIE) {
		linkText = stripHTML(rng.htmlText);
	} else {
		linkText = stripHTML(rng.toString());
	}
	setLinkText(linkText);
}
*/

function setLinkText(linkText) {
	//set link text value in insert link dialog
	try {
		window.InsertLink.document.linkForm.linkText.value = linkText;
	} catch (e) {
		//may take some time to create dialog window.
		//Keep looping until able to set.
		setTimeout("setLinkText('" + linkText + "');", 10);
	}
}


//Function to clear form
function ResetForm(frameName, skipConfirm) {
	if (skipConfirm || window.confirm('Clear the contents of this text editing window?')) {
		document.getElementById(frameName).contentWindow.focus();
	 	document.getElementById(frameName).contentWindow.document.body.innerHTML = ''; 
	 	return true;
	 } 
	 return false;		
}

function popUpWin (url, win, width, height, options) {
	var leftPos = (screen.availWidth - width) / 2;
	var topPos = (screen.availHeight - height) / 2;
	options += 'width=' + width + ',height=' + height + ',left=' + leftPos + ',top=' + topPos;
	return window.open(url, win, options);
}


function addImage(rte) {
	//function to add image
	imagePath = prompt('Enter Image URL:', 'http://');				
	if ((imagePath != null) && (imagePath != "")) {
		rteCommand(rte, 'InsertImage', imagePath);
	}
}

// Ernst de Moor: Fix the amount of digging parents up, in case the RTE editor itself is displayed in a div.
// KJR 11/12/2004 Changed to position palette based on parent div, so palette will always appear in proper location regardless of nested divs
function getOffsetTop(elm) {
	var mOffsetTop = elm.offsetTop;
	var mOffsetParent = elm.offsetParent;
	var parents_up = 2; //the positioning div is 2 elements up the tree
	
	while(parents_up > 0) {
		mOffsetTop += mOffsetParent.offsetTop;
		mOffsetParent = mOffsetParent.offsetParent;
		parents_up--;
	}
	
	return mOffsetTop;
}

// Ernst de Moor: Fix the amount of digging parents up, in case the RTE editor itself is displayed in a div.
// KJR 11/12/2004 Changed to position palette based on parent div, so palette will always appear in proper location regardless of nested divs
function getOffsetLeft(elm) {
	var mOffsetLeft = elm.offsetLeft;
	var mOffsetParent = elm.offsetParent;
	var parents_up = 2;
	
	while(mOffsetParent && mOffsetParent.tagName != 'DIV') {
	    mOffsetLeft += mOffsetParent.offsetLeft;
		mOffsetParent = mOffsetParent.offsetParent;
		parents_up--;
	}
	
	return mOffsetLeft;
}

function selectFont(rte, selectname) {
	//function to handle font changes
	var idx = document.getElementById(selectname).selectedIndex;
	// First one is always a label
	if (idx != 0) {
		var selected = document.getElementById(selectname).options[idx].value;
		var cmd = selectname.replace('_' + rte, '');
		rteCommand(rte, cmd, selected);
		document.getElementById(selectname).selectedIndex = 0;
	}
}

function insertHTML(html) {
	//function to add HTML -- thanks dannyuk1982
	var rte = currentRTE;
	
	var oRTE;
	if (document.all) {
		oRTE = frames[rte];
	} else {
		oRTE = document.getElementById(rte).contentWindow;
	}
	
	oRTE.focus();
	if (document.all) {
		var oRng = oRTE.document.selection.createRange();
		oRng.pasteHTML(html);
		oRng.collapse(false);
		oRng.select();
	} else {
		oRTE.document.execCommand('insertHTML', false, html);
	}
}

function showHideElement(element, showHide) {
	//function to show or hide elements
	//element variable can be string or object
	if (document.getElementById(element)) {
		element = document.getElementById(element);
	}
	
	if (showHide == "show") {
		element.style.visibility = "visible";
	} else if (showHide == "hide") {
		element.style.visibility = "hidden";
	}
}

function setRange(rte) {
	//function to store range of current selection
	var oRTE;
    rte = 'editFrame' + rte;
	if (document.all) {
		oRTE = frames[rte];
		var selection = oRTE.document.selection; 
		if (selection != null) rng = selection.createRange();
	} else {
		oRTE = document.getElementById(rte).contentWindow;
		var selection = oRTE.getSelection();
		rng = selection.getRangeAt(selection.rangeCount - 1).cloneRange();
	}
	return rng;
}

function stripHTML(oldString) {
	//function to strip all html
	var newString = oldString.replace(/(<([^>]+)>)/ig,"");
	
	//replace carriage returns and line feeds
   newString = newString.replace(/\r\n/g," ");
   newString = newString.replace(/\n/g," ");
   newString = newString.replace(/\r/g," ");
	
	//trim string
	newString = trim(newString);
	
	return newString;
}

function trim(inputString) {
   // Removes leading and trailing spaces from the passed string. Also removes
   // consecutive spaces and replaces it with one space. If something besides
   // a string is passed in (null, custom object, etc.) then return the input.
   if (typeof inputString != "string") return inputString;
   var retValue = inputString;
   var ch = retValue.substring(0, 1);
	
   while (ch == " ") { // Check for spaces at the beginning of the string
      retValue = retValue.substring(1, retValue.length);
      ch = retValue.substring(0, 1);
   }
   ch = retValue.substring(retValue.length - 1, retValue.length);
	
   while (ch == " ") { // Check for spaces at the end of the string
      retValue = retValue.substring(0, retValue.length - 1);
      ch = retValue.substring(retValue.length - 1, retValue.length);
   }
	
	// Note that there are two spaces in the string - look for multiple spaces within the string
   while (retValue.indexOf("  ") != -1) {
		// Again, there are two spaces in each of the strings
      retValue = retValue.substring(0, retValue.indexOf("  ")) + retValue.substring(retValue.indexOf("  ") + 1, retValue.length);
   }
   return retValue; // Return the trimmed string back to the user
}

//********************
//Gecko-Only Functions
//********************
function geckoKeyPress(evt) {
	//function to add bold, italic, and underline shortcut commands to gecko RTEs
	//contributed by Anti Veeranna (thanks Anti!)
	var rte = evt.target.id;
	
	if (evt.ctrlKey) {
		var key = String.fromCharCode(evt.charCode).toLowerCase();
		var cmd = '';
		switch (key) {
			case 'b': cmd = "bold"; break;
			case 'i': cmd = "italic"; break;
			case 'u': cmd = "underline"; break;
		};

		if (cmd) {
			rteCommand(rte, cmd, null);
			
			// stop the event bubble
			evt.preventDefault();
			evt.stopPropagation();
		}
 	}
}

//*****************
//IE-Only Functions
//*****************
function ieKeyPress(evt, rte) {
	var key = (evt.which || evt.charCode || evt.keyCode);
	var stringKey = String.fromCharCode(key).toLowerCase();
	
//the following breaks list and indentation functionality in IE (don't use)
//	switch (key) {
//		case 13:
//			//insert <br> tag instead of <p>
//			//change the key pressed to null
//			evt.keyCode = 0;
//			
//			//insert <br> tag
//			currentRTE = rte;
//			insertHTML('<br>');
//			break;
//	};
}

function checkspell() {
	//function to perform spell check
	try {
		var tmpis = new ActiveXObject("ieSpell.ieSpellExtension");
		tmpis.CheckAllLinkedDocuments(document);
	}
	catch(exception) {
		if(exception.number==-2146827859) {
			if (confirm("ieSpell not detected.  Click Ok to go to download page."))
				window.open("http://www.iespell.com/download.php","DownLoad");
		} else {
			alert("Error Loading ieSpell: Exception " + exception.number);
		}
	}
}

function raiseButton(e) {
	var el = window.event.srcElement;
	
	className = el.className;
	if (className == 'rteButtonImg' || className == 'rteImageLowered') {
		el.className = 'rteImageRaised';
	}
}

function normalButton(e) {
	var el = window.event.srcElement;
	
	className = el.className;
	if (className == 'rteImageRaised' || className == 'rteImageLowered') {
		el.className = 'rteButtonImg';
	}
}

function lowerButton(e) {
	var el = window.event.srcElement;
	
	className = el.className;
	if (className == 'rteButtonImg' || className == 'rteImageRaised') {
		el.className = 'rteImageLowered';
	}
}

function getRteContent() {
	var myRTEs = allRTEs.split(";");
    for (var i=0; i<myRTEs.length; i++) {
        var baseName = myRTEs[i];
        if (document.getElementById('htmlview'+baseName)) {
            rteval = document.getElementById('htmlview'+baseName).value;
        }
        else {
            rteval = getRTEContentStr('editFrame'+ baseName);
        }
        if (rteval) {
            eval('document.forms[0].elements[\''+baseName+'\'].value = rteval');
        }
    }
    return true;
}

function getRTEContentStr(frameName) {
    rteval = '';

	if (document.all) {
		if (generateXHTML) {
			rteval = get_xhtml(frames[frameName].document.body, lang, encoding);
		} else {
			rteval = frames[frameName].document.body.innerHTML;
		}
	} else {
		if (generateXHTML) {
			rteval = get_xhtml(document.getElementById(frameName).contentWindow.document.body, lang, encoding);
		} else {
			rteval = document.getElementById(frameName).contentWindow.document.body.innerHTML;
		}
	}

    return rteval;
}




// ------------------------------------------------------------------
// special onset stuff

// switch from RTE to HTML view and back, using the little tabs
function toggleHTMLView(frameName) {
    if (document.getElementById('htmlview'+frameName)) { 

        // set the iframe's html content to the value of the textzarea
        iDoc = document.getElementById('editFrame'+frameName).contentWindow.document;
        iDoc.body.innerHTML = '';
        iDoc.body.innerHTML = document.getElementById('htmlview'+frameName).value;

        // remove the textarea and all other silly nodes
        myparent = document.getElementById(frameName+'Wrapper').parentNode;
        myparent.removeChild(document.getElementById('twrapper'+frameName))

        document.getElementById(frameName+'Wrapper').style.display = 'block';
		if (isRichText && isGecko) { // see bugzilla #217205
            document.getElementById('editFrame'+frameName).contentDocument.designMode = "on"
        }

        // switch tabs
        document.getElementById(frameName+'rteTab').className = 'rteSwitchTabOn';
        document.getElementById(frameName+'rteTab').innerHTML = 'RTE View';
        document.getElementById(frameName+'htmlTab').className = 'rteSwitchTabOff';
        document.getElementById(frameName+'htmlTab').innerHTML = '<a href="#" onclick="toggleHTMLView(\''+frameName+'\'); return false">HTML View</a>';
    }
    else {
        document.getElementById(frameName+'Wrapper').style.display = 'none';
        myparent = document.getElementById(frameName+'Wrapper').parentNode;
        
        twrap = document.createElement('div');
        twrap.setAttribute('id', 'twrapper'+frameName);
        twrap.className = 'rteWrapper';

        tarea = document.createElement('textarea');
        tarea.setAttribute('id', 'htmlview'+frameName);
        tarea.setAttribute('name', 'htmlview'+frameName);
        tarea.setAttribute('wrap', 'off');
        tarea.style.width = '343px';
        tarea.style.height = '200px';
        tarea.value = getRTEContentStr('editFrame'+ frameName); 

        sizingCont = document.getElementById('frameSizingCont'+frameName).cloneNode(true);

        twrap.appendChild(sizingCont);
        twrap.appendChild(tarea);

        // insert all this as the first child so it goes above the tabs
        myparent.insertBefore(twrap, myparent.firstChild);


        // switch tabs
        document.getElementById(frameName+'rteTab').className = 'rteSwitchTabOff';
        document.getElementById(frameName+'rteTab').innerHTML = '<a href="javascript:toggleHTMLView(\''+frameName+'\')">RTE View</a>';
        document.getElementById(frameName+'htmlTab').className = 'rteSwitchTabOn';
        document.getElementById(frameName+'htmlTab').innerHTML = 'HTML View';
    }
}


// for stretching/shrinking the RTE
// @todo make draggable
function expandRTEFrame(frameName, reduce) {
    if (document.getElementById('htmlview'+frameName)) { // have to use entirely different methods for the textareas
        elem = document.getElementById('htmlview'+frameName);
        newheight = parseInt(elem.style.height);
        newwidth = parseInt(elem.style.width);
        if (!reduce || (newwidth > 30 && newheight > 30)) {
            newheight += (reduce)? -10 : 10;
            newwidth += (reduce)? -10 : 10;
            elem.style.height = newheight + 'px';
            elem.style.width = newwidth + 'px';
        }
    }
    else { // its a iframe, set height + width directly
        elem = document.getElementById('editFrame'+frameName);
        newheight = 1 * elem.height;
        newwidth = 1 * elem.width;
        if (!reduce || (newwidth > 30 && newheight > 30)) {
            newheight += (reduce)? -10 : 10;
            newwidth += (reduce)? -10 : 10;
            elem.height = newheight;
            elem.width = newwidth;
        }
    }
}

// automatically look for default value in the rte, in a 'valHolder' div. Set the 
// editframe document content to the same value
function setDefaultRTEValue(baseName) {

    rteFrame = 'editFrame'+baseName;
        var iRTEdoc = document.getElementById(rteFrame).contentWindow.document;
        //iRTEdoc.open();
        //iRTEdoc.write(frameHtml);
        //iRTEdoc.close();
        //if (isGecko && !readOnly) {
            ////attach a keyboard handler for gecko browsers to make keyboard shortcuts work
            //iRTEdoc.addEventListener("keypress", geckoKeyPress, true);
        //}

        defval = getDefaultEditorValue(baseName);

        if (document.getElementById('valHolder'+baseName)) {
            //iRTEdoc.body.innerHTML = '';
            //iRTEdoc.body.innerHTML = defval;
            iRTEdoc.open();
            iRTEdoc.write(defval);
            iRTEdoc.close();
        }
        else if (tbox = document.getElementById('editFrame'+baseName)) {
            if ('textarea' == tbox.type) {
                tbox.value = defval;
            }
        }
        //gecko may take some time to enable design mode.
        //Keep looping until able to set.
        //setTimeout('setDefaultRTEValue(\''+ baseName +'\')', 10);
        //setTimeout("enableDesignMode('" + rteFrame + "', '" + html + "', " + readOnly + ");", 10);
}

/* get the default value for the RTE or textarea as a string of HTML
 */
function getDefaultEditorValue (baseName) {
    if (document.getElementById('valHolder'+baseName)) {
        return document.getElementById('valHolder'+baseName).innerHTML;
    }
}


// set the contents of the editFrame to ''
function ResetForm(frameName) {
        if (window.confirm('Clear the contents of this text editing window?')) {
                document.getElementById('editFrame'+frameName).contentWindow.focus()
                document.getElementById('editFrame'+frameName).contentWindow.document.body.innerHTML = '';
                return true;
         }
         return false;
}



// show the colorpicker popup and remember what is selected in the RTE
function sicomaColorPalette(frameName, command) {
    if ((command == "forecolor") || (command == "hilitecolor")) {
        parent.command = command;
        buttonElement = document.getElementById(command+'_'+frameName);
        thisPalette = document.getElementById('palette_'+frameName);
        thisPalette.style.left = (getOffsetLeft(buttonElement) - 110) + "px";
        thisPalette.style.top = (getOffsetTop(buttonElement) + buttonElement.offsetHeight) + "px";
        if (thisPalette.style.display == "none")
            thisPalette.style.display="block";
        else {
            thisPalette.style.display="none";
        }

        //get current selected range
        var sel = document.getElementById('editFrame'+frameName).contentWindow.document.selection;
        if (sel!=null) {
            rng = sel.createRange();
        }
    }
}


//Function to set color on the selected range, called from colorpicker popup
function setColor(frameName, color) {
    rteName = 'editFrame'+frameName;
	if (document.all) {
        if (parent.command == "hilitecolor") parent.command = "backcolor";

		//retrieve selected range
		var sel = document.getElementById(rteName).contentWindow.document.selection; 
		if (sel!=null) {
			var newRng = sel.createRange();
			newRng = rng;
			newRng.select();
		}
	}
	else {
		document.getElementById(rteName).contentWindow.focus();
	}
	document.getElementById(rteName).contentWindow.document.execCommand(parent.command, false, color);
	document.getElementById(rteName).contentWindow.focus();
	document.getElementById("palette_"+frameName).style.display="none";
}
// highlights the color under the mouse and shows the RGB value
function colorPickerHighLight(frameName, color) {
    document.getElementById('palette_' + frameName + '_selectedColor').style.background = color;
    document.getElementById('palette_' + frameName + '_selectedColorValue').innerHTML = color;
}
// creates the colorpicker div dynamically from the colors array above
function colorPickerDivContents(frameName) {
    var total = colors.length;
    var width = 18;
    var cp_contents = "";
    cp_contents += '<table border=\"0\" cellspacing=\"2\" cellpadding=\"0\" bgcolor=\"#dddddd\">';
    var use_highlight = (document.getElementById || document.all)?true:false;
    for (var i=0; i<total; i++) {
        if ((i % width) == 0) { cp_contents += "<tr>"; }
        if (use_highlight) { var mo = 'onmouseover="colorPickerHighLight(\''+frameName+'\', \''+colors[i]+'\')"'; }
        else { mo = ""; }
        cp_contents += '<td bgcolor="'+colors[i]+'"><span style="font-size:0.75em"><a href="#" onclick="setColor(\''+frameName+'\', \''+colors[i]+'\');return false;" '+mo+' style="text-decoration:none;">&nbsp;&nbsp;&nbsp;</a></span></td>';
        if ( ((i+1)>=total) || (((i+1) % width) == 0)) { 
            cp_contents += "</tr>";
        }
    }

    if (document.getElementById) {
        cp_contents += '<tr><td colspan="'+width+'">';
        cp_contents += '<div style="width:48%; border: 1px solid #ddd; float: left; background-color: #fff" id="palette_'+frameName+'_selectedColor">&nbsp;</div>';
        cp_contents += '<div style="width:48%; border: 1px solid #ddd; float: right; background-color: #fff" id="palette_'+frameName+'_selectedColorValue">#ffffff</div>';
        cp_contents += '</td></tr>';
    }
    cp_contents += "</table>";
    return cp_contents;
}











/*==============================================================================

                             HTML2XHTML Converter 1.0
                             ========================
                       Copyright (c) 2004 Vyacheslav Smolin


Author:
-------
Vyacheslav Smolin (http://www.richarea.com, http://html2xhtml.richarea.com,
re@richarea.com)

About the script:
-----------------
HTML2XHTML Converter (H2X) generates a well formed XHTML string from a HTML DOM
object.

Requirements:
-------------
H2X works in  MS IE 5.0 for Windows or above,  in Netscape 7.1,  Mozilla 1.3 or
above. It should work in all Mozilla based browsers.

Usage:
------
Please see description of function get_xhtml below.

Demo:
-----
http://html2xhtml.richarea.com/, http://www.richarea.com/demo/

License:
--------
Free for non-commercial using. Please contact author for commercial licenses.


==============================================================================*/


//add \n before opening tag
var need_nl_before = '|div|p|table|tbody|tr|td|th|title|head|body|script|comment|li|meta|h1|h2|h3|h4|h5|h6|hr|ul|ol|option|';
//add \n after opening tag
var need_nl_after = '|html|head|body|p|th|style|';

var re_comment = new RegExp();
re_comment.compile("^<!--(.*)-->$");

var re_hyphen = new RegExp();
re_hyphen.compile("-$");


// Convert inner text of node to xhtml
// Call: get_xhtml(node);
//       get_xhtml(node, lang, encoding) -- to convert whole page
// other parameters are for inner usage and should be omitted
// Parameters:
// node - dom node to convert
// lang - document lang (need it if whole page converted)
// encoding - document charset (need it if whole page converted)
// need_nl - if true, add \n before a tag if it is in list need_nl_before
// inside_pre - if true, do not change content, as it is inside a <pre>
function get_xhtml(node, lang, encoding, need_nl, inside_pre) {
	var i;
	var text = '';
	var children = node.childNodes;
	var child_length = children.length;
	var tag_name;
	var do_nl = need_nl ? true : false;
	var page_mode = true;
	
	for (i = 0; i < child_length; i++) {
		var child = children[i];
		
		switch (child.nodeType) {
			case 1: { //ELEMENT_NODE
				var tag_name = String(child.tagName).toLowerCase();
				
				if (tag_name == '') break;
				
				if (tag_name == 'meta') {
					var meta_name = String(child.name).toLowerCase();
					if (meta_name == 'generator') break;
				}
				
				if (!need_nl && tag_name == 'body') { //html fragment mode
					page_mode = false;
				}
				
				if (tag_name == '!') { //COMMENT_NODE in IE 5.0/5.5
					//get comment inner text
					var parts = re_comment.exec(child.text);
					
					if (parts) {
						//the last char of the comment text must not be a hyphen
						var inner_text = parts[1];
						text += fix_comment(inner_text);
					}
				} else {
					if (tag_name == 'html') {
						text = '<?xml version="1.0" encoding="'+encoding+'"?>\n<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">\n';
					}
					
					//inset \n to make code more neat
					if (need_nl_before.indexOf('|'+tag_name+'|') != -1) {
						if ((do_nl || text != '') && !inside_pre) text += '\n';
					} else {
						do_nl = true;
					}
					
					text += '<'+tag_name;
					
					//add attributes
					var attr = child.attributes;
					var attr_length = attr.length;
					var attr_value;
					
					var attr_lang = false;
					var attr_xml_lang = false;
					var attr_xmlns = false;
					
					var is_alt_attr = false;
					
					for (j = 0; j < attr_length; j++) {
						var attr_name = attr[j].nodeName.toLowerCase();
						
						if (!attr[j].specified && 
							(attr_name != 'selected' || !child.selected) && 
							(attr_name != 'style' || child.style.cssText == '') && 
							attr_name != 'value') continue; //IE 5.0
						
						if (attr_name == '_moz_dirty' || 
							attr_name == '_moz_resizing' || 
							tag_name == 'br' && 
							attr_name == 'type' && 
							child.getAttribute('type') == '_moz') continue;
						
						var valid_attr = true;
						
						switch (attr_name) {
							case "style":
								attr_value = child.style.cssText;
								break;
							case "class":
								attr_value = child.className;
								break;
							case "http-equiv":
								attr_value = child.httpEquiv;
								break;
							case "noshade": break; //this set of choices will extend
							case "checked": break;
							case "selected": break;
							case "multiple": break;
							case "nowrap": break;
							case "disabled": break;
								attr_value = attr_name;
								break;
							default:
								try {
									attr_value = child.getAttribute(attr_name, 2);
								} catch (e) {
									valid_attr = false;
								}
								break;
						}
						
						//html tag attribs
						if (attr_name == 'lang') {
							attr_lang = true;
							attr_value = lang;
						}
						if (attr_name == 'xml:lang') {
							attr_xml_lang = true;
							attr_value = lang;
						}
						if (attr_name == 'xmlns') attr_xmlns = true;
						if (valid_attr) {
							//value attribute set to "0" is not handled correctly in Mozilla
							if (!(tag_name == 'li' && attr_name == 'value')) {
								text += ' '+attr_name+'="'+fix_attribute(attr_value)+'"';
							}
						}
						
						if (attr_name == 'alt') is_alt_attr = true;
					}
					
					if (tag_name == 'img' && !is_alt_attr) {
						text += ' alt=""';
					}
					
					if (tag_name == 'html') {
						if (!attr_lang) text += ' lang="'+lang+'"';
						if (!attr_xml_lang) text += ' xml:lang="'+lang+'"';
						if (!attr_xmlns) text += ' xmlns="http://www.w3.org/1999/xhtml"';
					}
					
					if (child.canHaveChildren || child.hasChildNodes()){
						text += '>';
//						if (need_nl_after.indexOf('|'+tag_name+'|') != -1) {
//							text += '\n';
//						}
						text += get_xhtml(child, lang, encoding, true, inside_pre || tag_name == 'pre' ? true : false);
						text += '</'+tag_name+'>';
					} else {
						if (tag_name == 'style' || tag_name == 'title' || tag_name == 'script') {
							text += '>';
							var inner_text;
							if (tag_name == 'script') {
								inner_text = child.text;
							} else {
								inner_text = child.innerHTML;
							}
							
							if (tag_name == 'style') {
								inner_text = String(inner_text).replace(/[\n]+/g,'\n');
							}
							
							text += inner_text+'</'+tag_name+'>';
						} else {
							text += ' />';
						}
					}
				}
				break;
			}
			case 3: { //TEXT_NODE
				if (!inside_pre) { //do not change text inside <pre> tag
					if (child.nodeValue != '\n') {
						text += fix_text(child.nodeValue);
					}
				} else {
					text += child.nodeValue;
				}
				break;
			}
			case 8: { //COMMENT_NODE
				text += fix_comment(child.nodeValue);
				break;
			}
			default:
				break;
		}
	}
	
	if (!need_nl && !page_mode) { //delete head and body tags from html fragment
		text = text.replace(/<\/?head>[\n]*/gi, "");
		text = text.replace(/<head \/>[\n]*/gi, "");
		text = text.replace(/<\/?body>[\n]*/gi, "");
	}
	
	return text;
}

//fix inner text of a comment
function fix_comment(text) {
	//delete double hyphens from the comment text
	text = text.replace(/--/g, "__");
	
	if(re_hyphen.exec(text)) { //last char must not be a hyphen
		text += " ";
	}
	
	return "<!--"+text+"-->";
}

//fix content of a text node
function fix_text(text) {
	//convert <,> and & to the corresponding entities
	return String(text).replace(/\n{2,}/g, "\n").replace(/\&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\u00A0/g, "&nbsp;");
}

//fix content of attributes href, src or background
function fix_attribute(text) {
	//convert <,>, & and " to the corresponding entities
	return String(text).replace(/\&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/\"/g, "&quot;");
}
