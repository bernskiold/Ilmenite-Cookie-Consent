/**
 * Script blocker.
 *
 * Loaded in the <head>, before any other script, to stop trackers from
 * running until the visitor has consented to their category.
 *
 * Three ways a tracker can be held back:
 *
 * 1. Script tags in the page whose src matches a category's domains are
 *    caught by a MutationObserver as the parser adds them, and are taken
 *    out of the page before they run.
 * 2. Scripts that other scripts create with document.createElement are
 *    caught when their src is set, and kept with an inert type.
 * 3. Opt-in markup, which the browser never runs or loads by itself:
 *      <script type="text/plain" data-ilcc-category="analytics" src="…"></script>
 *      <script type="text/plain" data-ilcc-category="marketing">…</script>
 *      <iframe data-ilcc-category="marketing" data-src="…"></iframe>
 *      <img data-ilcc-category="marketing" data-src="…">
 *    The plugin also rewrites tracker tags into this markup on the server
 *    (classes/class-blocker.php), because browsers start downloading
 *    <script src> and similar URLs from the HTML before any script can
 *    run, and those requests carry and set third-party cookies. (1) is the
 *    fallback for tags printed outside that rewrite.
 *
 * When consent is given, everything held back for that category is
 * restored, in the order it was blocked.
 *
 * Configuration comes from window.ilccBlockerConfig, printed by PHP just
 * before this file: the domain lists per category and the cookie names.
 * The decision is made here, in the browser, so pages stay cache-safe.
 */

const BLOCKED_TYPE = "javascript/blocked";
const CATEGORIES = [ "analytics", "marketing" ];

const config = window.ilccBlockerConfig || {};
const trackers = config.trackers || {};

/**
 * Read a cookie value.
 *
 * @param {string} name
 * @return {string|null} Value.
 */
function readCookie( name ) {
	const parts = document.cookie ? document.cookie.split( ";" ) : [];

	for ( let i = 0; i < parts.length; i++ ) {
		const part = parts[ i ].replace( /^\s+/, "" );

		if ( part.indexOf( name + "=" ) === 0 ) {
			const value = part.substring( name.length + 1 );

			try {
				return decodeURIComponent( value );
			} catch ( e ) {
				return value;
			}
		}
	}

	return null;
}

/**
 * The categories the visitor has already consented to.
 *
 * @return {Array} Category names.
 */
function storedConsent() {
	if ( readCookie( config.preferencesCookieName ) !== String( config.consentVersion || "1" ) ) {
		return [];
	}

	try {
		const categories = JSON.parse( readCookie( config.consentedCategoriesCookieName ) );
		return Array.isArray( categories ) ? categories : [];
	} catch ( e ) {
		return [];
	}
}

/**
 * Turn domains into patterns that match them literally anywhere in a URL.
 *
 * @param {Array} domains
 * @return {Array<RegExp>} Patterns.
 */
function toPatterns( domains ) {
	return ( domains || [] ).map( ( domain ) => new RegExp( String( domain ).replace( /[.*+?^${}()|[\]\\/]/g, "\\$&" ) ) );
}

const patterns = {
	necessary: toPatterns( trackers.necessary ),
	analytics: toPatterns( trackers.analytics ),
	marketing: toPatterns( trackers.marketing ),
};

const allowed = new Set( [ "necessary", ...storedConsent() ] );

/**
 * Scripts taken out of the page, waiting for consent.
 *
 * @type {Array<{element: HTMLScriptElement, type: string|null}>}
 */
const held = [];

/**
 * Which not-yet-consented category a script URL belongs to.
 *
 * Domains listed as necessary are never blocked.
 *
 * @param {string} url
 * @return {string|null} Category, or null if the URL may load.
 */
function blockedCategory( url ) {
	if ( ! url ) {
		return null;
	}

	if ( patterns.necessary.some( ( pattern ) => pattern.test( url ) ) ) {
		return null;
	}

	for ( const category of CATEGORIES ) {
		if ( ! allowed.has( category ) && patterns[ category ].some( ( pattern ) => pattern.test( url ) ) ) {
			return category;
		}
	}

	return null;
}

/**
 * Hold back a script the parser just added to the page.
 *
 * @param {HTMLScriptElement} script
 */
function holdScript( script ) {
	held.push( { element: script, type: script.getAttribute( "type" ) } );

	script.type = BLOCKED_TYPE;

	// Firefox runs some scripts despite the type change; stop it there.
	const preventExecution = ( event ) => {
		if ( script.getAttribute( "type" ) === BLOCKED_TYPE ) {
			event.preventDefault();
		}
		script.removeEventListener( "beforescriptexecute", preventExecution );
	};
	script.addEventListener( "beforescriptexecute", preventExecution );

	if ( script.parentElement ) {
		script.parentElement.removeChild( script );
	}
}

/**
 * Make an opt-in element live.
 *
 * Opt-in elements are written by hand, or by the plugin, which rewrites
 * tracker tags in the page before they reach the browser (ILCC_Blocker).
 *
 * @param {HTMLElement} element
 */
function activateOptIn( element ) {
	if ( "SCRIPT" !== element.tagName ) {
		// iframe and img: data-src(set) → src(set). link: data-href → href.
		for ( const name of [ "srcset", "src", "href" ] ) {
			const value = element.getAttribute( `data-${ name }` );

			if ( null !== value ) {
				element.removeAttribute( `data-${ name }` );
				element.setAttribute( name, value );
			}
		}

		element.removeAttribute( "data-ilcc-category" );
		return;
	}

	const script = createElement.call( document, "script" );

	for ( const attribute of Array.from( element.attributes ) ) {
		if ( ! [ "type", "data-ilcc-category", "data-ilcc-type" ].includes( attribute.name ) ) {
			script.setAttribute( attribute.name, attribute.value );
		}
	}

	const type = element.getAttribute( "data-ilcc-type" );

	if ( type ) {
		script.setAttribute( "type", type );
	}

	if ( element.hasAttribute( "src" ) ) {
		// Keep the page's order unless the original tag asked for async.
		script.async = element.hasAttribute( "async" );
	} else {
		script.text = element.text;
	}

	element.replaceWith( script );
}

const OPT_IN_SELECTOR = "script[data-ilcc-category], iframe[data-ilcc-category], img[data-ilcc-category], link[data-ilcc-category]";

function isWaitingOptIn( element ) {
	if ( ! element.matches || ! element.matches( OPT_IN_SELECTOR ) || ! allowed.has( element.getAttribute( "data-ilcc-category" ) ) ) {
		return false;
	}

	if ( "SCRIPT" === element.tagName ) {
		return "text/plain" === element.getAttribute( "type" );
	}

	return element.hasAttribute( "data-src" ) || element.hasAttribute( "data-srcset" ) || element.hasAttribute( "data-href" );
}

function activateWaitingOptIns() {
	document.querySelectorAll( OPT_IN_SELECTOR ).forEach( ( element ) => {
		if ( isWaitingOptIn( element ) ) {
			activateOptIn( element );
		}
	} );
}

/**
 * Watch what the parser and other scripts add to the page.
 */
const observer = new MutationObserver( ( mutations ) => {
	for ( const mutation of mutations ) {
		for ( const node of mutation.addedNodes ) {
			if ( 1 !== node.nodeType ) {
				continue;
			}

			if ( "SCRIPT" === node.tagName && node.src && node.getAttribute( "type" ) !== BLOCKED_TYPE && blockedCategory( node.src ) ) {
				holdScript( node );
			} else if ( isWaitingOptIn( node ) ) {
				activateOptIn( node );
			}
		}
	}
} );

observer.observe( document.documentElement, { childList: true, subtree: true } );

/**
 * Scripts created by other scripts: give them an inert type as soon as a
 * blocked src is set, so the browser never loads them.
 */
const createElement = document.createElement;
const srcProperty = Object.getOwnPropertyDescriptor( HTMLScriptElement.prototype, "src" );
const typeProperty = Object.getOwnPropertyDescriptor( HTMLScriptElement.prototype, "type" );

document.createElement = function( ...args ) {
	const element = createElement.apply( document, args );

	if ( "script" !== String( args[ 0 ] ).toLowerCase() ) {
		return element;
	}

	try {
		Object.defineProperties( element, {
			src: {
				...srcProperty,
				set( value ) {
					if ( blockedCategory( String( value ) ) ) {
						element.dataset.ilccType = typeProperty.get.call( this ) || "";
						typeProperty.set.call( this, BLOCKED_TYPE );
					}
					srcProperty.set.call( this, value );
				},
			},
			type: {
				...typeProperty,
				set( value ) {
					if ( typeProperty.get.call( this ) === BLOCKED_TYPE && blockedCategory( this.src ) ) {
						element.dataset.ilccType = value;
						return;
					}
					typeProperty.set.call( this, value );
				},
			},
		} );

		const setAttribute = element.setAttribute;
		element.setAttribute = function( name, value ) {
			if ( "src" === name || "type" === name ) {
				element[ name ] = value;
			} else {
				setAttribute.call( element, name, value );
			}
		};
	} catch ( e ) {
		// Another script already patched script elements; the observer still applies.
	}

	return element;
};

/**
 * Re-create a held-back script so the browser loads and runs it.
 *
 * @param {HTMLScriptElement} script
 * @param {string|null}       type   The script's original type.
 */
function restoreScript( script, type ) {
	const restored = createElement.call( document, "script" );

	for ( const attribute of Array.from( script.attributes ) ) {
		if ( ! [ "src", "type", "data-ilcc-type" ].includes( attribute.name ) ) {
			restored.setAttribute( attribute.name, attribute.value );
		}
	}

	if ( type ) {
		typeProperty.set.call( restored, type );
	}

	// Scripts added from JavaScript run as soon as they load, in any order.
	// Keep the page's order unless the original tag asked for async.
	restored.async = script.hasAttribute( "async" );

	srcProperty.set.call( restored, script.src );

	if ( script.parentElement ) {
		script.replaceWith( restored );
	} else {
		document.head.appendChild( restored );
	}
}

/**
 * Let the scripts of these categories run, now and from here on.
 *
 * @param {Array} categories Category names. Omit to unblock everything.
 */
function unblock( categories = CATEGORIES ) {
	categories.forEach( ( category ) => allowed.add( category ) );

	// Scripts taken out of the page by the observer, in their original order.
	for ( let i = 0; i < held.length; i++ ) {
		const { element, type } = held[ i ];

		if ( ! blockedCategory( element.src ) ) {
			held.splice( i--, 1 );
			restoreScript( element, type );
		}
	}

	// Scripts created by other scripts, still in the page with an inert type.
	document.querySelectorAll( `script[type="${ BLOCKED_TYPE }"]` ).forEach( ( script ) => {
		if ( ! blockedCategory( script.src ) ) {
			restoreScript( script, script.dataset.ilccType || null );
		}
	} );

	activateWaitingOptIns();
}

if ( "loading" === document.readyState ) {
	document.addEventListener( "DOMContentLoaded", activateWaitingOptIns );
} else {
	activateWaitingOptIns();
}

window.ilccBlocker = {
	patterns,
	get consented() {
		return Array.from( allowed ).filter( ( category ) => "necessary" !== category );
	},
	isBlocked: ( url ) => null !== blockedCategory( url ),
	unblock,
};

/**
 * Earlier versions exposed window.yett.unblock(). Keep it working for
 * custom code that calls it: it unblocks everything.
 *
 * @deprecated 3.4.0 Use window.ilccBlocker.unblock( categories ).
 */
window.yett = {
	unblock: () => unblock(),
};
