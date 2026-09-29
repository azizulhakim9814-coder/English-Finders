/**
 * "Keep your progress" nudge for visitors who aren't signed in (English
 * Finders Account 0.17.0).
 *
 * English Finders Study's practice tools and Word Games Pro's games send one
 * shared browser event, `ef:progress`, when a visitor gets an answer right or
 * finishes a round. The first time that happens in a visit, a small bar at
 * the bottom of the screen offers a free account (and back to this page).
 *
 *  - Only loaded for visitors who aren't signed in (PHP decides).
 *  - Once per visit (sessionStorage); a dismissal keeps it away for 3 days
 *    (localStorage). Storage being blocked just means it can show again.
 *  - Never steals focus from the game, and announces itself politely.
 */
( function () {
	'use strict';

	var config = window.efaGuestNudge || {};
	var text = config.text || {};
	var SHOWN = 'efa_nudge_shown';
	var DISMISSED = 'efa_nudge_dismissed_at';
	var QUIET_MS = 3 * 24 * 60 * 60 * 1000;

	function read( store, key ) {
		try {
			return window[ store ].getItem( key );
		} catch ( e ) {
			return null;
		}
	}

	function write( store, key, value ) {
		try {
			window[ store ].setItem( key, value );
		} catch ( e ) {}
	}

	function quiet() {
		if ( read( 'sessionStorage', SHOWN ) ) {
			return true;
		}
		var at = parseInt( read( 'localStorage', DISMISSED ) || '0', 10 );
		return at > 0 && Date.now() - at < QUIET_MS;
	}

	function withReturn( base ) {
		var here = window.location.href.split( '#' )[ 0 ];
		return base + ( base.indexOf( '?' ) < 0 ? '?' : '&' ) + ( config.param || 'efa_return' ) + '=' + encodeURIComponent( here );
	}

	function style() {
		if ( document.getElementById( 'efa-nudge-css' ) ) {
			return;
		}
		var css = document.createElement( 'style' );
		css.id = 'efa-nudge-css';
		css.textContent =
			'.efa-nudge{position:fixed;left:50%;bottom:16px;z-index:99990;box-sizing:border-box;width:calc(100% - 24px);max-width:560px;transform:translate(-50%,0);display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;background:#0e2a4a;color:#fff;font-family:Lexend,sans-serif;line-height:1.35;box-shadow:0 10px 30px rgba(14,42,74,.35);animation:efa-nudge-in .35s ease-out}' +
			'.efa-nudge *{box-sizing:border-box}' +
			'.efa-nudge__text{flex:1;min-width:0;margin:0;font-size:14px}' +
			'.efa-nudge__text strong{display:block;margin-bottom:2px;font-size:15px;font-weight:600;color:#fff}' +
			'.efa-nudge__actions{order:2;display:flex;align-items:center;gap:12px;flex:none}' +
			'.efa-nudge a.efa-nudge__signup{display:inline-flex;align-items:center;height:38px;padding:0 18px;border-radius:25px;background:#fff;color:#075aae;font-size:14px;font-weight:600;text-decoration:none;white-space:nowrap}' +
			'.efa-nudge a.efa-nudge__signup:hover{background:#e8f1fb;color:#075aae}' +
			'.efa-nudge a.efa-nudge__login{color:#cfe2f7;font-size:14px;text-decoration:underline;white-space:nowrap}' +
			'.efa-nudge a.efa-nudge__login:hover{color:#fff}' +
			'.efa-nudge button.efa-nudge__close{flex:none;display:inline-grid;place-items:center;width:30px;height:30px;margin:-4px -6px 0 0;padding:0;border:0;border-radius:50%;background:transparent;color:#cfe2f7;font-size:20px;line-height:1;cursor:pointer;align-self:flex-start;order:3}' +
			'.efa-nudge button.efa-nudge__close:hover{background:rgba(255,255,255,.12);color:#fff}' +
			'.efa-nudge a:focus-visible,.efa-nudge button:focus-visible{outline:2px solid #fff;outline-offset:2px}' +
			'@keyframes efa-nudge-in{from{opacity:0;transform:translate(-50%,16px)}to{opacity:1;transform:translate(-50%,0)}}' +
			'@media (prefers-reduced-motion:reduce){.efa-nudge{animation:none}}' +
			'@media (max-width:560px){.efa-nudge{flex-wrap:wrap;bottom:max(12px,env(safe-area-inset-bottom));gap:10px 12px}.efa-nudge__text{flex-basis:calc(100% - 40px)}.efa-nudge button.efa-nudge__close{order:2}.efa-nudge__actions{order:3;width:100%}}';
		document.head.appendChild( css );
	}

	function show() {
		if ( quiet() || document.querySelector( '.efa-nudge' ) || ! config.signup ) {
			return;
		}
		write( 'sessionStorage', SHOWN, '1' );
		style();

		var bar = document.createElement( 'div' );
		bar.className = 'efa-nudge';
		bar.setAttribute( 'role', 'status' );
		bar.setAttribute( 'aria-live', 'polite' );

		var p = document.createElement( 'p' );
		p.className = 'efa-nudge__text';
		var strong = document.createElement( 'strong' );
		strong.textContent = text.title || 'Nice work!';
		p.appendChild( strong );
		p.appendChild( document.createTextNode( text.body || 'Create a free account to save your XP, keep a daily streak and review what you get wrong.' ) );

		var actions = document.createElement( 'div' );
		actions.className = 'efa-nudge__actions';
		var signup = document.createElement( 'a' );
		signup.className = 'efa-nudge__signup';
		signup.href = withReturn( config.signup );
		signup.textContent = text.signup || 'Sign up free';
		actions.appendChild( signup );
		if ( config.login ) {
			var login = document.createElement( 'a' );
			login.className = 'efa-nudge__login';
			login.href = withReturn( config.login );
			login.textContent = text.login || 'Log in';
			actions.appendChild( login );
		}

		var close = document.createElement( 'button' );
		close.type = 'button';
		close.className = 'efa-nudge__close';
		close.setAttribute( 'aria-label', text.close || 'Dismiss' );
		close.textContent = '×';
		close.addEventListener( 'click', function () {
			write( 'localStorage', DISMISSED, String( Date.now() ) );
			bar.parentNode && bar.parentNode.removeChild( bar );
		} );

		bar.appendChild( p );
		bar.appendChild( close );
		bar.appendChild( actions );
		document.body.appendChild( bar );
	}

	document.addEventListener( 'ef:progress', show );
}() );
