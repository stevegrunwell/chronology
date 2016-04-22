/**
 * Scripting for the WP-Admin area.
 *
 * @package Chronology
 */
( function ( $ ) {
	'use strict';

	var metabox = $( document.getElementById( 'chronology' ) ),
		templateRow = document.getElementById( 'chronology-row-_template' ),

		/**
		 * Add a new row to the end of the list.
		 */
		addRow = function ( e ) {
			var id = generateId(),
				row = templateRow.outerHTML.replace( /_template/g, id );

			e.preventDefault();

			// Add this to the end of the list (i.e. just before _template).
			templateRow.insertAdjacentHTML( 'beforebegin', row );

			// Focus on the new input.
			document.getElementById( 'chronology-date-' + id ).focus();
		},

		/**
		 * Generate a unique ID to assign to newly-created rows.
		 */
		generateId = function () {
			return Math.floor( Math.random() * 100000 );
		};

	// Wire up event listeners.
	metabox.on( 'click', '.chronology-add-event', addRow );

} ( jQuery, undefined ) );
