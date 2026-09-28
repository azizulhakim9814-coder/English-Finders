/**
 * Show / Hide on password fields (English Finders Account 0.14.0), and a
 * live "passwords match" check on Confirm password fields (0.14.3).
 *
 * The Show buttons ship `hidden` and are only revealed here, so without
 * JavaScript there's no dead button.
 */
( function () {
	'use strict';

	var text = window.efaAuthForms || {};

	function init( wrap ) {
		var input = wrap.querySelector( 'input' );
		var button = wrap.querySelector( '.efa-pw-toggle' );
		if ( ! input || ! button ) {
			return;
		}
		button.hidden = false;
		wrap.classList.add( 'has-toggle' );
		button.addEventListener( 'click', function () {
			var showing = 'text' === input.type;
			input.type = showing ? 'password' : 'text';
			button.setAttribute( 'aria-pressed', showing ? 'false' : 'true' );
			button.textContent = showing ? ( text.show || 'Show' ) : ( text.hide || 'Hide' );
			input.focus();
		} );
		// Never submit (or let the browser remember) a password field left in plain-text mode.
		if ( input.form ) {
			input.form.addEventListener( 'submit', function () {
				input.type = 'password';
			} );
		}
	}

	/*
	 * 0.14.3: "Confirm password" (data-efa-match="<id of the first field>").
	 * Says as you type whether the two match, and blocks submitting while they
	 * don't -- the server checks again, this just saves a round trip.
	 */
	function initMatch( confirm ) {
		var first = document.getElementById( confirm.getAttribute( 'data-efa-match' ) );
		var statusId = confirm.getAttribute( 'aria-describedby' );
		var status = statusId ? document.getElementById( statusId ) : null;
		if ( ! first ) {
			return;
		}
		var touched = false;

		function check() {
			var same = first.value === confirm.value;
			confirm.setCustomValidity( '' !== confirm.value && ! same ? ( text.mismatch || "Passwords don't match yet." ) : '' );
			if ( ! status ) {
				return;
			}
			// Quiet until they have typed in the confirm box.
			if ( ! touched || '' === confirm.value ) {
				status.textContent = '';
				status.className = 'efa-pw-match';
				return;
			}
			status.textContent = same ? ( text.match || 'Passwords match.' ) : ( text.mismatch || "Passwords don't match yet." );
			status.className = 'efa-pw-match ' + ( same ? 'is-match' : 'is-mismatch' );
		}

		confirm.addEventListener( 'input', function () {
			touched = true;
			check();
		} );
		first.addEventListener( 'input', check );
	}

	function boot() {
		var fields = document.querySelectorAll( '[data-efa-password]' );
		for ( var i = 0; i < fields.length; i++ ) {
			init( fields[ i ] );
		}
		var confirms = document.querySelectorAll( '[data-efa-match]' );
		for ( var j = 0; j < confirms.length; j++ ) {
			initMatch( confirms[ j ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
