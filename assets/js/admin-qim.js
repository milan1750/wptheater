( function ( $ ) {
	'use strict';

	// Main Object.
	var QimAdmin = {
		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function () {
			// Element actions.
			QimAdmin.buildUIActions();
		},

		/**
		 * Element binds and actions.
		 *
		 * @since 1.0.0
		 */
		buildUIActions: function () {
			$( document ).ready( function () {
				console.log( 'ready' );
			} );
		},
	};

	QimAdmin.init();
} )( jQuery );
