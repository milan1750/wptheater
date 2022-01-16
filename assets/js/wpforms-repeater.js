/* global wpforms, wpforms_conditional_logic */
( function ( $ ) {
	'use strict';
	var $wpforms = {};

	// Main Survey admin builder object.
	var Repeater = {
		init: function () {
			$( Repeater.ready );
			Repeater.buildUIActions();
		},
		ready: function () {
			// Backup intial form data.
			$( document )
				.find( 'form.wpforms-form' )
				.each( function () {
					$wpforms[ $( this ).attr( 'id' ) ] = $(
						this
					).serializeArray();
				} );

			$( document )
				.find(
					'.wpforms-repeater-fields-wrapper .wpforms-field-repeater'
				)
				.each( function () {
					if ( 1 > $( this ).siblings().length ) {
						$( this )
							.parents( '.wpforms-repeater-field-container' )
							.remove();
					}
				} );

			// Set default value to fields.
			$( document )
				.find(
					'.wpforms-repeater-field-container input, .wpforms-repeater-field-container textarea, .wpforms-repeater-field-container select'
				)
				.each( function () {
					if ( undefined === $( this ).attr( 'name' ) ) {
						return;
					}
					var repeaterID = $( this )
						.parents( '.wpforms-repeater-fields-wrapper' )
						.prev()
						.attr( 'data-field-id' );
					$( this ).attr(
						'name',
						$( this )
							.attr( 'name' )
							.replace(
								/wpforms\[fields\]\[\d+\]\[(\d+)\]/,
								'wpforms[fields][' + repeaterID + '][1]'
							)
					);
				} );

			$( document )
				.find( '.wpforms-field-repeater' )
				.each( function () {
					Repeater.conditionalLogic( $( this ) );
				} );

			// Conditional logic on repeater field wrapper.
			$( document ).on( 'wpformsProcessConditionalsField', function (
				event,
				formID,
				fieldID
			) {
				var $fieldContainer = $( document ).find(
					'#wpforms-' + formID + '-field_' + fieldID + '-container'
				);

				Repeater.conditionalLogic( $fieldContainer );
			} );
		},
		conditionalLogic: function ( $fieldContainer ) {
			if (
				$fieldContainer.hasClass( 'wpforms-field-repeater' ) &&
				$fieldContainer.hasClass( 'wpforms-conditional-show' )
			) {
				$fieldContainer
					.parents( '.wpforms-repeater-field-container' )
					.addClass( 'wpforms-conditional-show' )
					.removeClass( 'wpforms-conditional-hide' )
					.css( 'display', 'block' );
			} else if (
				$fieldContainer.hasClass( 'wpforms-field-repeater' ) &&
				$fieldContainer.hasClass( 'wpforms-conditional-hide' )
			) {
				$fieldContainer
					.parents( '.wpforms-repeater-field-container' )
					.addClass( 'wpforms-conditional-hide' )
					.removeClass( 'wpforms-conditional-show' )
					.css( 'display', 'none' );
			}
		},
		buildUIActions: function () {
			// Adds Repeater Row.
			$( document ).on(
				'click',
				'.wpforms-field-repeater .wpforms-repeater-button.wpforms-repeater-add',
				function () {
					var formId = $( this )
							.closest( 'form.wpforms-form' )
							.attr( 'id' ),
						$form = $( '#' + formId ),
						formID = $form.attr( 'data-formid' ),
						repeaterID = $( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings()
							.first()
							.attr( 'data-field-id' ),
						cloned = $( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.clone( true ),
						rows = $( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings()
							.find(
								'.wpforms-field-repeater .wpforms-field-label'
							)
							.map( function () {
								return $( this ).data( 'repeater-row' );
							} )
							.get();

					// Push rows.
					rows.push( $( this ).parent().attr( 'data-repeater-row' ) );

					var repeatCount = 1;
					cloned.find( 'input, textarea, select' ).each( function () {
						if ( undefined === $( this ).attr( 'name' ) ) {
							return;
						}
						$( this ).attr(
							'name',
							$( this )
								.attr( 'name' )
								.replace(
									/wpforms\[fields\]\[\d+\]\[(\d+)\]/,
									'wpforms[fields][' +
										repeaterID +
										'][' +
										repeatCount +
										']'
								)
						);
					} );
					// Set default value to fields.
					cloned.find( 'input, textarea, select' ).each( function () {
						var $field = $( this );
						// eslint-disable-next-line array-callback-return
						var dataVal = $wpforms[ formId ].filter( function (
							item
						) {
							if ( item.name === $field.attr( 'name' ) ) {
								return item;
							}
						} );
						// eslint-disable-next-line array-callback-return
						var valueArray = dataVal.map( function ( item ) {
							if ( item.name === $field.attr( 'name' ) ) {
								return item.value;
							}
						} );

						switch ( $field.prop( 'tagName' ).toLowerCase() ) {
							case 'textarea':
								$field.val( valueArray[ 0 ] );
								break;
							case 'select':
								if ( undefined !== $field.attr( 'multiple' ) ) {
									$field.val( valueArray );
								} else {
									$field.val( valueArray[ 0 ] );
								}
								break;
							default:
								switch ( $field.attr( 'type' ) ) {
									case 'checkbox':
										if (
											valueArray.includes(
												$( this ).attr( 'value' )
											)
										) {
											$( this ).prop( 'checked', true );
										} else {
											$( this ).prop( 'checked', false );
										}
										break;
									case 'radio':
										if (
											valueArray.includes(
												$( this ).attr( 'value' )
											)
										) {
											$( this ).prop( 'checked', true );
										} else {
											$( this ).prop( 'checked', false );
										}

										// Uncheck likert scale option.
										if (
											$( this ).hasClass(
												'wpforms-likert-scale-option'
											)
										) {
											$( this ).prop( 'checked', false );
										}
										break;
									default:
										$field.val( valueArray[ 0 ] );
										break;
								}
								break;
						}
					} );

					// Repeat Count.
					repeatCount = parseInt( Math.max.apply( Math, rows ) + 1 );

					// Change name and id inside Repeater Field.
					cloned.find( 'input, select, textarea' ).each( function () {
						if ( undefined === $( this ).attr( 'name' ) ) {
							return;
						}
						$( this ).attr(
							'name',
							$( this )
								.attr( 'name' )
								.replace(
									/wpforms\[fields\]\[\d+\]\[(\d+)\]/,
									'wpforms[fields][' +
										repeaterID +
										'][' +
										repeatCount +
										']'
								)
						);

						if ( undefined === $( this ).attr( 'id' ) ) {
							return;
						}

						var $label = $( this ).siblings(
							'label[for="' + $( this ).attr( 'id' ) + '"]'
						);

						if (
							$( this )
								.attr( 'id' )
								.match( /repeater/ )
						) {
							$( this ).attr(
								'id',
								$( this )
									.attr( 'id' )
									.replace(
										/repeater_[\d+]/,
										'repeater_' + repeatCount
									)
							);
						} else {
							$( this ).attr(
								'id',
								$( this ).attr( 'id' ) +
									'_repeater_' +
									repeatCount
							);
						}

						$label.attr( 'for', $( this ).attr( 'id' ) );

						$( this )
							.parents( '[id*=wpforms-' + formID + '-field_]' )
							.attr(
								'id',
								'wpforms-' +
									formID +
									'-field_' +
									$( this )
										.attr( 'id' )
										.match( /field_(.*)/ )[ 1 ] +
									'-container'
							);
					} );

					// Initilize inputs properties.
					cloned.find( 'input, select, textarea' ).each( function () {
						var name =
							'wpforms[fields][' +
							repeaterID +
							'][1][' +
							$( this )
								.closest( '.wpforms-field' )
								.attr( 'data-field-id' ) +
							']';
						if ( 'multiple' === $( this ).attr( 'multiple' ) ) {
							name += '[]';
						}

						// eslint-disable-next-line array-callback-return
						var dataVal = $wpforms[ formId ].filter( function (
							item
						) {
							if ( item.name === name ) {
								return item;
							}
						} );
						// eslint-disable-next-line array-callback-return
						var valueArray = dataVal.map( function ( item ) {
							if ( item.name === name ) {
								return item.value;
							}
						} );

						if ( $( this ).attr( 'type' ) === 'radio' ) {
							if (
								valueArray.length > 0 &&
								valueArray[ 0 ] !== '' &&
								valueArray.includes( $( this ).attr( 'value' ) )
							) {
								$( this ).prop( 'checked', true );
							} else {
								$( this ).prop( 'checked', false );
							}
						}
						if (
							$( this ).hasClass( 'wpforms-smart-phone-field' )
						) {
							if ( typeof $.fn.intlTelInput === 'undefined' ) {
								return;
							}

							var $el = $( this ).clone();
							$el.removeClass( 'wpforms-input-temp-name' );
							$el.attr(
								'name',
								$el.attr( 'name' ).replace( 'wpf-temp-', '' )
							);
							$( this ).parents( '.wpforms-field' ).append( $el );
							$el.parents( '.wpforms-field' )
								.find( '.iti--allow-dropdown' )
								.remove();
						}
						if ( $( this ).hasClass( 'wpforms-datepicker' ) ) {
							$( this ).removeClass(
								'flatpickr-input active wpforms-valid'
							);
							$( this ).removeAttr( 'readonly' );
							$( this ).removeAttr( 'aria-invalid' );
							$( this ).siblings( 'a' ).css( 'display', 'none' );
							$el = $( this ).clone();
							$( this ).replaceWith( $el );
						}

						if ( $( this ).hasClass( 'wpforms-timepicker' ) ) {
							$( this ).removeClass(
								' ui-timepicker-input wpforms-valid'
							);
							$( this ).removeAttr( 'autocomplete' );
							$( this ).removeAttr( 'aria-invalid' );
							$el = $( this ).clone();
							$( this ).replaceWith( $el );
						}

						if ( $( this ).hasClass( 'choicesjs-select' ) ) {
							$( this ).removeClass(
								'choices__input choices__input--hidden wpforms-valid'
							);
							$( this ).removeAttr( 'data-choice' );
							$( this ).removeAttr( 'tabindex' );
							$( this ).removeAttr( 'aria-invalid' );
							$el = $( this ).clone();
							if ( 'multiple' !== $el.attr( 'multiple' ) ) {
								$el.html( '' );
							} else {
								var placeholder = $( this )
									.closest( '.wpforms-field' )
									.find( '.choices__input' )
									.attr( 'aria-label' );
							}
							var optionsArray = [];
							$( this )
								.parents( '.wpforms-field' )
								.find( '.choices__item' )
								.each( function ( index, e ) {
									optionsArray.push( {
										text: $( e ).html(),
										value: $( e ).attr( 'data-value' ),
									} );
								} );
							var placeFlag = true;
							for ( var i = 0; i < optionsArray.length; i++ ) {
								$el.find( 'option' ).each( function () {
									var selected = $( this ).val();
									if ( valueArray.includes( selected ) ) {
										$( this ).attr(
											'selected',
											'selected'
										);
									}
								} );
								if (
									optionsArray[ i ].text.match(
										/button|No choices to/
									) ||
									$el.has(
										'option[value="' +
											optionsArray[ i ].value +
											'"]'
									).length > 0
								) {
									continue;
								}

								if ( placeFlag ) {
									$el.append(
										'<option value class="placeholder" disabled>' +
											optionsArray[ i ].text +
											'</option>'
									);
									placeFlag = false;
								}
								var selected = '';

								if (
									valueArray.length > 0 &&
									valueArray[ 0 ] !== '' &&
									valueArray.includes(
										optionsArray[ i ].value
									)
								) {
									selected = 'selected="selected"';
								}
								$el.append(
									'<option value="' +
										optionsArray[ i ].value +
										'" ' +
										selected +
										'>' +
										optionsArray[ i ].text +
										'</option>'
								);
							}
							if (
								$el.find( 'option[selected="selected"]' )
									.length === 0
							) {
								$el.find( 'option.placeholder' ).attr(
									'selected',
									'selected'
								);
							}

							if ( 'multiple' === $el.attr( 'multiple' ) ) {
								$el.find( 'option.placeholder' ).removeAttr(
									'selected'
								);
								$el.find( 'option.placeholder' ).html(
									placeholder
								);
							} else {
								$( $el )
									.find(
										'[value="' +
											$( $el )
												.find( '.placeholder' )
												.text() +
											'"]'
									)
									.remove();
							}
							$( this ).parents( '.wpforms-field' ).append( $el );
							$( this ).parents( '.choices ' ).remove();
						}
					} );

					// Clone buttons and add to repeater.
					cloned
						.find( '.wpforms-field-repeater .wpforms-field-label' )
						.attr( 'data-repeater-row', repeatCount );

					// Add Fields to repeater.
					cloned.insertAfter(
						$( this ).parents( '.wpforms-repeater-fields-wrapper' )
					);

					// Load jQuery library.
					wpforms.loadDatePicker();
					wpforms.loadTimePicker();
					wpforms.loadInputMask();
					wpforms.loadSmartPhoneField();
					wpforms.loadPayments();
					wpforms.loadChoicesJS();

					if (
						typeof wpforms_conditional_logic !== 'undefined' &&
						typeof wpforms_conditional_logic[ formID ] !==
							'undefined'
					) {
						var fields = wpforms_conditional_logic[ formID ];
						cloned
							.find( 'input, select, textarea' )
							.each( function () {
								if ( undefined === $( this ).attr( 'id' ) ) {
									return;
								}

								var fieldId = $( this )
									.parents( '.wpforms-field' )
									.attr( 'data-field-id' );
								var newId = $( this )
									.attr( 'id' )
									.replace(
										'wpforms-' + formID + '-field_',
										''
									);

								if (
									undefined !== fields[ fieldId ] &&
									undefined === fields[ newId ]
								) {
									var newField = JSON.parse(
										JSON.stringify( fields[ fieldId ] )
									);

									for ( var i in newField.logic ) {
										for (
											var j = 0;
											j < newField.logic[ i ].length;
											j++
										) {
											if (
												$( document )
													.find(
														'#wpforms-' +
															formID +
															'-field_' +
															newField.logic[ i ][
																j
															].field +
															'-container'
													)
													.parents(
														'.wpforms-repeater-fields-wrapper'
													).length > 0
											) {
												newField.logic[ i ][ j ].field =
													newField.logic[ i ][ j ]
														.field +
													'_repeater_' +
													repeatCount;
											}
										}
									}

									fields[ newId ] = newField;
								}
							} );
						wpforms_conditional_logic[ formID ] = fields;
					}

					var repeaterLength = $( this )
						.parents( '.wpforms-repeater-fields-wrapper' )
						.siblings().length;

					// Check Repeater length and show/hide buttons.
					if (
						$( this ).parent().attr( 'data-repeat-limit' ) !==
							undefined &&
						$( this ).parent().attr( 'data-repeat-limit' ) > 1 &&
						repeaterLength >=
							$( this ).parent().attr( 'data-repeat-limit' )
					) {
						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings()
							.each( function () {
								$( this )
									.find( '.wpforms-repeater-add' )
									.addClass( 'wpforms-hidden' );
								$( this )
									.find( '.wpforms-repeater-remove' )
									.removeClass( 'wpforms-hidden' );
							} );
						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.find( '.wpforms-repeater-add' )
							.addClass( 'wpforms-hidden' )
							.find( '.wpforms-repeater-remove' )
							.removeClass( 'wpforms-hidden' );
					}

					// Check Repeater length and show/hide buttons.
					if ( repeaterLength === 2 ) {
						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings()
							.each( function () {
								$( this )
									.find( 'button.wpforms-repeater-remove' )
									.removeClass( 'wpforms-hidden' );
							} );

						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.find( 'button.wpforms-repeater-remove' )
							.removeClass( 'wpforms-hidden' );
					}

					if ( undefined !== window.wpformsconditionals ) {
						window.wpformsconditionals.processConditionals(
							$form,
							false
						);
					}
				}
			);

			// Remove Repeater Field.
			$( document ).on(
				'click',
				'.wpforms-field-repeater .wpforms-repeater-button.wpforms-repeater-remove',
				function () {
					// Repeat Count.
					var repeaterLength =
						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings().length - 1;

					// Check Repeater length and show/hide buttons.
					if (
						repeaterLength <
						$( this ).parent().attr( 'data-repeat-limit' )
					) {
						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings()
							.each( function () {
								$( this )
									.find( '.wpforms-repeater-add' )
									.removeClass( 'wpforms-hidden' );
							} );
					}

					// Check Repeater length and show/hide buttons.
					if ( repeaterLength === 1 ) {
						$( this )
							.parents( '.wpforms-repeater-fields-wrapper' )
							.siblings()
							.each( function () {
								$( this )
									.find( '.wpforms-repeater-remove' )
									.addClass( 'wpforms-hidden' );
							} );
					}

					// Remove Repeater Fields
					$( this )
						.parents( '.wpforms-repeater-fields-wrapper' )
						.remove();
					wpforms.loadPayments();
				}
			);
		},
	};
	Repeater.init();
} )( jQuery );
