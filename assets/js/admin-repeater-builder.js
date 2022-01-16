/* global wpf, wpforms_builder, wpforms_repeater_builder */
( function ( $ ) {
	'use strict';

	// Global settings access.
	var s, $builder;

	// Main Survey admin builder object.
	var WPFormsRepeaterBuilder = {
		// Settings.
		settings: {},

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function () {
			// Settings shortcut.
			s = this.settings;

			$builder = $( '#wpforms-builder' );

			s.formID = $( '#wpforms-builder-form' ).data( 'id' );

			// Element actions.
			WPFormsRepeaterBuilder.buildUIActions();

			$( document ).on(
				'click',
				'.toggle-smart-tag-display',
				function () {
					$( document )
						.find( '.wpforms-repeater-buttons' )
						.each( function () {
							WPFormsRepeaterBuilder.toggleRepeaterFieldSmartTag(
								$( this ).parent().attr( 'data-field-id' )
							);
						} );
					$( document )
						.find(
							'.wpforms-field input, .wpforms-field select, .wpforms-field textarea'
						)
						.each( function () {
							WPFormsRepeaterBuilder.toggleRepeaterFieldSmartTag(
								$( this )
									.closest( '.wpforms-field' )
									.attr( 'data-field-id' )
							);
						} );
				}
			);
		},

		/**
		 * Element binds and actions.
		 *
		 * @since 1.0.0
		 */
		buildUIActions: function () {
			// Add Repeater Field.
			$( document ).on( 'wpformsFieldAdd', function ( event, id, type ) {
				if ( 'repeater' === type ) {
					WPFormsRepeaterBuilder.fieldRepeaterAdd( event, id, type );
				}
			} );

			// Remove Repeater Field.
			$( document ).on( 'wpformsFieldDelete', function (
				event,
				id,
				type
			) {
				if ( 'repeater' === type ) {
					WPFormsRepeaterBuilder.fieldRepeaterDelete(
						event,
						id,
						type
					);
				}
			} );

			// Move Repeater Field.
			$( document ).on( 'wpformsFieldMove', function ( event, ui ) {
				if ( $( ui.item ).attr( 'data-field-type' ) === 'repeater' ) {
					WPFormsRepeaterBuilder.checkValidRepeaterSort( ui.item );
				}
				WPFormsRepeaterBuilder.toggleRepeaterFieldSmartTag(
					ui.item.attr( 'data-field-id' )
				);
			} );

			// Move Page Break.
			$( document ).on( 'wpformsFieldMove', function ( event, ui ) {
				if ( $( ui.item ).attr( 'data-field-type' ) === 'pagebreak' ) {
					WPFormsRepeaterBuilder.checkValidPagebreakSort( ui.item );
				}
			} );

			// Add Page Break.
			$( document ).on( 'wpformsFieldAdd', function ( event, id, type ) {
				if ( 'pagebreak' === type ) {
					WPFormsRepeaterBuilder.validatePageBreakAdd( event, id );
				}
			} );

			// Change Label Text.
			$( document ).on(
				'keyup',
				'.wpforms-field-option-row-label input',
				function () {
					$( document )
						.find(
							'.wpforms-field-repeater[data-field-id="' +
								$( this ).parent().attr( 'data-field-id' ) +
								'"] span.wpforms-repeater-label'
						)
						.html( $( this ).val() );
				}
			);

			// Change Align Select.
			$( document ).on(
				'change',
				'.wpforms-field-option-row-repeat_label_align select',
				function () {
					$( document )
						.find(
							'.wpforms-field-repeater[data-field-id="' +
								$( this ).parent().attr( 'data-field-id' ) +
								'"] div.wpforms-repeater-divider'
						)
						.css( 'text-align', $( this ).val() );
				}
			);

			// Hide Repeater Label.
			$( document ).on(
				'click',
				'.wpforms-field-option-row-label_hide input',
				function () {
					if ( $( this ).prop( 'checked' ) ) {
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this )
										.parents( '.wpforms-field-option-row' )
										.attr( 'data-field-id' ) +
									'"] .wpforms-repeater-label'
							)
							.css( 'display', 'none' );
					} else {
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this )
										.parents( '.wpforms-field-option-row' )
										.attr( 'data-field-id' ) +
									'"] .wpforms-repeater-label'
							)
							.css( 'display', 'block' );
					}
				}
			);

			// Change Button Style Select.
			$( document ).on(
				'change',
				'.wpforms-field-option-row-repeater_button_style select',
				function () {
					var style = $( this ).val();
					if ( style === 'text' ) {
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this ).parent().attr( 'data-field-id' ) +
									'"] div.wpforms-repeater-buttons span.text'
							)
							.css( 'display', 'inline' );
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this ).parent().attr( 'data-field-id' ) +
									'"] div.wpforms-repeater-buttons span.icon'
							)
							.css( 'display', 'none' );
						$( this )
							.closest( '.wpforms-field-option-row' )
							.next()
							.css( 'display', 'inline' )
							.next()
							.css( 'display', 'inline' );
					} else if ( style === 'icon' ) {
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this ).parent().attr( 'data-field-id' ) +
									'"] div.wpforms-repeater-buttons span.text'
							)
							.css( 'display', 'none' );
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this ).parent().attr( 'data-field-id' ) +
									'"] div.wpforms-repeater-buttons span.icon'
							)
							.css( 'display', 'inline' );
						$( this )
							.closest( '.wpforms-field-option-row' )
							.next()
							.css( 'display', 'none' )
							.next()
							.css( 'display', 'none' );
					} else {
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this ).parent().attr( 'data-field-id' ) +
									'"] div.wpforms-repeater-buttons span.text'
							)
							.css( 'display', 'inline' );
						$( document )
							.find(
								'.wpforms-field-repeater[data-field-id="' +
									$( this ).parent().attr( 'data-field-id' ) +
									'"] div.wpforms-repeater-buttons span.icon'
							)
							.css( 'display', 'inline' );
						$( this )
							.closest( '.wpforms-field-option-row' )
							.next()
							.css( 'display', 'inline' )
							.next()
							.css( 'display', 'inline' );
					}
				}
			);

			// Show/Hide Button Label.
			$( document )
				.find(
					'.wpforms-field-option-row-repeater_button_style select'
				)
				.each( function () {
					var style = $( this ).val();
					if ( style === 'text' ) {
						$( this )
							.closest( '.wpforms-field-option-row' )
							.next()
							.css( 'display', 'block' )
							.next()
							.css( 'display', 'block' );
					} else if ( style === 'icon' ) {
						$( this )
							.closest( '.wpforms-field-option-row' )
							.next()
							.css( 'display', 'none' )
							.next()
							.css( 'display', 'none' );
					} else {
						$( this )
							.closest( '.wpforms-field-option-row' )
							.next()
							.css( 'display', 'block' )
							.next()
							.css( 'display', 'block' );
					}
				} );

			// Change Button Align Select.
			$( document ).on(
				'change',
				'.wpforms-field-option-row-repeater_button_align select',
				function () {
					$( document )
						.find(
							'.wpforms-field-repeater[data-field-id="' +
								$( this ).parent().attr( 'data-field-id' ) +
								'"] div.wpforms-repeater-buttons'
						)
						.css( 'text-align', $( this ).val() );
				}
			);

			// Change Add Button Label Text.
			$( document ).on(
				'keyup',
				'.wpforms-field-option-row-add_button_label input',
				function () {
					$( document )
						.find(
							'.wpforms-field-repeater[data-field-id="' +
								$( this ).parent().attr( 'data-field-id' ) +
								'"] .wpforms-repeater-add span.text'
						)
						.html( $( this ).val() );
				}
			);

			// Change Remove Button Label Text.
			$( document ).on(
				'keyup',
				'.wpforms-field-option-row-remove_button_label input',
				function () {
					$( document )
						.find(
							'.wpforms-field-repeater[data-field-id="' +
								$( this ).parent().attr( 'data-field-id' ) +
								'"] .wpforms-repeater-remove span.text'
						)
						.html( $( this ).val() );
				}
			);
		},

		/**
		 * Check valid repeater sort.
		 *
		 * @since 1.0.0
		 * @param {*} item
		 */
		checkValidRepeaterSort: function ( item ) {
			// Check Repeater Nested.
			var cancelled = false;
			if (
				$( item )
					.nextAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-buttons' ).length > 0 &&
				$( item )
					.prevAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-divider' ).length > 0
			) {
				cancelled = true;
				WPFormsRepeaterBuilder.revertOptionSort( item );
			}

			// Check Repeater Nested.
			if (
				$( item ).has( '.wpforms-repeater-divider' ).length > 0 &&
				$( item )
					.nextAll(
						'.wpforms-field-repeater,.wpforms-field-pagebreak'
					)
					.first()
					.has( '.wpforms-repeater-buttons' ).length === 0
			) {
				cancelled = true;
				WPFormsRepeaterBuilder.revertOptionSort( item );
			}

			// Check Repetaer Nested.
			if (
				$( item ).has( '.wpforms-repeater-buttons' ).length > 0 &&
				$( item )
					.prevAll(
						'.wpforms-field-repeater,.wpforms-field-pagebreak'
					)
					.first()
					.has( '.wpforms-repeater-divider' ).length === 0
			) {
				cancelled = true;
				WPFormsRepeaterBuilder.revertOptionSort( item );
			}

			if ( cancelled ) {
				$.confirm( {
					title: wpforms_builder.heads_up,
					content: wpforms_repeater_builder.invalid_sort_msg,
					icon: 'fa fa-exclamation-circle',
					type: 'red',
					buttons: {
						cancel: {
							text: wpforms_builder.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
			}
		},

		/**
		 * Revert Option Sort.
		 *
		 * @param {*} item
		 * @param {*} newIndex
		 */
		revertOptionSort: function ( item, newIndex ) {
			var fieldOptions = $( '.wpforms-field-options' );
			newIndex = item.index();
			$( '.wpforms-field-wrap' ).sortable( 'cancel' );
			var oldIndex = item.index();
			var field = fieldOptions[ 0 ].children[ oldIndex ];
			var fieldNew = fieldOptions[ 0 ].children[ newIndex ];
			if ( oldIndex < newIndex ) {
				$( field ).before( fieldNew );
			} else {
				$( field ).after( fieldNew );
			}
		},

		/**
		 * Check Valid Pagebreak Sort.
		 *
		 * @since 1.0.0
		 * @param {*} item
		 */
		checkValidPagebreakSort: function ( item ) {
			// Check Pagebreak inside Repeater.
			if (
				$( item )
					.nextAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-buttons' ).length > 0 &&
				$( item )
					.prevAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-divider' ).length > 0
			) {
				$( '.wpforms-field-wrap' ).sortable( 'cancel' );
				$.confirm( {
					title: wpforms_builder.heads_up,
					content: wpforms_repeater_builder.invalid_pagebreak_msg,
					icon: 'fa fa-exclamation-circle',
					type: 'red',
					buttons: {
						cancel: {
							text: wpforms_builder.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
						},
					},
				} );
			}
		},

		/**
		 * Validate Property.
		 *
		 * @since 1.0.0
		 */
		validateRepeaterProperty: function () {
			// Remove Delete Option from Repeater Header.
			$( document )
				.find(
					'#wpforms-panel-fields .wpforms-field.wpforms-field-repeater'
				)
				.has( '.wpforms-repeater-divider' )
				.each( function () {
					$( this ).find( '.wpforms-field-delete' ).remove();
				} );
		},

		/**
		 * Validate Property
		 *
		 * @since 1.0.0
		 * @param {*} event
		 * @param {*} id
		 */
		validatePageBreakAdd: function ( event, id ) {
			if (
				$( event.target ).has( '.wpforms-field.wpforms-pagebreak-top' )
					.length > 0
			) {
				var topId = $( event.target )
					.find( '.wpforms-field.wpforms-pagebreak-top' )
					.attr( 'data-field-id' );
				if (
					topId !== undefined &&
					$( event.target ).has(
						'.wpforms-field[data-field-id="' + ( topId - 1 ) + '"]'
					).length === 0
				) {
					$( '.wpforms-field.wpforms-pagebreak-top' ).remove();
					$(
						'.wpforms-field-option[data-field-id="' + topId + '"]'
					).remove();
				}
			}

			if (
				$( event.target ).has(
					'.wpforms-field.wpforms-pagebreak-bottom'
				).length > 0
			) {
				var bottomId = $( event.target )
					.find( '.wpforms-field.wpforms-pagebreak-bottom' )
					.attr( 'data-field-id' );
				if (
					bottomId !== undefined &&
					$( event.target ).has(
						'.wpforms-field[data-field-id="' +
							( bottomId - 2 ) +
							'"]'
					).length === 0
				) {
					$( '.wpforms-field.wpforms-pagebreak-bottom' ).remove();
					$(
						'.wpforms-field-option[data-field-id="' +
							bottomId +
							'"]'
					).remove();
				}
			}

			// Validate Pagebreak and Remove Option once gets failed.
			if (
				$(
					'.wpforms-field.wpforms-field-pagebreak[data-field-id="' +
						id +
						'"]'
				)
					.nextAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-buttons' ).length > 0 &&
				$(
					'.wpforms-field.wpforms-field-pagebreak[data-field-id="' +
						id +
						'"]'
				)
					.prevAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-divider' ).length > 0
			) {
				// Entry Preview remove.
				if (
					$( document ).has(
						'.wpforms-field-drag[data-field-type="entry-preview"]'
					).length > 0
				) {
					$( document )
						.find(
							'.wpforms-field-drag[data-field-type="entry-preview"]'
						)
						.remove();

					$( document )
						.find( '#wpforms-add-fields-entry-preview' )
						.removeClass( 'wpforms-entry-preview-adding' );

					$.confirm( {
						title: wpforms_builder.heads_up,
						content:
							wpforms_repeater_builder.invalid_entry_preview_msg,
						icon: 'fa fa-exclamation-circle',
						type: 'red',
						buttons: {
							cancel: {
								text: wpforms_builder.ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
							},
						},
					} );
				}

				// Page Break remove notice.
				if (
					$(
						'.wpforms-field.wpforms-field-pagebreak-normal[data-field-id="' +
							id +
							'"]'
					)
						.nextAll( '.wpforms-field-repeater' )
						.first()
						.has( '.wpforms-repeater-buttons' ).length > 0 &&
					$(
						'.wpforms-field.wpforms-field-pagebreak-normal[data-field-id="' +
							id +
							'"]'
					)
						.prevAll( '.wpforms-field-repeater' )
						.first()
						.has( '.wpforms-repeater-divider' ).length > 0
				) {
					$.confirm( {
						title: wpforms_builder.heads_up,
						content:
							'<div>Page Break can not be added inside Repeater Field</div>',
						icon: 'fa fa-exclamation-circle',
						type: 'red',
						buttons: {
							cancel: {
								text: wpforms_builder.ok,
								btnClass: 'btn-confirm',
								keys: [ 'enter' ],
							},
						},
					} );
				}

				// Page Break remove.
				$(
					'.wpforms-field.wpforms-field-pagebreak[data-field-id="' +
						id +
						'"]'
				).remove();
				$(
					'.wpforms-field-option[data-field-id="' + id + '"]'
				).remove();
			}
		},

		/**
		 * Adds Repeater Row.
		 *
		 * @since 1.0.0
		 * @param {*} event
		 * @param {*} id
		 * @param {*} type
		 */
		fieldRepeaterAdd: function ( event, id, type ) {
			if (
				$(
					'.wpforms-field.wpforms-field-repeater[data-field-id="' +
						id +
						'"]'
				).has( '.wpforms-repeater-divider' ).length > 0 &&
				$(
					'.wpforms-field.wpforms-field-repeater[data-field-id="' +
						id +
						'"]'
				)
					.nextAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-buttons' ).length > 0 &&
				$(
					'.wpforms-field.wpforms-field-repeater[data-field-id="' +
						id +
						'"]'
				).has( '.wpforms-repeater-divider' ).length > 0 &&
				$(
					'.wpforms-field.wpforms-field-repeater[data-field-id="' +
						id +
						'"]'
				)
					.prevAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-divider' ).length > 0
			) {
				$(
					'.wpforms-field.wpforms-field-repeater[data-field-id="' +
						id +
						'"]'
				).remove();
				$(
					'.wpforms-field-option[data-field-id="' + id + '"]'
				).remove();
				return;
			}
			var defaults = {
					position: 'bottom',
					placeholder: false,
					scroll: false,
					defaults: false,
				},
				options = $.extend( {}, defaults, options );
			var data = {
				action: 'wpforms_new_field_' + type,
				id: s.formID,
				type: type,
				defaults: options,
				nonce: wpforms_builder.nonce,
			};

			return $.post( wpforms_builder.ajax_url, data, function ( res ) {
				if ( res.success ) {
					var $preview = $(
							'#wpforms-panel-fields .wpforms-panel-content-wrap'
						),
						$newField = $( res.data.preview ),
						$newOptions = $( res.data.options );
					$newField.css( 'display', 'none' );
					if ( options.placeholder ) {
						options.placeholder.remove();
					}
					$newField.insertAfter(
						'.wpforms-field.wpforms-field-repeater[data-field-id="' +
							id +
							'"]'
					);
					$newOptions.insertAfter(
						'.wpforms-field-option[data-field-id="' + id + '"]'
					);
					$newOptions.css( 'display', 'none' );
					$newField.fadeIn();
					$builder.find( '.no-fields, .no-fields-preview' ).remove();
					$builder.find( '.wpforms-field-submit' ).show();
					// Scroll to the desired position.
					if ( options.scroll && options.position.length ) {
						var scrollTop = $preview.scrollTop(),
							newFieldPosition = $newField.position().top,
							scrollAmount =
								newFieldPosition > scrollTop
									? newFieldPosition - scrollTop
									: newFieldPosition + scrollTop;
						$preview.animate(
							{
								// Position `bottom` actually means that we need to scroll to the newly added field.
								scrollTop:
									options.position === 'bottom'
										? scrollAmount
										: 0,
							},
							1000
						);
					}
					$( '#wpforms-field-id' ).val( res.data.field.id + 1 );
					wpf.initTooltips();
					WPFormsRepeaterBuilder.validateRepeaterProperty();
				}
			} )
				.fail( function ( xhr ) {
					// eslint-disable-next-line no-console
					console.log( xhr.responseText );
				} )
				.always( function () {
					$builder
						.find(
							'.wpforms-add-fields .wpforms-add-fields-button'
						)
						.prop( 'disabled', false );
				} );
		},

		/**
		 * Remove Repeater Field.
		 *
		 * @since 1.0.0
		 * @param {*} event
		 * @param {*} id
		 * @param {*} type
		 */
		fieldRepeaterDelete: function ( event, id, type ) {
			if ( 'repeater' !== type ) {
				return;
			}

			var closestId = id - 1;
			var $repeaterTop = $( document ).find(
				".wpforms-repeater-top[data-field-id='" + closestId + "']"
			);
			$repeaterTop.remove();
			$( '#wpforms-field-option-' + closestId ).remove();
		},

		// Toggle Repeater Field Smart Tag.
		toggleRepeaterFieldSmartTag: function ( id ) {
			if (
				$( '.wpforms-field[data-field-id="' + id + '"]' )
					.nextAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-buttons' ).length > 0 &&
				$( '.wpforms-field[data-field-id="' + id + '"]' )
					.prevAll( '.wpforms-field-repeater' )
					.first()
					.has( '.wpforms-repeater-divider' ).length > 0
			) {
				$( document )
					.find( '.smart-tags-list-display li' )
					.has( 'a[data-meta="' + id + '"]' )
					.each( function name() {
						$( this ).hide();
					} );
			} else if (
				$(
					'.wpforms-field.wpforms-field-repeater[data-field-id="' +
						id +
						'"]'
				).has( '.wpforms-repeater-buttons' ).length > 0
			) {
				$( document )
					.find( '.smart-tags-list-display li' )
					.has( 'a[data-meta="' + id + '"]' )
					.each( function name() {
						$( this ).hide();
					} );
			} else {
				$( document )
					.find( '.smart-tags-list-display li' )
					.has( 'a[data-meta="' + id + '"]' )
					.each( function name() {
						$( this ).show();
					} );
			}
		},
	};

	WPFormsRepeaterBuilder.init();
} )( jQuery );
