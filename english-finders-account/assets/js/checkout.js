/**
 * Opens Paddle's checkout overlay for an "Upgrade" button.
 *
 * Deliberately minimal: no build step, no framework -- this only needs to
 * initialise Paddle.js once and wire up click handlers. `efaCheckout` is
 * localized by MembershipController::maybe_enqueue_checkout() and is only
 * ever enqueued alongside this script, so it is expected to exist -- the
 * guards below are for the CDN script itself failing to load (network
 * block, ad blocker), not for missing config.
 *
 * @package EnglishFindersAccount
 */
( function () {
	'use strict';

	if ( typeof window.Paddle === 'undefined' || typeof window.efaCheckout === 'undefined' ) {
		return;
	}

	if ( 'sandbox' === window.efaCheckout.environment ) {
		window.Paddle.Environment.set( 'sandbox' );
	}

	window.Paddle.Initialize( { token: window.efaCheckout.clientSideToken } );

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-efa-price-id]' );
		if ( ! button ) {
			return;
		}

		event.preventDefault();

		var options = {
			items: [ { priceId: button.getAttribute( 'data-efa-price-id' ), quantity: 1 } ],
			customData: { user_id: window.efaCheckout.userId },
		};
		// 0.18.0: the member's email is filled in, and a paid checkout lands on My Account with a welcome note.
		if ( window.efaCheckout.email ) {
			options.customer = { email: window.efaCheckout.email };
		}
		if ( window.efaCheckout.successUrl ) {
			options.settings = { successUrl: window.efaCheckout.successUrl };
		}

		window.Paddle.Checkout.open( options );
	} );
} )();
