
function integr_yst_testFocusKw(lang) {
	// Retrieve focus keyword and trim
	var focuskw = jQuery.trim(jQuery('#' + wpseoMetaboxL10n.field_prefix + 'focuskw-' + lang).val());
	focuskw = yst_escapeFocusKw(focuskw).toLowerCase();

	if (jQuery('#editable-post-name-full').length) {
		var postname = jQuery('#editable-post-name-full').text();
		var url = wpseoMetaboxL10n.wpseo_permalink_template.replace('%postname%', postname).replace('http://', '');
	}
	var p = new RegExp("(^|[ \s\n\r\t\.,'\(\"\+;!?:\-])" + focuskw + "($|[ \s\n\r\t.,'\)\"\+!?:;\-])", 'gim');
	//remove diacritics of a lower cased focuskw for url matching in foreign lang
	var focuskwNoDiacritics = removeLowerCaseDiacritics(focuskw);
	var p2 = new RegExp(focuskwNoDiacritics.replace(/\s+/g, "[-_\\\//]"), 'gim');

	var focuskwresults = jQuery('#wpseo-metabox-lang-tabs-div-' + lang + ' #focuskwresults');
	var metadesc = jQuery('#wpseosnippet-' + lang).find('.desc span.content').text();

	if (focuskw != '') {
		var html = '<p>' + wpseoMetaboxL10n.keyword_header + '</p>';
		html += '<ul>';
		titleElm = jQuery( id_title_selector_qtransl + lang );
		title = qtrans_use( lang, titleElm.val() );
		if (title.length) {
			html += '<li>' + wpseoMetaboxL10n.article_header_text + ptest( title , p ) + '</li>';
		}
		html += '<li>' + wpseoMetaboxL10n.page_title_text + ptest(jQuery('#wpseosnippet_title-' + lang).text(), p) + '</li>';
		html += '<li>' + wpseoMetaboxL10n.page_url_text + ptest(url, p2) + '</li>';
		if (jQuery('#content').length) {
			html += '<li>' + wpseoMetaboxL10n.content_text + ptest(jQuery('#content').val(), p) + '</li>';
		}
		html += '<li>' + wpseoMetaboxL10n.meta_description_text + ptest(metadesc, p) + '</li>';
		html += '</ul>';
		focuskwresults.html(html);
	} else {
		focuskwresults.html('');
	}
}


function integr_yst_replaceVariables(str, callback, lang) {
	if (typeof str === "undefined") {
		return '';
	}
	
	console.log("------Prueba------");
	console.log(id_title_selector_qtransl + lang);
	// title
	if (jQuery( id_title_selector_qtransl + lang ).length) {
		str = str.replace(/%%title%%/g, jQuery( id_title_selector_qtransl + lang ).val());
	}

	// These are added in the head for performance reasons.
	str = str.replace(/%%sitedesc%%/g, wpseoMetaboxL10n.sitedesc);
	str = str.replace(/%%sitename%%/g, wpseoMetaboxL10n.sitename);
	str = str.replace(/%%sep%%/g, wpseoMetaboxL10n.sep);
	str = str.replace(/%%date%%/g, wpseoMetaboxL10n.date);
	str = str.replace(/%%id%%/g, wpseoMetaboxL10n.id);
	str = str.replace(/%%page%%/g, wpseoMetaboxL10n.page);
	str = str.replace(/%%currenttime%%/g, wpseoMetaboxL10n.currenttime);
	str = str.replace(/%%currentdate%%/g, wpseoMetaboxL10n.currentdate);
	str = str.replace(/%%currentday%%/g, wpseoMetaboxL10n.currentday);
	str = str.replace(/%%currentmonth%%/g, wpseoMetaboxL10n.currentmonth);
	str = str.replace(/%%currentyear%%/g, wpseoMetaboxL10n.currentyear);

	str = str.replace(/%%focuskw%%/g, jQuery('#yoast_wpseo_focuskw').val() );
	
	// excerpt
	var excerpt = '';
	if (jQuery('#excerpt').length) {
		excerpt = yst_clean(jQuery("#excerpt").val());
		str = str.replace(/%%excerpt_only%%/g, excerpt);
	}
	if ('' == excerpt && jQuery('#content').length) {
		excerpt = jQuery('#content').val().replace(/(<([^>]+)>)/ig,"").substring(0,wpseoMetaboxL10n.wpseo_meta_desc_length-1);
	}
	str = str.replace(/%%excerpt%%/g, excerpt);

	// parent page
	if (jQuery('#parent_id').length && jQuery('#parent_id option:selected').text() != wpseoMetaboxL10n.no_parent_text ) {
		str = str.replace(/%%parent_title%%/g, jQuery('#parent_id option:selected').text());
	}

	// remove double separators
	var esc_sep = yst_escapeFocusKw(wpseoMetaboxL10n.sep);
	var pattern = new RegExp(esc_sep + ' ' + esc_sep, 'g');
	str = str.replace(pattern, wpseoMetaboxL10n.sep);

	if (str.indexOf('%%') != -1 && str.match(/%%[a-z0-9_-]+%%/i) != null) {
		regex = /%%[a-z0-9_-]+%%/gi;
		matches = str.match(regex);
		for (i = 0; i < matches.length; i++) {
			if (replacedVars[matches[i]] != undefined) {
				str = str.replace(matches[i], replacedVars[matches[i]]);
			} else {
				replaceableVar = matches[i];
				// create the cache already, so we don't do the request twice.
				replacedVars[replaceableVar] = '';
				jQuery.post(ajaxurl, {
							action  : 'wpseo_replace_vars',
							string  : matches[i],
							post_id : jQuery('#post_ID').val(),
							_wpnonce: wpseoMetaboxL10n.wpseo_replace_vars_nonce
						}, function (data) {
							if (data) {
								replacedVars[replaceableVar] = data;
								integr_yst_replaceVariables(str, callback, lang);
							} else {
								integr_yst_replaceVariables(str, callback, lang);
							}
						}
				);
			}
		}
	}
	callback(str);
}

function integr_yst_updateTitle(force, lang) {
	var title = '';
	var titleElm = jQuery(id_title_selector_qtransl + lang);
	var titleLengthError = jQuery('#wpseo-metabox-lang-tabs-div-' + lang + ' #' + wpseoMetaboxL10n.field_prefix + 'title-length-warning');
	var divHtml = jQuery('<div />');
	var snippetTitle = jQuery('#wpseosnippet_title-' + lang); 				

	if(snippetTitle.text()){
		title = snippetTitle.text();
	}
	else if (titleElm.val()) {
		title = qtrans_use( lang, titleElm.val());
	} else {
		title = wpseoMetaboxL10n.wpseo_title_template;
		title = divHtml.html(title).text();
	}
	if (title == '') {
		snippetTitle.html('');
		titleLengthError.hide();
		return;
	}

	title = yst_clean(title);
	title = jQuery.trim(title);
	title = divHtml.text(title).html();

	if (force) {
		titleElm.val(title);
	}

	title = integr_yst_replaceVariables(title, function (title) {
		// do the placeholder
		var placeholder_title = divHtml.html(title).text();
		titleElm.attr('placeholder', placeholder_title);

		// and now the snippet preview title
		title = integr_yst_boldKeywords(title, false, lang);

		jQuery('#wpseosnippet_title-' + lang).html(title);

		var e = document.getElementById('wpseosnippet_title-' + lang);
		if (e != null) {
			if (e.scrollWidth > e.clientWidth) {
				titleLengthError.show();
			} else {
				titleLengthError.hide();
			}
		}

		integr_yst_testFocusKw(lang);
	}, lang);
}

function integr_yst_updateDesc(lang) {
	var desc = jQuery.trim(yst_clean(jQuery('#' + wpseoMetaboxL10n.field_prefix + 'metadesc-' + lang).val()));
	var divHtml = jQuery('<div />');
	var snippet = jQuery('#wpseosnippet-' + lang);

	if (desc == '' && wpseoMetaboxL10n.wpseo_metadesc_template != '') {
		desc = wpseoMetaboxL10n.wpseo_metadesc_template;
	}

	if (desc != '') {
		desc = integr_yst_replaceVariables(desc, function (desc) {
			desc = divHtml.text(desc).html();
			desc = yst_clean(desc);


			var len = -1;
			len = wpseoMetaboxL10n.wpseo_meta_desc_length - desc.length;

			if (len < 0)
				len = '<span class="wrong">' + len + '</span>';
			else
				len = '<span class="good">' + len + '</span>';

			jQuery('#wpseo-metabox-lang-tabs-div-' + lang + ' #' + wpseoMetaboxL10n.field_prefix + 'metadesc-length').html(len);

			desc = yst_trimDesc(desc);
			desc = integr_yst_boldKeywords(desc, false, lang);
			// Clear the autogen description.
			snippet.find('.desc span.autogen').html('');
			// Set our new one.
			snippet.find('.desc span.content').html(desc);

			integr_yst_testFocusKw(lang);
		}, lang);
	} else {
		// Clear the generated description
		snippet.find('.desc span.content').html('');
		integr_yst_testFocusKw(lang);

		if (jQuery('#content').length) {
			desc = jQuery('#content').val();
			desc = yst_clean(desc);
		}

		var focuskw = yst_escapeFocusKw(jQuery.trim(jQuery('#' + wpseoMetaboxL10n.field_prefix + 'focuskw-' + lang).val()));
		if (focuskw != '') {
			var descsearch = new RegExp(focuskw, 'gim');
			if (desc.search(descsearch) != -1 && desc.length > wpseoMetaboxL10n.wpseo_meta_desc_length) {
				desc = desc.substr(desc.search(descsearch), wpseoMetaboxL10n.wpseo_meta_desc_length);
			} else {
				desc = desc.substr(0, wpseoMetaboxL10n.wpseo_meta_desc_length);
			}
		} else {
			desc = desc.substr(0, wpseoMetaboxL10n.wpseo_meta_desc_length);
		}
		desc = integr_yst_boldKeywords(desc, false, lang);
		desc = yst_trimDesc(desc);
		snippet.find('.desc span.autogen').html(desc);
	}

}


function integr_yst_boldKeywords(str, url, lang) {
	var focuskw = yst_escapeFocusKw(jQuery.trim(jQuery('#' + wpseoMetaboxL10n.field_prefix + 'focuskw-' + lang).val()));
	var keywords;

	if (focuskw == '')
		return str;

	if (focuskw.search(' ') != -1) {
		keywords = focuskw.split(' ');
	} else {
		keywords = new Array(focuskw);
	}
	for (var i = 0; i < keywords.length; i++) {
		var kw = yst_clean(keywords[i]);
		var kwregex = '';
		if (url) {
			kw = kw.replace(' ', '-').toLowerCase();
			kwregex = new RegExp("([-/])(" + kw + ")([-/])?");
		} else {
			kwregex = new RegExp("(^|[ \s\n\r\t\.,'\(\"\+;!?:\-]+)(" + kw + ")($|[ \s\n\r\t\.,'\)\"\+;!?:\-]+)", 'gim');
		}
		if (str != undefined) {
			str = str.replace(kwregex, "$1<strong>$2</strong>$3");
		}
	}
	return str;
}

function integr_yst_updateSnippet(lang) {
	yst_updateURL();
	integr_yst_updateTitle(true, lang);
	integr_yst_updateDesc(lang);
}

/*
 *  Overwritten function to make a hack in the hidden title. The aim of this function is insert a space " " between
 *  different languages in hidden title. The original qtranslate configure the title in the next way:
 *  
 *   <!--:es-->Title in spanish<!--:--><!--:en-->Title in english<!--:-->
 *   
 *   The problem arises when the original JS function yst_clean() and ptest() filter the title. These functions delete
 *   the braces that delimits each language. The result  would be in the following way:
 *   
 *   Title in spanishTitle in english
 *   
 *   IN that way, the SEO plugin don´t filter in a correct way the title element. So, the goal is insert an space between the braces.
 *   
 *   <!--:--> <!--:en-->
 *   
 */
function yst_clean(str) {
	if (str == '' || str == undefined)
		return '';
	try {
		str = str.replace(/<\!--:-->/gi, '<!--:--> '); // <---- Line with the hack
		str = jQuery('<div/>').html(str).text();
		str = str.replace(/<\/?[^>]+>/gi, '');
		str = str.replace(/\[(.+?)\](.+?\[\/\\1\])?/g, '');
	} catch (e) {
	}

	return str;
}

/*
 *  Custom function to attach behaviour to the language tabs
 */
function intgr_yst_languages_tabs(){
	
	var active_meta_lang = wpseoMetaboxIntegration["default_lang"];
	var langs = wpseoMetaboxIntegration["langs"];
	
	// Hash
	/*
	var active_tab = window.location.hash;
	
	for (i = 0; i < langs.length; i++) {
		if (active_tab == '#wpseo-metabox-lang-tabs-div-' + langs[i] ){
			active_tab = '#wpseo-metabox-lang-tabs-div-' + langs[i];
		}else{
			active_tab = '#wpseo-metabox-lang-tabs-div-' + wpseoMetaboxIntegration["default_lang"];
		}
	}
	*/
	var active_tab = '#wpseo-metabox-lang-tabs-div-' + wpseoMetaboxIntegration["default_lang"];
	jQuery(active_tab).addClass('active');
	jQuery(".wpseo-lang-es a").addClass('tab-active');
	
	if (jQuery('#wpseo_meta .wpseo-metabox-lang-tabs-div').length > 0) {

		jQuery('#wpseo_meta #wpseo-metabox-lang-tabs-div-' + active_meta_lang).addClass('active');

		/*
		var descElm = jQuery('#' + wpseoMetaboxL10n.field_prefix + 'metadesc');
		var desc = jQuery.trim(yst_clean(descElm.val()));
		desc = jQuery('<div />').html(desc).text();
		descElm.val(desc);
		*/

		jQuery('#wpseo_meta a.wpseo_tablink_lang').click(function () {
			
			// Tabs 
			jQuery('#wpseo_meta .wpseo_tablink_lang').removeClass('tab-active');
			jQuery(this).addClass('tab-active');
			
			// Div with content
			jQuery('#wpseo_meta .wpseo-metabox-lang-tabs-div').removeClass('active');
			jQuery('#wpseo_meta .wpseo-metabox-lang-tabs-div li').removeClass('active');

			var id = jQuery(this).attr('tab');
			jQuery(id).addClass('active');
			// jQuery(this).parent().addClass('active');

			if (jQuery(this).hasClass('scroll')) {
				var scrollto = jQuery(this).attr('tab');
				jQuery("html, body").animate({
					scrollTop: jQuery(scrollto).offset().top
				}, 500);
			}
		});
	}
}

// Custom Variable
var id_title_selector_qtransl = "#qtrans_title_";

// ______________

var delay = (function () {
	var timer = 0;
	return function (callback, ms) {
		clearTimeout(timer);
		timer = setTimeout(callback, ms);
	};
})();

jQuery(document).ready(function () {
	replacedVars = new Array();
	
	var default_lang = wpseoMetaboxIntegration["default_lang"];
	var langs = wpseoMetaboxIntegration["langs"];
	
	var cache = {}, lastXhr;
	
	for (i = 0; i < langs.length; i++) {
		var lang = langs[i];
	
		if(lang == default_lang){
			continue;
		}
		
		jQuery('#' + wpseoMetaboxL10n.field_prefix + 'focuskw-' + lang).autocomplete({
			minLength   : 3,
			formatResult: function (row) {
				return jQuery('<div/>').html(row).html();
			},
			source      : function (request, response) {
				var term = request.term;
				if (term in cache) {
					response(cache[term]);
					return;
				}
				request._ajax_nonce = wpseoMetaboxL10n.wpseo_keyword_suggest_nonce;
				request.action = 'wpseo_get_suggest';

				lastXhr = jQuery.getJSON(ajaxurl, request, function (data, status, xhr) {
					cache[term] = data;
					if (xhr === lastXhr) {
						response(data);
					}
					return;
				});
			}
		});
		
		jQuery('#qtrans_title_' + lang).keyup(function () {
			integr_yst_updateTitle(false, lang);
			integr_yst_updateDesc(lang);
		});
		
		/*
		jQuery('#parent_id').change(function () {
			integr_yst_updateTitle(true, lang);
			integr_yst_updateDesc(lang);
		});
		*/
		
		// DON'T 'optimize' this to use descElm! descElm might not be defined and will cause js errors (Soliloquy issue)
		jQuery('#' + wpseoMetaboxL10n.field_prefix + 'metadesc-' + lang).keyup(function () {
			integr_yst_updateDesc(lang);
		});
		jQuery('#excerpt').keyup(function () {
			integr_yst_updateDesc(lang);
		});
		jQuery('#content').focusout(function () {
			integr_yst_updateDesc(lang);
		});
		
		// Help Boxes
		var focuskwhelptriggered = false;
		jQuery(document).on('change', '#' + wpseoMetaboxL10n.field_prefix + 'focuskw-' + lang, function () {
			var focuskwhelpElm = jQuery('#focuskwhelp-' + lang);
			if (jQuery('#' + wpseoMetaboxL10n.field_prefix + 'focuskw-' + lang).val().search(',') != -1) {
				focuskwhelpElm.click();
				focuskwhelptriggered = true;
			} else if (focuskwhelptriggered) {
				focuskwhelpElm.qtip("hide");
				focuskwhelptriggered = false;
			}

			integr_yst_updateSnippet(lang);
		});

		integr_yst_updateSnippet(lang);

	} // end loop of langs

	
	
	// Metabox langs TABS
	// intgr_yst_languages_tabs();
	jQuery("#wpseo-metabox-tabs-div").tabs();

});





