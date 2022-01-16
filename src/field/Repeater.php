<?php

namespace WPForms\Repeater\Field;

/**
 * Repeater field.
 *
 * @since 1.0.0
 */
class Repeater extends \WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		// Define field type information.
		$this->name  = esc_html__( 'Repeater', 'wpforms-repeater' );
		$this->type  = 'repeater';
		$this->icon  = 'fa-repeat';
		$this->order = 300;
		$this->group = 'fancy';

		// Hooks.
		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.0.0
	 */
	private function hooks() {
		// Admin form builder enqueues.
		add_action( 'wpforms_builder_enqueues_before', [ $this, 'admin_builder_enqueues' ] );

		// Add preview field classes.
		add_filter( 'wpforms_field_new_class', [ $this, 'preview_field_class' ], 10, 2 );
		add_filter( 'wpforms_field_preview_class', [ $this, 'preview_field_class' ], 10, 2 );

		// Form frontend Stylesheet enqueues.
		add_action( 'wpforms_frontend_css', [ $this, 'enqueue_frontend_css' ] );

		// Form frontend JavaScript enqueues.
		add_action( 'wpforms_frontend_js', [ $this, 'enqueue_frontend_js' ] );

		// Repeater field frontend visibility.
		add_filter( 'wpforms_field_data', [ $this, 'field_visibility' ], 10, 2 );

		// Inject the repeater fields wrapper.
		add_action( 'wpforms_display_field_before', [ $this, 'inject_before_repeater_field' ], -9999 );
		add_action( 'wpforms_display_field_after', [ $this, 'inject_after_repeater_field' ], PHP_INT_MAX );

		// Prefill and define additional field properties.
		add_filter( 'wpforms_field_properties', [ $this, 'field_modify_name_property' ], 5, 3 );
		add_filter( 'wpforms_field_properties_' . $this->type, [ $this, 'field_properties' ], 5, 3 );

		// Change the repeater's value while saving entries.
		add_filter( 'wpforms_process_before_form_data', [ $this, 'process_before_form_data' ], 5 );

		// Customize the value format for entry details.
		add_filter( 'wpforms_html_field_value', [ $this, 'field_html_value' ], 10, 4 );

		// Hide the repeater fields in the entries list-table.
		add_filter( 'wpforms_entries_table_columns', [ $this, 'hide_entries_table_columns' ], 10, 2 );
		add_filter( 'wpforms_entries_table_fields_disallow', [ $this, 'disallow_entries_table_fields' ] );

		// Hide the repeater fields in the entries preview output.
		add_filter( 'wpforms_pro_fields_entry_preview_print_entry_preview_exclude_field', [ $this, 'exclude_field_from_entry_preview' ], 10, 3 );

		// Recognize repeater fields as payment field types.
		add_filter( 'wpforms_payment_fields', [ $this, 'allow_repeater_payment_fields' ] );

		// Update entry fields before preview.
		add_filter( 'wpforms_entry_single_data', [ $this, 'entry_single_data' ], 20, 3 );

		// Export field value.
		add_filter( 'wpforms_pro_admin_entries_export_ajax_get_csv_cols', [ $this, 'export_get_csv_cols' ], 10, 2 );
	}

	/**
	 * Enqueues for the admin form builder.
	 *
	 * @since 1.0.0
	 */
	public function admin_builder_enqueues() {
		$min = wpforms_get_min_suffix();

		// Stylesheet.
		wp_enqueue_style(
			'wpforms-repeater-builder',
			plugins_url( 'assets/css/admin-repeater-builder.css', WPFORMS_REPEATER_PLUGIN_FILE ),
			[],
			WPFORMS_REPEATER_VERSION
		);

		// JavaScript.
		wp_enqueue_script(
			'wpforms-repeater-builder',
			plugins_url( "assets/js/admin-repeater-builder{$min}.js", WPFORMS_REPEATER_PLUGIN_FILE ),
			[ 'jquery', 'wpforms-builder', 'wpforms-utils' ],
			WPFORMS_REPEATER_VERSION,
			true
		);

		wp_localize_script(
			'wpforms-repeater-builder',
			'wpforms_repeater_builder',
			[
				'invalid_sort_msg'          => esc_html__( 'Invalid Repeater Field sort.', 'wpforms-repeater' ),
				'invalid_pagebreak_msg'     => esc_html__( 'Page Break field cannot be added inside Repeater field.', 'wpforms-repeater' ),
				'invalid_entry_preview_msg' => esc_html__( 'Entry preview field cannot be added inside Repeater field.', 'wpforms-repeater' ),
			]
		);
	}

	/**
	 * Form fronetend CSS enqueues.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_css() {
		wp_enqueue_style(
			'wpforms-repeater-builder',
			plugins_url( 'assets/css/wpforms-repeater.css', WPFORMS_REPEATER_PLUGIN_FILE ),
			[],
			WPFORMS_REPEATER_VERSION
		);
	}

	/**
	 * Enqueues for the frontend.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_js() {
		$min = wpforms_get_min_suffix();

		// JavaScript.
		wp_enqueue_script(
			'wpforms-repeater',
			plugins_url( "assets/js/wpforms-repeater{$min}.js", WPFORMS_REPEATER_PLUGIN_FILE ),
			[ 'jquery', 'wpforms' ],
			WPFORMS_REPEATER_VERSION,
			true
		);
	}

	/**
	 * Add class to the builder field preview.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css CSS classes.
	 * @param array  $field Field data and settings.
	 *
	 * @return string
	 */
	public function preview_field_class( $css, $field ) {
		if ( 'repeater' === $field['type'] ) {
			if ( ! empty( $field['position'] ) && 'bottom' === $field['position'] ) {
				$css .= ' wpforms-repeater-bottom';
			} else {
				$css .= ' wpforms-repeater-top';
			}
		}

		return $css;
	}

	/**
	 * Repeater field frontend visibility.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data.
	 * @param array $form_data Form Data and settings.
	 *
	 * @return array Modified repeatable field data.
	 */
	public function field_visibility( $field, $form_data ) {
		$repeater_starts = false;
		$repeater        = [];
		$hide_repeater   = false;
		if ( ! empty( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $fld ) {
				if ( $this->type === $fld['type'] && ! $repeater_starts ) {
					$repeater        = $fld;
					$repeater_starts = true;
					$hide_repeater   = isset( $fld['hide_repeater'] );
					if ( (int) $field['id'] === (int) $fld['id'] && ( $hide_repeater && '1' === $repeater['hide_repeater'] ) ) {
						return false;
					}
				} elseif ( $this->type === $fld['type'] && $repeater_starts ) {
					$repeater_starts = false;
					if ( (int) $field['id'] === (int) $fld['id'] && ( $hide_repeater && '1' === $repeater['hide_repeater'] ) ) {
						return false;
					}
				} elseif ( $this->type !== $fld['type'] && $repeater_starts && $hide_repeater && (int) $field['id'] === (int) $fld['id'] ) {
					if ( '1' === $repeater['hide_repeater'] ) {
						return false;
					}
					return $field;
				}
			}
		}

		return $field;
	}

	/**
	 * Repeater field wrapper before.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $field Field data.
	 */
	public function inject_before_repeater_field( $field ) {
		if ( 'repeater' === $field['type'] && 'bottom' !== $field['position'] ) {
			echo '<div class="wpforms-repeater-field-container">';
		}
	}

	/**
	 * Repeater field wrapper after.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $field Field data.
	 */
	public function inject_after_repeater_field( $field ) {
		if ( 'repeater' === $field['type'] ) {
			echo 'bottom' !== $field['position'] ? '<div class="wpforms-repeater-fields-wrapper">' : '</div></div>';
		}
	}

	/**
	 * Modify field name property for repeater fields.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties List field properties.
	 * @param array $field Field data and settings.
	 * @param array $form_data Form data and settings.
	 *
	 * @return array Modified field properties.
	 */
	public function field_modify_name_property( $properties, $field, $form_data ) {
		$repeater_id     = 0;
		$repeater_starts = false;

		if ( ! empty( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $fld ) {
				if ( in_array( $fld['type'], [ 'html', 'divider' ], true ) ) {
					continue;
				}

				if ( 'repeater' === $fld['type'] && ! $repeater_starts ) {
					$repeater_starts = true;
					$repeater_id     = $fld['id'];
				} elseif ( 'repeater' === $fld['type'] && $repeater_starts ) {
					$repeater_starts = false;
				} elseif ( 'repeater' !== $fld['type'] && $repeater_starts && $fld['id'] === $field['id'] ) {
					if ( isset( $properties['inputs']['primary'] ) ) {
						$properties['inputs']['primary']['attr']['name'] = preg_replace( '/wpforms\[fields\]/', 'wpforms[fields][' . $repeater_id . '][1]', $properties['inputs']['primary']['attr']['name'] );

						// For Confirmation Field Options.
						if ( isset( $properties['inputs']['secondary'] ) ) {
							$properties['inputs']['secondary']['attr']['name'] = preg_replace( '/wpforms\[fields\]/', 'wpforms[fields][' . $repeater_id . '][1]', $properties['inputs']['secondary']['attr']['name'] );
						}
					} elseif ( 'select' === $field['type'] || 'payment-select' === $field['type'] ) {
						$properties['input_container']['attr']['name'] = preg_replace( '/wpforms\[fields\]/', 'wpforms[fields][' . $repeater_id . '][1]', $properties['input_container']['attr']['name'] );
					} else {
						foreach ( $properties['inputs'] as $index => $input ) {
							$properties['inputs'][ $index ]['attr']['name'] = preg_replace( '/wpforms\[fields\]/', 'wpforms[fields][' . $repeater_id . '][1]', $properties['inputs'][ $index ]['attr']['name'] );
						}
					}
				}
			}
		}

		return $properties;
	}

	/**
	 * Define the additional repeater field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array $properties List field properties.
	 * @param array $field Field data and settings.
	 *
	 * @return array Modified repeater field properties.
	 */
	public function field_properties( $properties, $field ) {
		if ( ! empty( $field['position'] ) && 'bottom' === $field['position'] ) {
			$properties['position'] = 'bottom';
		} else {
			$properties['position']      = 'top';
			$properties['hide_repeater'] = isset( $field['hide_repeater'] ) ? $field['hide_repeater'] : '0';

			// Label properties.
			$properties['label']['class'][]       = 'wpforms-repeater_label';
			$properties['label']['attr']['style'] = ( isset( $properties['label']['attr']['style'] ) ? $properties['label']['attr']['style'] : '' ) . sprintf( 'text-align: %s', isset( $field['repeater_label_align'] ) ? esc_attr( $field['repeater_label_align'] ) : 'left' );
		}

		return $properties;
	}

	/**
	 * Change the repeater's value while saving entries.
	 *
	 * This runs at the very beginning of the form processing. We change all the
	 * repeatable fields type with prefix 'repeater-` and remove the repeater end
	 * field to the $forms_data, for quick and easy validation during the process,
	 * since $form_data is used and passed throughout the processing work flow.
	 *
	 * @since 1.0.0
	 *
	 * @param array $form_data Form data and settings.
	 *
	 * @return array Modified form data.
	 */
	public function process_before_form_data( $form_data ) {
		$repeater_starts = false;

		if ( ! empty( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $key => $field ) {
				if ( in_array( $field['type'], [ 'html', 'divider' ], true ) ) {
					continue;
				}

				if ( $this->type === $field['type'] && ! $repeater_starts ) {
					$repeater_starts = true;
				} elseif ( $this->type === $field['type'] && $repeater_starts ) {
					$repeater_starts = false;
				} elseif ( $this->type !== $field['type'] && $repeater_starts ) {
					$form_data['fields'][ $key ]['type'] = 'repeater-' . $form_data['fields'][ $key ]['type'];
				}

				// Remove repeater bottom from form data while saving entries.
				if ( $this->type === $field['type'] && 'bottom' === $field['position'] ) {
					unset( $form_data['fields'][ $key ] );
				}
			}
		}

		return $form_data;
	}

	/**
	 * Customize format for HTML email notifications and entry details.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Field value.
	 * @param array  $field Field settings.
	 * @param array  $form_data Form data and settings.
	 * @param string $context Value display context.
	 *
	 * @return string Modified HTML field value.
	 */
	public function field_html_value( $value, $field, $form_data = [], $context = '' ) {
		if ( empty( $field['value'] ) || $field['type'] !== $this->type || 'entry-details' === $context ) {
			return $value;
		}

		if (
			! empty( $field['value_raw'] ) &&
			$this->type === $field['type'] &&
			in_array( $context, [ 'entry-single', 'entry-preview', 'email-html' ], true )
		) {
			$items             = [];
			$hide_empty        = ( isset( $_COOKIE['wpforms_entry_hide_empty'] ) && 'true' === $_COOKIE['wpforms_entry_hide_empty'] ) || 'email-html' === $context;
			$show_empty        = apply_filters( 'wpforms_repeater_show_empty', ! $hide_empty );
			$repeatable_fields = (array) $field['repeatable_fields'];

			foreach ( $field['value_raw'] as $row_key => $row ) {
				$repeater_items = [];

				foreach ( $repeatable_fields as $field_key ) {
					$fld = $row[ $field_key ];

					if ( ! $show_empty && empty( $fld['value'] ) ) {
						continue;
					}

					$repeater_items[ $field_key ] = sprintf(
						'<strong>%s:</strong> %s',
						/* translators: %s: Field ID */
						! empty( $fld['name'] ) ? wp_strip_all_tags( $fld['name'] ) : sprintf( esc_html__( 'Field ID #%d', 'wpforms-repeater' ), absint( $fld['id'] ) ),
						apply_filters(
							'wpforms_html_field_value',
							implode( ', ', explode( "\n", $fld['value'] ? $fld['value'] : '(Empty)' ) ),
							$fld,
							$form_data,
							'repeater-field'
						)
					);

					// In case of Likert field.
					$repeater_items[ $field_key ] = str_replace( ':,', ':', $repeater_items[ $field_key ] );
				}

				$items[ $row_key ] = sprintf( '<span class="wpforms-entry-repeater-field-value" style="display:block;">%s</span>', implode( '<br/>', $repeater_items ) );
			}

			return implode( '<div class="wpforms-repeater-sepearator" style="border-top:1px dotted #C3C4C7;display: block;"></div>', $items );
		}

		return $value;
	}

	/**
	 * Hide the repeater field from entry preview.
	 *
	 * @since 1.1.0
	 *
	 * @param bool  $hide      Hide the field.
	 * @param array $field     Field data.
	 * @param array $form_data Form data.
	 *
	 * @return bool
	 */
	public function exclude_field_from_entry_preview( $hide, $field, $form_data ) {
		$repeater_fields = [];
		$repeater_starts = false;
		$form_field_data = wpforms()->form->get( $form_data['id'], [ 'content_only' => true ] );

		if ( ! empty( $form_field_data['fields'] ) ) {
			foreach ( $form_field_data['fields'] as $fld ) {
				if ( $this->type === $fld['type'] && ! $repeater_starts ) {
					$repeater_starts = true;
				} elseif ( $this->type === $fld['type'] && $repeater_starts ) {
					$repeater_starts   = false;
					$repeater_fields[] = (int) $fld['id'];
				} elseif ( $this->type !== $fld['type'] && $repeater_starts ) {
					$repeater_fields[] = (int) $fld['id'];
				}
			}
		}

		// Hide repeater wrapped fields.
		if ( in_array( (int) $field['id'], $repeater_fields, true ) ) {
			return true;
		}

		return $hide;
	}

	/**
	 * Hide repeater field in entries table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Columns.
	 * @param array $form_data Form data and settings.
	 *
	 * @return array $columns Modified entries table columns.
	 */
	public function hide_entries_table_columns( $columns, $form_data ) {
		$repeater_starts = false;

		foreach ( $form_data['fields'] as $field_key => $field ) {
			if ( 'repeater' === $field['type'] && ! $repeater_starts ) {
				$repeater_starts = true;
			} elseif ( 'repeater' === $field['type'] && $repeater_starts ) {
				$repeater_starts = false;
			} elseif ( 'repeater' !== $field['type'] && $repeater_starts ) {
				unset( $columns[ 'wpforms_field_' . $field_key ] );
			}
		}

		return $columns;
	}

	/**
	 * Disallow repeater field from being displayed in the entries table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Fields to disallow.
	 * @return array Modified fields to disallow.
	 */
	public function disallow_entries_table_fields( $fields ) {
		return array_merge( $fields, [ 'repeater' ] );
	}

	/**
	 * Allow repeater field from being accepted as payment field types.
	 *
	 * @since 1.1.0
	 *
	 * @param array $payment_fields Payment field types.
	 * @return array Modified Payment field type to allow repeater.
	 */
	public function allow_repeater_payment_fields( $payment_fields ) {
		return array_merge( $payment_fields, [ 'repeater' ] );
	}

	/**
	 * Update entry fields before preview.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Field data.
	 * @param array $entry     Entry data.
	 * @param array $form_data Form Data and settings.
	 *
	 * @return array Modified fieldS data.
	 */
	public function entry_single_data( $fields, $entry, $form_data ) {
		$repeater_fields = [];
		$repeater_starts = false;

		if ( ! empty( $form_data['fields'] ) ) {
			foreach ( $form_data['fields'] as $fld ) {
				if ( $this->type === $fld['type'] && ! $repeater_starts ) {
					$repeater_starts = true;
				} elseif ( $this->type === $fld['type'] && $repeater_starts ) {
					$repeater_starts   = false;
					$repeater_fields[] = $fld['id'];
				} elseif ( $this->type !== $fld['type'] && $repeater_starts ) {
					$repeater_fields[] = $fld['id'];
				}
			}
		}

		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['id'] ) && in_array( $field['id'], $repeater_fields, true ) ) {
				unset( $fields[ $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Modify the CSV columns.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns_row Array of columns.
	 * @param array $request_data Request data array.
	 *
	 * @return array $columns_row Array of modified columns.
	 */
	public function export_get_csv_cols( $columns_row, $request_data ) {
		$form_data = $request_data['form_data'];

		if ( ! empty( $form_data['fields'] ) ) {
			$repeater_starts = false;

			foreach ( $form_data['fields'] as $fld ) {
				if ( 'repeater' === $fld['type'] && ! $repeater_starts ) {
					$repeater_starts = true;
				} elseif ( 'repeater' === $fld['type'] && $repeater_starts ) {
					$repeater_starts = false;
					unset( $columns_row[ $fld['id'] ] );
				} elseif ( 'repeater' !== $fld['type'] && $repeater_starts ) {
					unset( $columns_row[ $fld['id'] ] );
				}
			}
		}

		return $columns_row;
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_options( $field ) {
		$position = ! empty( $field['position'] ) ? esc_attr( $field['position'] ) : '';

		// Hidden field indicating the position.
		$this->field_element(
			'text',
			$field,
			[
				'type'  => 'hidden',
				'slug'  => 'position',
				'value' => $position,
				'class' => 'position',
			]
		);

		// Options specific to the top pagebreak.
		if ( 'bottom' === $position ) {

			// Options open markup.
			$args = [
				'markup' => 'open',
			];
			$this->field_option( 'basic-options', $field, $args );

			$repeater_button_align        = ! empty( $field['repeater_button_align'] ) ? esc_attr( $field['repeater_button_align'] ) : 'left';
			$repeater_align_label         = $this->field_element(
				'label',
				$field,
				[
					'slug'    => 'repeater_button_align',
					'value'   => esc_html__( 'Repeater Button Align', 'wpforms-repeater' ),
					'tooltip' => esc_html__( 'Select repeat button  type.', 'wpforms-repeater' ),
				],
				false
			);
			$repeater_button_align_select = $this->field_element(
				'select',
				$field,
				[
					'slug'    => 'repeater_button_align',
					'value'   => $repeater_button_align,
					'options' => [
						'left'   => esc_html__( 'Left', 'wpforms-repeater' ),
						'center' => esc_html__( 'Center', 'wpforms-repeater' ),
						'right'  => esc_html__( 'Right', 'wpforms-repeater' ),
					],
				],
				false
			);
			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'repeater_button_align',
					'content' => $repeater_align_label . $repeater_button_align_select,
				]
			);

			// Repeater Label.
			$repeater_button_style        = ! empty( $field['repeater_button_style'] ) ? esc_attr( $field['repeater_button_style'] ) : 'text';
			$repeater_button_style_label  = $this->field_element(
				'label',
				$field,
				[
					'slug'    => 'repeater_button_style',
					'value'   => esc_html__( 'Repeater Button Style', 'wpforms-repeater' ),
					'tooltip' => esc_html__( 'Select repeat button  type.', 'wpforms-repeater' ),
				],
				false
			);
			$repeater_button_style_select = $this->field_element(
				'select',
				$field,
				[
					'slug'    => 'repeater_button_style',
					'value'   => $repeater_button_style,
					'options' => [
						'text'      => esc_html__( 'Text', 'wpforms-repeater' ),
						'icon'      => esc_html__( 'Icon', 'wpforms-repeater' ),
						'icon_text' => esc_html__( 'Icon with Text', 'wpforms-repeater' ),
					],
				],
				false
			);
			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'repeater_button_style',
					'content' => $repeater_button_style_label . $repeater_button_style_select,
				]
			);

			// Add Button Label.
			$add_button       = ! empty( $field['add_button_label'] ) ? $field['add_button_label'] : 'Add';
			$add_button_label = $this->field_element(
				'label',
				$field,
				[
					'slug'    => 'add_button_label',
					'value'   => esc_html__( 'Add Button Label', 'wpforms-repeater' ),
					'tooltip' => esc_html__( 'Define the add button label', 'wpforms-repeater' ),
				],
				false
			);
			$add_button_value = $this->field_element(
				'text',
				$field,
				[
					'type'  => 'text',
					'slug'  => 'add_button_label',
					'value' => $add_button,
				],
				false
			);
			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'add_button_label',
					'content' => $add_button_label . $add_button_value,
				]
			);

			// Remove Button Label.
			$remove_button       = ! empty( $field['remove_button_label'] ) ? $field['remove_button_label'] : 'Remove';
			$remove_button_label = $this->field_element(
				'label',
				$field,
				[
					'slug'    => 'remove_button_label',
					'value'   => esc_html__( 'Remove Button Label', 'wpforms-repeater' ),
					'tooltip' => esc_html__( 'Define the remove button label', 'wpforms-repeater' ),
				],
				false
			);
			$remove_button_value = $this->field_element(
				'text',
				$field,
				[
					'type'  => 'text',
					'slug'  => 'remove_button_label',
					'value' => $remove_button,
				],
				false
			);
			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'remove_button_label',
					'content' => $remove_button_label . $remove_button_value,
				]
			);

			// Maximum Repeat Limit.
			$max_repeat_limit = ! empty( $field['max_repeat_limit'] ) ? (float) $field['max_repeat_limit'] : '';
			$max_limit_label  = $this->field_element(
				'label',
				$field,
				[
					'slug'    => 'max_limit_label',
					'value'   => esc_html__( 'Maximum Repeat Limit', 'wpforms-repeater' ),
					'tooltip' => esc_html__( 'Define the maximum repeat limit.', 'wpforms-repeater' ),
				],
				false
			);
			$max_limit_value  = $this->field_element(
				'text',
				$field,
				[
					'type'  => 'number',
					'slug'  => 'max_limit_label',
					'value' => $max_repeat_limit,
				],
				false
			);
			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'limit_label',
					'content' => $max_limit_label . $max_limit_value,
				]
			);

			// Options close markup.
			$this->field_option(
				'basic-options',
				$field,
				[
					'markup' => 'close',
				]
			);

		} else {

			// Options open markup.
			$args = [
				'markup' => 'open',
			];
			$this->field_option( 'basic-options', $field, $args );

			// Label.
			$this->field_option( 'label', $field );

			// Description.
			$this->field_option( 'description', $field );

			$repeater_label_align        = ! empty( $field['repeater_label_align'] ) ? esc_attr( $field['repeater_label_align'] ) : 'left';
			$repeater_label_aligin_label = $this->field_element(
				'label',
				$field,
				[
					'slug'    => 'repeater_label_align',
					'value'   => esc_html__( 'Repeater Label Align', 'wpforms-repeater' ),
					'tooltip' => esc_html__( 'Select repeat button  type.', 'wpforms-repeater' ),
				],
				false
			);
			$repeater_label_align_select = $this->field_element(
				'select',
				$field,
				[
					'slug'    => 'repeater_label_align',
					'value'   => $repeater_label_align,
					'options' => [
						'left'   => esc_html__( 'Left', 'wpforms-repeater' ),
						'center' => esc_html__( 'Center', 'wpforms-repeater' ),
						'right'  => esc_html__( 'Right', 'wpforms-repeater' ),
					],
				],
				false
			);
			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'repeat_label_align',
					'content' => $repeater_label_aligin_label . $repeater_label_align_select,
				]
			);

			$this->field_element(
				'row',
				$field,
				[
					'slug'    => 'hide_repeater',
					'content' => $this->field_element(
						'toggle',
						$field,
						[
							'slug'    => 'hide_repeater',
							'value'   => isset( $field['hide_repeater'] ) ? $field['hide_repeater'] : '0',
							'desc'    => esc_html__( 'Hide repeater field on the frontend', 'wpforms-repeater' ),
							'tooltip' => esc_html__( 'Check this option to hide repeater field on the frontend', 'wpforms-repeater' ),
						],
						false
					),
				]
			);

			// Options close markup.
			$this->field_option(
				'basic-options',
				$field,
				[
					'markup' => 'close',
				]
			);

			/*
			* Advanced field options.
			*/

			// Options open markup.
			$this->field_option(
				'advanced-options',
				$field,
				[
					'markup' => 'open',
				]
			);

			// Custom CSS classes.
			$this->field_option( 'css', $field );

			// Hide label.
			$this->field_option( 'label_hide', $field );

			// Options close markup.
			$this->field_option(
				'advanced-options',
				$field,
				[
					'markup' => 'close',
				]
			);
		}
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 */
	public function field_preview( $field ) {
		$label     = ! empty( $field['label'] ) ? esc_html( $field['label'] ) : esc_html__( 'Repeater Fields', 'wpforms-repeater' );
		$position  = ! empty( $field['position'] ) ? esc_html( $field['position'] ) : 'top';
		$nav_align = ! empty( $field['repeater_label_align'] ) ? esc_attr( $field['repeater_label_align'] ) : 'left';

		if ( 'bottom' === $position ) {
			$add_button_label    = ! empty( $field['add_button_label'] ) ? esc_html( $field['add_button_label'] ) : esc_html__( 'Add', 'wpforms-repeater' );
			$remove_button_label = ! empty( $field['remove_button_label'] ) ? esc_html( $field['remove_button_label'] ) : esc_html__( 'Remove', 'wpforms-repeater' );
			$button_align        = ! empty( $field['repeater_button_align'] ) ? esc_attr( $field['repeater_button_align'] ) : 'left';
			$button_style        = ! empty( $field['repeater_button_style'] ) ? esc_attr( $field['repeater_button_style'] ) : 'text';

			if ( empty( $this->form_data ) ) {
				$this->form_data = wpforms()->form->get(
					$this->form_id,
					[
						'content_only' => true,
					]
				);
			}

			$icon_style = 'text' === $button_style ? 'display: none' : '';
			$text_style = 'icon' === $button_style ? 'display: none' : '';

			echo '<div class="wpforms-repeater-buttons" style="text-align: ' . esc_attr( $button_align ) . '">';
				printf(
					'<button type="button" class="wpforms-repeater-button wpforms-repeater-add"><span class="icon" style="%s"> + </span><span class="text" style="%s">%s</span></button>',
					$icon_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$text_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $add_button_label )
				);
				printf(
					'<button type="button" class="wpforms-repeater-button wpforms-repeater-remove"><span class="icon" style="%s"> - </span><span class="text" style="%s">%s</span></button></button>',
					$icon_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$text_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					esc_html( $remove_button_label )
				);
			echo '</div>';
		} else {
			// Visual divider.
			echo '<div class="wpforms-repeater-divider" style="text-align: ' . esc_attr( $nav_align ) . '">';
			if ( 'bottom' !== $position ) {
				printf( '<span class="wpforms-repeater-label">%s</span>', esc_html( $label ) );
			}
			echo '<span class="line"></span>';
			echo '</div>';
		}
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field settings.
	 * @param array $deprecated Deprecated.
	 * @param array $form_data Form data and settings.
	 */
	public function field_display( $field, $deprecated, $form_data ) {
		$position            = ! empty( $field['position'] ) ? esc_html( $field['position'] ) : 'top';
		$add_button_label    = ! empty( $field['add_button_label'] ) ? esc_html( $field['add_button_label'] ) : esc_html__( 'Add', 'wpforms-repeater' );
		$remove_button_label = ! empty( $field['remove_button_label'] ) ? esc_html( $field['remove_button_label'] ) : esc_html__( 'Remove', 'wpforms-repeater' );

		if ( 'bottom' === $position ) {
			$button_style = ! empty( $field['repeater_button_style'] ) ? esc_attr( $field['repeater_button_style'] ) : 'text';
			$button_align = ! empty( $field['repeater_button_align'] ) ? esc_attr( $field['repeater_button_align'] ) : 'left';
			$limit        = isset( $field['max_limit_label'] ) ? $field['max_limit_label'] : 1;

			$icon_style = 'text' === $button_style ? 'display: none' : '';
			$text_style = 'icon' === $button_style ? 'display: none' : '';

			echo '<div class="wpforms-field-label" data-repeat-limit="' . absint( $limit ) . '" data-repeater-row="1" style="text-align: ' . esc_attr( $button_align ) . '">';
			printf(
				'<button type="button" class="wpforms-repeater-button wpforms-repeater-add"><span class="icon" style="%s"> + </span><span class="text" style="%s">%s</span></button>',
				$icon_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$text_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $add_button_label )
			);
			printf(
				'<button type="button" class="wpforms-repeater-button wpforms-repeater-remove wpforms-hidden"><span class="icon" style="%s"> - </span><span class="text" style="%s">%s</span></button></button>',
				$icon_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$text_style, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $remove_button_label )
			);
			echo '</div>';
		}
	}

	/**
	 * Validate field on form submit.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id Field ID.
	 * @param mixed $field_submit Field value that was submitted.
	 * @param array $form_data Form data and settings.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$repeater_field = $form_data['fields'][ $field_id ];

		if ( is_array( $field_submit ) ) {
			foreach ( $field_submit as $row ) {
				foreach ( $row as $field_key => $field ) {
					if ( isset( $repeater_field['position'] ) && 'bottom' !== $repeater_field['position'] ) {
						$form_data['fields'][ $field_key ]['type'] = str_replace( 'repeater-', '', $form_data['fields'][ $field_key ]['type'] );
						$field_data                                = $form_data['fields'][ $field_key ];
						/**
						 * Apply things for validation, see WPForms_Field::validate().
						 *
						 * @since 1.0.0
						 *
						 * @param int   $field_id     Field ID.
						 * @param mixed $field_submit Field value that was submitted.
						 * @param array $form_data    Form data and settings.
						 */
						do_action( "wpforms_process_validate_{$field_data['type']}", $field_key, $field, $form_data );
						$form_data['fields'][ $field_key ]['type'] = 'repeater-' . $form_data['fields'][ $field_key ]['type'];
					} else {
						$form_data['fields'][ $field_key ]['type'] = str_replace( 'repeater-', '', $form_data['fields'][ $field_key ]['type'] );
					}
				}
			}

			wpforms()->process->form_data = $form_data;
		}
	}

	/**
	 * Format field.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $field_id Field ID.
	 * @param array $field_submit Submitted field value.
	 * @param array $form_data Form data and settings.
	 */
	public function format( $field_id, $field_submit, $form_data ) {
		// Define data.
		$name       = ! empty( $form_data['fields'][ $field_id ]['label'] ) ? sanitize_text_field( $form_data['fields'][ $field_id ]['label'] ) : '';
		$value      = '';
		$value_raw  = ! empty( $field_submit ) ? (array) $field_submit : '';
		$amount     = 0;
		$show_empty = apply_filters( 'wpforms_repeater_show_empty', true );

		$payment_fields    = wpforms_payment_fields();
		$form_field_data   = wpforms()->form->get( $form_data['id'], [ 'content_only' => true ] );
		$repeater_starts   = false;
		$repeatable_fields = [];

		if ( ! empty( $form_field_data['fields'] ) ) {
			foreach ( $form_field_data['fields'] as $field ) {
				if ( in_array( $field['type'], [ 'html', 'divider' ], true ) ) {
					continue;
				}

				if ( $this->type === $field['type'] && (int) $field['id'] === (int) $field_id && ! $repeater_starts ) {
					$repeater_starts = true;
				} elseif ( $this->type === $field['type'] && $repeater_starts ) {
					$repeater_starts = false;
				} elseif ( $this->type !== $field['type'] && $repeater_starts ) {
					$repeatable_fields[] = $field['id'];
				}
			}
		}

		// Process submitted data.
		if ( ! empty( $value_raw ) ) {
			foreach ( $value_raw as $row_key => $row ) {
				foreach ( $repeatable_fields as $field_key ) {
					$field                                     = ! empty( $row[ $field_key ] ) ? $row[ $field_key ] : '';
					$form_data['fields'][ $field_key ]['type'] = str_replace( 'repeater-', '', $form_data['fields'][ $field_key ]['type'] );
					$field_data                                = $form_field_data['fields'][ $field_key ];
					/**
					 * Apply things for format and sanitize, see WPForms_Field::format().
					 *
					 * @since 1.0.0
					 *
					 * @param int    $field       Field ID.
					 * @param string $field_value Submitted field value.
					 * @param array  $form_data   Form data and settings.
					 */
					do_action( "wpforms_process_format_{$field_data['type']}", $field_key, $field, $form_data );

					$value_raw[ $row_key ][ $field_key ] = wpforms()->process->fields[ $field_key ];

					if ( ! empty( $value_raw[ $row_key ][ $field_key ]['value'] ) ) {
						$value .= sanitize_text_field( $value_raw[ $row_key ][ $field_key ]['name'] ) . ': ' . implode( ', ', explode( "\n", $value_raw[ $row_key ][ $field_key ]['value'] ) ) . "\n";
					} elseif ( $show_empty ) {
						$value .= sanitize_text_field( $value_raw[ $row_key ][ $field_key ]['name'] ) . ': ' . esc_html__( '(Empty)', 'wpforms-repeater' ) . "\n";
					}

					// Increment the amount.
					if ( ! empty( $value_raw[ $row_key ][ $field_key ]['type'] && in_array( $value_raw[ $row_key ][ $field_key ]['type'], $payment_fields, true ) ) ) {
						if ( ! empty( $value_raw[ $row_key ][ $field_key ]['amount'] ) ) {
							$amount += wpforms_sanitize_amount( $value_raw[ $row_key ][ $field_key ]['amount'] );
						}
					}

					// Finally unset the field.
					unset( wpforms()->process->fields[ $field_key ] );
				}

				$value .= "\n";
			}

			// In case of Likert field.
			$value = str_replace( ':,', ':', $value );
		}

		// Set final field details.
		wpforms()->process->fields[ $field_id ] = [
			'name'              => $name,
			'value'             => $value,
			'value_raw'         => $value_raw,
			'amount'            => wpforms_format_amount( $amount ),
			'amount_raw'        => $amount,
			'id'                => absint( $field_id ),
			'type'              => $this->type,
			'repeatable_fields' => $repeatable_fields,
		];
	}
}
