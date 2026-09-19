( function ( $ ) {
	'use strict';

	/**
	 * Decideix si cal sincronitzar amb WPML segons la configuració global,
	 * demanant confirmació a l'usuari quan el mode és "ask".
	 *
	 * @param {Function} cb Rep un booleà (sincronitzar o no).
	 */
	function askSyncIfNeeded( cb ) {
		if ( ! window.FISB || ! FISB.wpmlActive ) {
			cb( false );
			return;
		}

		if ( 'sync' === FISB.syncMode ) {
			cb( true );
		} else if ( 'ask' === FISB.syncMode ) {
			cb( window.confirm( FISB.i18n.confirmSync ) );
		} else {
			cb( false );
		}
	}

	/**
	 * Desa un camp (ALT o Títol) via AJAX i mostra l'estat a l'input.
	 *
	 * @param {jQuery} $input        Input de text.
	 * @param {boolean} syncOverride Sincronitzar amb traduccions.
	 */
	function saveField( $input, syncOverride ) {
		var id    = $input.data( 'id' );
		var field = $input.data( 'field' );
		var value = $input.val();

		$input.prop( 'disabled', true ).addClass( 'fisb-saving' );

		$.post( FISB.ajaxUrl, {
			action: 'fisb_save_field',
			nonce: FISB.nonce,
			id: id,
			field: field,
			value: value,
			sync: syncOverride ? 1 : 0
		} ).done( function ( response ) {
			$input.prop( 'disabled', false ).removeClass( 'fisb-saving' );

			if ( response && response.success ) {
				$input.addClass( 'fisb-saved' );
				setTimeout( function () {
					$input.removeClass( 'fisb-saved' );
				}, 1200 );
			} else {
				window.alert( ( response && response.data && response.data.message ) || FISB.i18n.error );
			}
		} ).fail( function () {
			$input.prop( 'disabled', false ).removeClass( 'fisb-saving' );
			window.alert( FISB.i18n.error );
		} );
	}

	// Desat automàtic en perdre el focus si el valor ha canviat.
	$( document ).on( 'change', '.fisb-field', function () {
		var $input = $( this );
		askSyncIfNeeded( function ( sync ) {
			saveField( $input, sync );
		} );
	} );

	// Generació automàtica per fila (ALT o Títol; nom de fitxer / context).
	$( document ).on( 'click', '.fisb-generate', function ( e ) {
		e.preventDefault();

		var $link  = $( this );
		var id     = $link.data( 'id' );
		var method = $link.data( 'method' );
		var field  = $link.data( 'field' ) || 'alt';
		var $row   = $link.closest( 'tr' );
		var $target = $row.find( '.fisb-field[data-field="' + field + '"]' );

		$link.addClass( 'fisb-saving' );

		$.post( FISB.ajaxUrl, {
			action: 'fisb_generate',
			nonce: FISB.nonce,
			id: id,
			method: method
		} ).done( function ( response ) {
			$link.removeClass( 'fisb-saving' );

			if ( response && response.success ) {
				$target.val( response.data.value );
				askSyncIfNeeded( function ( sync ) {
					saveField( $target, sync );
				} );
			} else {
				window.alert( ( response && response.data && response.data.message ) || FISB.i18n.error );
			}
		} ).fail( function () {
			$link.removeClass( 'fisb-saving' );
			window.alert( FISB.i18n.error );
		} );
	} );

} )( jQuery );
