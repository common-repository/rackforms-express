<?php

/*
  Plugin Name: RackForms Express
  Plugin URI: http://www.rackforms.com/documentation/rackforms/wordpress/express.php
  Description: Install and Manage RackForms Express, The Best Free Form Builder For WordPress!
  Version: 1.0
  Author: nicsoft
  Author URI: http://www.rackforms.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
 */

/*
  Copyright 2008-2014 nicsoft  (email : info@rackforms.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

define('HEFO_VERSION', '1.3.9');

$hefo_options = get_option('hefo');

add_action('init', 'hefo_init');
function hefo_init() {
  global $hefo_options;
  
  if (get_option('hefo_version') == null) {
    update_option('hefo_version', HEFO_VERSION);
    // Patch for version 1.3.9
    $hefo_options['og_enabled'] = 1;
    update_option('hefo', $hefo_options);
  }
  
  if (get_option('hefo_version') != HEFO_VERSION) {
    update_option('hefo_version', HEFO_VERSION);
  }
  
}

add_action('wp_head', 'hefo_wp_head_post', 11);

// RackForms - Replace.
function hefo_wp_head_post() {
	
	//wp_enqueue_script('jquery');
	
    global $hefo_options, $wp_query, $wpdb;
    $buffer = '';
    if (is_home ()) $buffer .= hefo_replace($hefo_options['head_home']);

    $rackforms_js_content = <<<EOF

<script type="text/javascript">

/**
 * This is freely distributable code brought to you by nicSoft, makers of RackForms.
 *
 * Code should work on almost all modern browsers, including old versions of IE.
 * 
 * This code will resize an iframe (or any other element) based on window size.
 * Please note that by default RackForms sets absolute (pixle) sizes on elements
 * which means this code will not work without first changing all child elements
 * 'width' property to a percentage.
 *
 * USAGE
 *
 * 1. set_width and set_height control which direction we want to
 *    resize in. By default, we do not resize height. 
 *
 * 2. width_offset and height_offset control the padding.
 */
 
var obj_name = 'rackforms-iframe'; // must match the iframe class name, defaults to rackforms-iframe
var set_width = true; // default true
var set_height = true; // default false

var width_offset = -500; // this should be the column with the iframe sits in.
var height_offset = 20;


// get iframe object based on class name
function getObj(name){	
	object = getElementsByClassName(name);
	
	this.obj = object[0];
    this.style = object[0].style;	
};

// get window size
function getWinSize(){
	var iWidth = 0, iHeight = 0;
	
	if (document.getElementById){
		iWidth = window.innerWidth;
		iHeight = window.innerHeight;
	} else if (document.all){
		iWidth = document.body.offsetWidth;
 		iHeight = document.body.offsetHeight;
	}
	
	return {width:iWidth, height:iHeight};
};

// http://james.padolsey.com/javascript/get-document-height-cross-browser/
function getDocHeight(D) {
    return Math.max(
        Math.max(D.body.scrollHeight, D.documentElement.scrollHeight),
        Math.max(D.body.offsetHeight, D.documentElement.offsetHeight),
        Math.max(D.body.clientHeight, D.documentElement.clientHeight)
    );
}

// resize window logic
function resize_id(obj) {

	var _parentDocHeight = (document.height !== undefined) ? document.height : document.body.offsetHeight;
	var _parentDocWidth = (document.width !== undefined) ? document.width : document.body.offsetWidth;
	
	var oContent = new getObj(obj);
	var oWinSize = getWinSize();
	
	var cw = getElementsByClassName('rackforms-iframe');
	cw = cw[0];
	
	var _docHeight = -1;
	
	if(cw.document !== undefined){ // IE
		
		// http://www.w3schools.com/jsref/prop_frame_contentdocument.asp
		var y = (cw.contentWindow || cw.contentDocument);
		if (y.document)
			y = y.document;
		
		// get height
		if(y.documentElement)
			_docHeight = y.documentElement.scrollHeight;
	
	} else if(cw.contentDocument.documentElement.scrollHeight !== undefined) { // Chrome
	
		if(cw.contentDocument.documentElement.scrollHeight !== undefined)
			_docHeight = cw.contentDocument.documentElement.scrollHeight;
			
	}
	
	var h = oWinSize.height - parseInt(oContent.obj.offsetTop,10);
	var w = oWinSize.width - parseInt(oContent.obj.offsetTop,10);
	
	h = h - height_offset;
	w = w - width_offset;
	
	//if (h > 0 && w > 0) { // doesn't work well with console enabled.
	
		if(set_height && _docHeight > 150) // must be at least 100px to avoid a FF bug where the form doesn't show on load.
			oContent.style.height = _docHeight.toString()+"px";
		
		if(set_width)
			oContent.style.width = w.toString()+"px";
			
	//}

};

/*
	Developed by Robert Nyman, http://www.robertnyman.com
	Code/licensing: http://code.google.com/p/getelementsbyclassname/
*/	
var getElementsByClassName = function (className, tag, elm){
	if (document.getElementsByClassName) {
		getElementsByClassName = function (className, tag, elm) {
			elm = elm || document;
			var elements = elm.getElementsByClassName(className),
				nodeName = (tag)? new RegExp("\\b" + tag + "\\b", "i") : null,
				returnElements = [],
				current;
			for(var i=0, il=elements.length; i<il; i+=1){
				current = elements[i];
				if(!nodeName || nodeName.test(current.nodeName)) {
					returnElements.push(current);
				}
			}
			return returnElements;
		};
	}
	else if (document.evaluate) {
		getElementsByClassName = function (className, tag, elm) {
			tag = tag || "*";
			elm = elm || document;
			var classes = className.split(" "),
				classesToCheck = "",
				xhtmlNamespace = "http://www.w3.org/1999/xhtml",
				namespaceResolver = (document.documentElement.namespaceURI === xhtmlNamespace)? xhtmlNamespace : null,
				returnElements = [],
				elements,
				node;
			for(var j=0, jl=classes.length; j<jl; j+=1){
				classesToCheck += "[contains(concat(' ', @class, ' '), ' " + classes[j] + " ')]";
			}
			try	{
				elements = document.evaluate(".//" + tag + classesToCheck, elm, namespaceResolver, 0, null);
			}
			catch (e) {
				elements = document.evaluate(".//" + tag + classesToCheck, elm, null, 0, null);
			}
			while ((node = elements.iterateNext())) {
				returnElements.push(node);
			}
			return returnElements;
		};
	}
	else {
		getElementsByClassName = function (className, tag, elm) {
			tag = tag || "*";
			elm = elm || document;
			var classes = className.split(" "),
				classesToCheck = [],
				elements = (tag === "*" && elm.all)? elm.all : elm.getElementsByTagName(tag),
				current,
				returnElements = [],
				match;
			for(var k=0, kl=classes.length; k<kl; k+=1){
				classesToCheck.push(new RegExp("(^|\\s)" + classes[k] + "(\\s|$)"));
			}
			for(var l=0, ll=elements.length; l<ll; l+=1){
				current = elements[l];
				match = false;
				for(var m=0, ml=classesToCheck.length; m<ml; m+=1){
					match = classesToCheck[m].test(current.className);
					if (!match) {
						break;
					}
				}
				if (match) {
					returnElements.push(current);
				}
			}
			return returnElements;
		};
	}
	return getElementsByClassName(className, tag, elm);
};


// http://code.google.com/p/domready/
// This way we do not need the entire jQuery libraries.
(function(){

    var DomReady = window.DomReady = {};

	// Everything that has to do with properly supporting our document ready event. Brought over from the most awesome jQuery. 

    var userAgent = navigator.userAgent.toLowerCase();

    // Figure out what browser is being used
    var browser = {
    	version: (userAgent.match( /.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/ ) || [])[1],
    	safari: /webkit/.test(userAgent),
    	opera: /opera/.test(userAgent),
    	msie: (/msie/.test(userAgent)) && (!/opera/.test( userAgent )),
    	mozilla: (/mozilla/.test(userAgent)) && (!/(compatible|webkit)/.test(userAgent))
    };    

	var readyBound = false;	
	var isReady = false;
	var readyList = [];

	// Handle when the DOM is ready
	function domReady() {
		// Make sure that the DOM is not already loaded
		if(!isReady) {
			// Remember that the DOM is ready
			isReady = true;
        
	        if(readyList) {
	            for(var fn = 0; fn < readyList.length; fn++) {
	                readyList[fn].call(window, []);
	            }
            
	            readyList = [];
	        }
		}
	};

	// From Simon Willison. A safe way to fire onload w/o screwing up everyone else.
	function addLoadEvent(func) {
	  var oldonload = window.onload;
	  if (typeof window.onload != 'function') {
	    window.onload = func;
	  } else {
	    window.onload = function() {
	      if (oldonload) {
	        oldonload();
	      }
	      func();
	    }
	  }
	};

	// does the heavy work of working through the browsers idiosyncracies (let's call them that) to hook onload.
	function bindReady() {
		if(readyBound) {
		    return;
	    }
	
		readyBound = true;

		// Mozilla, Opera (see further below for it) and webkit nightlies currently support this event
		if (document.addEventListener && !browser.opera) {
			// Use the handy event callback
			document.addEventListener("DOMContentLoaded", domReady, false);
		}

		// If IE is used and is not in a frame
		// Continually check to see if the document is ready
		if (browser.msie && window == top) (function(){
			if (isReady) return;
			try {
				// If IE is used, use the trick by Diego Perini
				// http://javascript.nwbox.com/IEContentLoaded/
				document.documentElement.doScroll("left");
			} catch(error) {
				setTimeout(arguments.callee, 0);
				return;
			}
			// and execute any waiting functions
		    domReady();
		})();

		if(browser.opera) {
			document.addEventListener( "DOMContentLoaded", function () {
				if (isReady) return;
				for (var i = 0; i < document.styleSheets.length; i++)
					if (document.styleSheets[i].disabled) {
						setTimeout( arguments.callee, 0 );
						return;
					}
				// and execute any waiting functions
	            domReady();
			}, false);
		}

		if(browser.safari) {
		    var numStyles;
			(function(){
				if (isReady) return;
				if (document.readyState != "loaded" && document.readyState != "complete") {
					setTimeout( arguments.callee, 0 );
					return;
				}
				if (numStyles === undefined) {
	                var links = document.getElementsByTagName("link");
	                for (var i=0; i < links.length; i++) {
	                	if(links[i].getAttribute('rel') == 'stylesheet') {
	                	    numStyles++;
	                	}
	                }
	                var styles = document.getElementsByTagName("style");
	                numStyles += styles.length;
				}
				if (document.styleSheets.length != numStyles) {
					setTimeout( arguments.callee, 0 );
					return;
				}
			
				// and execute any waiting functions
				domReady();
			})();
		}

		// A fallback to window.onload, that will always work
	    addLoadEvent(domReady);
	};

	// This is the public function that people can use to hook up ready.
	DomReady.ready = function(fn, args) {
		// Attach the listeners
		bindReady();
    
		// If the DOM is already ready
		if (isReady) {
			// Execute the function immediately
			fn.call(window, []);
	    } else {
			// Add the function to the wait list
	        readyList.push( function() { return fn.call(window, []); } );
	    }
	};
    
	bindReady();
	
})();

// http://www.abeautifulsite.net/blog/2011/11/detecting-mobile-devices-with-javascript/
var isMobile = {
    Android: function() {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function() {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function() {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function() {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function() {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function() {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};


var getLocation = function(href) {
	var l = document.createElement("a");
	l.href = href;
	return l;
};

/**
 * Gets all related CSS rules, though will not pick up on media query blocks.
 */
function getCSSRule(ruleName){
	
	ruleName = ruleName.toLowerCase(); 
	
	foundRules = new Array();
	
	if (document.styleSheets) {
	
		for (var i = 0; i < document.styleSheets.length; i++) {
			
			var styleSheet = document.styleSheets[i]; 
			var ii = 0; 
			var cssRule = false;
			var rulesCount = -1;
			
			// must be same origin for FF or we throw an exception
			var l = getLocation(styleSheet.href);
			if(l.hostname == window.location.hostname){
			
				if (styleSheet.cssRules) {
					rulesCount = styleSheet.cssRules.length;
				} else {
					rulesCount = styleSheet.rules.length;
	            }
			
				for (var j = 0; j < rulesCount; j++) {
				
					if (styleSheet.cssRules) {
						cssRule = styleSheet.cssRules[ii];
					} else {
						cssRule = styleSheet.rules[ii];
		            }
		            
		            if (cssRule)  {
						
		            	// CSSStyleRule
		            	if (cssRule.selectorText && cssRule.selectorText.toLowerCase() == ruleName) {
							foundRules.push(cssRule);
							ii++;
							continue;
						}
						
						// CSSMediaRule
						if (cssRule.cssRules) {

							for(var r = 0; r < cssRule.cssRules.length; r++){
							
								var cssMediaRule = cssRule.cssRules[r];
								
								if (cssMediaRule.selectorText && cssMediaRule.selectorText.toLowerCase() == ruleName) {
									foundRules.push(cssMediaRule);
								}
							
							}
						
			            } else if(cssRule.rules) {
			            
			            	for(var r = 0; r < cssRule.rules.length; r++){
							
								var cssMediaRule = cssRule.rules[r];
								
								if (cssMediaRule.selectorText && cssMediaRule.selectorText.toLowerCase() == ruleName) {
									foundRules.push(cssMediaRule);
								}
							
							}
			            
			            }

			            
						
					}

					ii++;
					
				}

			}
	
		}
		
		return foundRules;
		
	}
}

function isResponsive(){
	
	// Rules for responsive/liquid detection:
	// All post content held in a div called #content.
	// in 2012 we wrap #content with a div called #primary, but this has a class of .site-content, this is what has the %.
	// in 2011 we wrap #content with a div called primary, but this div spans the entire page.
	// in 2011 the #content div has % as a css file declaration.
	// in 2010 #content has no %, and no wrapper that uses % -- it is a non-responsive template.
	
	// 2012
	var r = getCSSRule('.site-content');
	for(var t = 0; t < r.length; t++){
		if(r[t].style.width.indexOf('%') != -1){
			return true;
		}
	}
	
	// 2011
	r = getCSSRule('#content');
	for(var t = 0; t < r.length; t++){
		if(r[t].style.width.indexOf('%') != -1){
			return true;
		}
	}
	
	// other conditions...
	
	return false;
	
}


var rf_isMobile;
var rf_isResponsive;

// handle onload()
DomReady.ready(function() {

	rf_isMobile = isMobile.any();
	rf_isResponsive = isResponsive();

	// only activate mobile version if a mobile device is being used (detected)
	if(rf_isResponsive || rf_isMobile){
	
		// get iframe
		o = getElementsByClassName(obj_name);
	
		iframe = o[0];

		// update the src property of the iframe to be the mobile version
		link = iframe.src.split('/');
		
		job_name = link[link.length - 2];
		
		new_name = iframe.src.replace(job_name, job_name + '-mobile');
		
		iframe.src = new_name;
		
		// resize on page load
 		resize_id(obj_name);
 		
	} else {
	
		// always resize iframe on page load
 		resize_id(obj_name);
	
	}
	
	// always enable the resize event handler
	window.onresize = function() { resize_id(obj_name); };

});
</script>

EOF;
	
    // replace the head content with our custom script
	$buffer .= hefo_replace($rackforms_js_content);

    ob_start();
    eval('?>' . $buffer);
    $buffer = ob_get_contents();
    ob_end_clean();
    echo $buffer;
}

function hefo_replace($buffer) {
    global $hefo_options;
    if (empty($buffer)) return '';
    for ($i=1; $i<=5; $i++) {
        $buffer = str_replace('[snippet_' . $i . ']', $hefo_options['snippet_' . $i], $buffer);
    }
    return $buffer;
}

function hefo_execute($buffer) {
    if (empty($buffer)) return '';
    ob_start();
    eval('?>' . $buffer);
    $buffer = ob_get_contents();
    ob_end_clean();
    return $buffer;
}
?>
