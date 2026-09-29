/**
 * Profile photo picker (English Finders Account 0.13.1).
 *
 * On choosing a photo: shows it in the avatar circle straight away, then
 * centre-crops and shrinks it in the browser to at most 512x512 (JPG stays
 * JPG, PNG stays PNG) and swaps the smaller file into the form. A 5 MB phone
 * photo becomes ~50-150 KB, so saving is quick and never hits the upload
 * limit. The server still checks and re-encodes whatever arrives.
 *
 * Everything here is an enhancement: if the browser can't decode the image
 * or can't replace an input's files (no DataTransfer), the original file is
 * submitted unchanged and the server handles it as before.
 */
( function () {
	'use strict';

	var MAX_SIDE = 512;
	var TYPES = [ 'image/jpeg', 'image/png' ];
	var cfg = window.efaAvatarPicker || {};
	var text = cfg.i18n || {};
	var MAX_BYTES = Number( cfg.maxBytes ) || 2097152; // Same limits as the server (Profile\Avatar).
	var MIN_SIDE = Number( cfg.minSide ) || 64;

	function isAllowed( file ) {
		if ( TYPES.indexOf( file.type ) !== -1 ) {
			return true;
		}
		// Some browsers leave the type empty; fall back to the extension.
		return ! file.type && /\.(jpe?g|png)$/i.test( file.name );
	}

	function loadImage( file ) {
		return new Promise( function ( resolve, reject ) {
			var url = URL.createObjectURL( file );
			var img = new Image();
			img.onload = function () {
				resolve( { img: img, url: url } );
			};
			img.onerror = function () {
				URL.revokeObjectURL( url );
				reject( new Error( 'decode' ) );
			};
			img.src = url;
		} );
	}

	/** Centre square crop, at most MAX_SIDE px, same format as the original. */
	function shrink( img, type ) {
		return new Promise( function ( resolve ) {
			var w = img.naturalWidth;
			var h = img.naturalHeight;
			var side = Math.min( w, h );
			var out = Math.min( MAX_SIDE, side );
			var canvas = document.createElement( 'canvas' );
			canvas.width = out;
			canvas.height = out;
			var ctx = canvas.getContext( '2d' );
			if ( ! ctx || ! canvas.toBlob ) {
				resolve( null );
				return;
			}
			if ( 'image/jpeg' === type ) {
				// JPG has no transparency: paint white first so nothing turns black.
				ctx.fillStyle = '#fff';
				ctx.fillRect( 0, 0, out, out );
			}
			ctx.imageSmoothingQuality = 'high';
			ctx.drawImage( img, ( w - side ) / 2, ( h - side ) / 2, side, side, 0, 0, out, out );
			canvas.toBlob( resolve, type, 0.88 );
		} );
	}

	function showPreview( circle, url ) {
		if ( ! circle ) {
			return;
		}
		var img = circle.tagName === 'IMG' ? circle : circle.querySelector( 'img' );
		if ( ! img ) {
			img = document.createElement( 'img' );
			img.alt = '';
			circle.textContent = '';
			circle.appendChild( img );
		}
		img.src = url;
		circle.classList.add( 'has-photo' );
	}

	function setStatus( el, message, isError ) {
		if ( ! el ) {
			return;
		}
		el.textContent = message || '';
		el.classList.toggle( 'is-error', !! isError );
	}

	function init( picker ) {
		var input = picker.querySelector( 'input[type="file"]' );
		var circle = picker.querySelector( '[data-efa-photo-preview]' );
		var status = picker.querySelector( '[data-efa-photo-status]' );
		var remove = picker.querySelector( 'input[name="remove_avatar"]' );
		if ( ! input ) {
			return;
		}

		// What the circle showed before any pick, so a refused file can't leave a misleading preview.
		var original = circle ? { html: circle.innerHTML, photo: circle.classList.contains( 'has-photo' ) } : null;
		function restoreCircle() {
			if ( circle && original ) {
				circle.innerHTML = original.html;
				circle.classList.toggle( 'has-photo', original.photo );
			}
		}

		input.addEventListener( 'change', function () {
			var file = input.files && input.files[ 0 ];
			if ( ! file ) {
				restoreCircle();
				setStatus( status, '', false );
				return;
			}
			if ( ! isAllowed( file ) ) {
				input.value = '';
				restoreCircle();
				setStatus( status, text.wrongType || 'Please choose a JPG or PNG image.', true );
				return;
			}
			if ( remove ) {
				remove.checked = false;
			}
			setStatus( status, text.preparing || 'Preparing your photo…', false );

			// 0.13.2: refuse here, before uploading, anything the server would refuse.
			function refuse( message ) {
				input.value = '';
				restoreCircle();
				setStatus( status, message, true );
			}

			loadImage( file ).then( function ( loaded ) {
				if ( Math.min( loaded.img.naturalWidth, loaded.img.naturalHeight ) < MIN_SIDE ) {
					refuse( text.tooSmall || 'This photo is too small. Please use one at least 64 pixels wide and tall.' );
					return;
				}
				showPreview( circle, loaded.url );
				var type = 'image/png' === file.type || /\.png$/i.test( file.name ) ? 'image/png' : 'image/jpeg';
				return shrink( loaded.img, type ).then( function ( blob ) {
					if ( blob && blob.size < file.size && window.DataTransfer ) {
						try {
							var name = file.name.replace( /\.[^.]+$/, '' ) + ( 'image/png' === type ? '.png' : '.jpg' );
							var dt = new DataTransfer();
							dt.items.add( new File( [ blob ], name, { type: type } ) );
							input.files = dt.files;
						} catch ( e ) {
							// Keep the original file.
						}
					}
					var sending = input.files && input.files[ 0 ];
					if ( sending && sending.size > MAX_BYTES ) {
						refuse( text.tooBig || 'This photo is larger than 2 MB. Please choose a smaller one.' );
						return;
					}
					setStatus( status, text.ready || 'Looks good. It is saved when you submit the form.', false );
				} );
			} ).catch( function () {
				// The browser couldn't read it (e.g. an unusual JPG variant): send it as it is, if the server would take its size.
				if ( file.size > MAX_BYTES ) {
					refuse( text.tooBig || 'This photo is larger than 2 MB. Please choose a smaller one.' );
					return;
				}
				setStatus( status, text.unreadable || 'Photo selected. It is checked when you submit the form.', false );
			} );
		} );

		if ( input.form ) {
			input.form.addEventListener( 'submit', function () {
				if ( ! input.files || ! input.files.length ) {
					return;
				}
				var button = input.form.querySelector( 'button[type="submit"]:not([formnovalidate])' );
				if ( button ) {
					button.dataset.efaLabel = button.textContent;
					button.disabled = true;
					button.textContent = text.saving || 'Saving…';
				}
			} );
			// Coming back with the browser's Back button can restore the disabled state.
			window.addEventListener( 'pageshow', function () {
				var button = input.form.querySelector( 'button[type="submit"][data-efa-label]' );
				if ( button ) {
					button.disabled = false;
					button.textContent = button.dataset.efaLabel;
				}
			} );
		}
	}

	function boot() {
		var pickers = document.querySelectorAll( '[data-efa-photo-picker]' );
		for ( var i = 0; i < pickers.length; i++ ) {
			init( pickers[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
