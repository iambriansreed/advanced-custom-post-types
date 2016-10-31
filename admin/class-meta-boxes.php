<?php

namespace Advanced_Custom_Post_Types\Admin;

class Meta_Boxes {

	private $plugin_dir_url;
	private $fields;
	private $field_values = null;

	public function __construct( Fields $fields ) {

		global $post;

		$this->fields = $fields;

		$this->plugin_dir_url = plugin_dir_url( __FILE__ );

		$this->set_field_values( $post );

		foreach ( $fields->groups() as $name => $field_group ) {

			add_filter(
				'postbox_classes_' . ACPT_POST_TYPE . '_' . $field_group->key,
				array( $this, 'postbox_classes' )
			);

			$meta_box = (object) array(
				'id'       => $field_group->key,
				'title'    => $field_group->title,
				'context'  => $field_group->position,
				'priority' => $field_group->style
			);

			add_meta_box(
				$meta_box->id,
				$meta_box->title,
				array( $this, 'meta_box_html' ),
				'acpt_content_type',
				$meta_box->context,
				$meta_box->priority,
				$field_group->fields
			);

		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function admin_enqueue_scripts() {

		wp_dequeue_style( 'select2' );
		wp_deregister_style( 'select2' );

		wp_dequeue_script( 'select2' );
		wp_deregister_script( 'select2' );

		wp_enqueue_script(
			'advanced_custom_post_types_select2',
			$this->plugin_dir_url . 'assets/select2.min.js',
			array( 'jquery' )
		);

		wp_enqueue_script(
			'advanced_custom_post_types_pluralize',
			$this->plugin_dir_url . 'assets/pluralize.js'
		);

		wp_enqueue_script(
			'advanced_custom_post_types',
			$this->plugin_dir_url . 'assets/script.js',
			array( 'jquery', 'advanced_custom_post_types_select2', 'advanced_custom_post_types_pluralize' )
		);

		wp_enqueue_style(
			'advanced_custom_post_types_select2',
			$this->plugin_dir_url . 'assets/select2.min.css'
		);

		wp_enqueue_style(
			'advanced_custom_post_types',
			$this->plugin_dir_url . 'assets/style.css',
			array( 'advanced_custom_post_types_select2' )
		);
	}

	/**
	 * @param $classes
	 *
	 * @return array
	 */
	public function postbox_classes( $classes ) {
		$classes[] = 'acpt-postbox';

		return $classes;
	}

	/**
	 * @param $post
	 * @param $metabox
	 */
	public function meta_box_html( $post, $metabox ) {

		$this->set_field_values( $post );

		$group = $this->group_fields_by_tab( $metabox['args'] );

		foreach ( $group->fields as $field ) {
			if ( ! property_exists( $field, 'hidden' ) || ! $field->hidden ) {

				$this->field_html( $field );
			}
		}

		if ( ! count( $group->tabs ) ) {
			return;
		}

		echo '<div class="tabs">';

		$selected = ' selected';
		foreach ( $group->tabs as $tab_id => $tab ) {

			echo "<div class=\"tab{$selected}\"
		     data-field-key=\"{$tab->field->key}\">{$tab->label}";

			if ( $tab->field->conditional_logic ): ?>
				<script>
					acpt.conditional_logic.Add({
						key: '<?php echo $tab->field->key; ?>',
						args: <?php echo json_encode( $tab->field->conditional_logic ); ?> });
				</script>
				<?php

			endif;

			echo "</div>";
			$selected = '';
		}

		echo '<div class="border"></div></div>';

		echo '<div class="tab-contents">';

		$selected = ' selected';
		foreach ( $group->tabs as $tab_id => $tab ) {

			echo "<div class=\"tab-content{$selected}\">";

			$selected = '';
			foreach ( $tab->fields as $field ) {
				$this->field_html( $field );
			}

			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * @param $fields
	 *
	 * @return object
	 */
	public function group_fields_by_tab( $fields ) {

		$output = (object) array(
			'tabs'   => array(),
			'fields' => array()
		);

		$tab_counter = 0;

		foreach ( $fields as $field ) {

			if ( $field->type === 'tab' ) {

				$tab_counter ++;

				$output->tabs[ $tab_counter ] = (object) array(
					'field'  => $field,
					'fields' => array(),
					'label'  => $field->label
				);

				continue;
			}

			if ( $tab_counter ) {

				$output->tabs[ $tab_counter ]->fields[] = $field;
			} else {

				$output->fields[] = $field;
			}

		}

		return $output;
	}

	public function set_field_values( $post ) {

		if ( $this->field_values ) {
			return;
		}

		$this->field_values = $this->fields->defaults();

		$post_type_data = $post->post_content ? json_decode( $post->post_content, true ) : false;

		if ( $post_type_data && is_array( $post_type_data ) &&
		     isset( $post_type_data['args'] ) && is_array( $post_type_data['args'] )
		) {
			foreach ( $post_type_data['args'] as $name => $value ) {
				$this->field_values[ 'acpt_' . $name ] = $value;
			}
		} else {

			Notices::add_warning( 'There was an error loading your Custom Post Type.' );
		}
	}

	public function get_field_value( $field_name ) {

		return isset( $this->field_values[ $field_name ] ) ? $this->field_values[ $field_name ] : '';
	}

	/**
	 * @param $field
	 *
	 * @internal param $post_id
	 */
	public function field_html( $field ) {

		$parent_type = $field->type;

		$value = $this->get_field_value( $field->name );

		$options = array();

		$multiple = ( property_exists( $field, 'multiple' ) && $field->multiple ) || $field->type === 'checkbox';

		if ( $field->type === 'true_false' ) {

			$parent_type = 'true_false';

			$options[] = array(
				'text'    => $field->message,
				'value'   => 1,
				'checked' => $value
			);

			$value = $value ? 1 : '';

			$multiple = false;

			$field->type = 'checkbox';

		} elseif ( $field->type === 'checkbox' || $field->type === 'select' ) {

			foreach ( $field->choices as $option_value => $text ) {

				$options[] = array(
					'text'    => $text,
					'value'   => $option_value,
					'checked' => is_array( $value ) ? in_array( $option_value, $value ) : $value == $option_value
				);
			}
		}

		$attr_name = esc_attr( $field->name );

		$readonly = ( property_exists( $field, 'readonly' ) && $field->readonly ) ? ' readonly="readonly"' : '';

		$maxlength = ( property_exists( $field, 'maxlength' ) && is_numeric( $field->maxlength )
		               && intval( $field->maxlength ) ) ? ' maxlength="' . intval( $field->maxlength ) . '"' : '';

		?>
		<div class="field <?php echo $field->wrapper->class; ?>"
		     data-field-key="<?php echo $field->key; ?>"
		     data-field-type="<?php echo $parent_type; ?>">

			<label for="<?php echo $attr_name; ?>"><?php

				echo $field->label;
				if ( $field->required ):
					?><span>*</span><?php
				endif; ?></label>

			<div class="input">
				<?php
				if ( $field->type === 'text' || $field->type === 'number' ): ?>
					<input class="widefat" id="<?php echo $attr_name; ?>"
					       name="<?php echo $attr_name; ?>"<?php echo $readonly; ?><?php echo $maxlength; ?>
					       type="<?php echo esc_attr( $field->type ); ?>" value="<?php echo esc_attr( $value ); ?>">
					<?php
				elseif ( $field->type === 'textarea' ):
					?>
					<textarea id="<?php echo $attr_name; ?>"<?php echo $readonly; ?>
					          name="<?php echo $attr_name; ?>"><?php echo $value; ?></textarea>
					<?php
				elseif ( $field->type === 'checkbox' ):

					foreach ( $options as $option ): ?>
						<label class="checkbox">
							<input type="checkbox"<?php echo $readonly; ?>
							       name="<?php echo esc_attr( $field->name . ( $multiple ? '[]' : '' ) ); ?>"
								<?php if ( ! $multiple ): ?>
									id="<?php echo esc_attr( $field->name ); ?>"
								<?php endif; ?>
								   value="<?php echo esc_attr( $option['value'] ); ?>"
								<?php echo $option['checked'] ? 'checked="checked"' : '' ?>/><?php echo $option['text']; ?>
						</label>
						<?php
					endforeach;
				elseif ( $field->type === 'select' ):

					?><select<?php echo $readonly; ?>
					name="<?php echo esc_attr( $field->name . ( $multiple ? '[]' : '' ) ); ?>"
					id="<?php echo $attr_name; ?>" title=""><?php
					foreach ( $options as $option ): ?>
						<option value="<?php echo esc_attr( $option['value'] ); ?>"
							<?php echo $option['checked'] ? ' selected="selected"' : '' ?>><?php echo $option['text']; ?></option>
						<?php
					endforeach;

					?></select><?php
				endif; ?>
			</div>
			<?php if ( $field->instructions ): ?>
				<p class="instructions"><?php echo $field->instructions; ?></p>
			<?php endif; ?>

		</div>
		<?php

		if ( $field->conditional_logic ): ?>
			<script>
				acpt.conditional_logic.Add({
					key: '<?php echo $field->key; ?>',
					args: <?php echo json_encode( $field->conditional_logic ); ?> });
			</script>
			<?php

		endif;
	}

	public static function attribute_html( $attributes ) {

		$attributes_output = '';

		foreach ( $attributes as $key => $value ) {
			$attributes_output .= ' ' . $key . '="' . esc_attr( $value ) . '"';
		}

		return $attributes_output;
	}

}