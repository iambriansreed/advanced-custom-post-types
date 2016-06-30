<?php

class acpt_admin
{
	var $post_type = 'acpt_content_type';

	var $post_types_info = null;

	function __construct( $post_types_info )
	{
		// actions

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'acf/init', array( $this, 'acf_init' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );

		add_filter( 'acf/load_field/name=acpt_taxonomies', array( $this, 'acf_load_field_name_acpt_taxonomies' ) );
		add_filter( 'acf/load_field/name=acpt_menu_icon', array( $this, 'acf_load_field_name_acpt_menu_icon' ) );


		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ), 10, 1 );

		$this->post_types_info = $post_types_info;

	}

	function admin_head()
	{
		echo '<style>';

		foreach ( $this->post_types_info as $post_type_info )
		{
			echo '#dashboard_right_now .' . $post_type_info['post_type'] . '-count a:before {
                    content: "\f' . $post_type_info['menu_icon_unicode_number'] . '";
				}';
		}

		echo '</style>';

	}

	function dashboard_glance_items( $items )
	{
		foreach ( $this->post_types_info as $post_type_info )
		{
			$type = $post_type_info['post_type'];

			if ( ! post_type_exists( $type ) )
			{
				continue;
			}
			$num_posts = wp_count_posts( $type );

			if ( $num_posts )
			{
				$published = intval( $num_posts->publish );

				$post_type = get_post_type_object( $type );

				$text =
					_n( '%s ' . $post_type->labels->singular_name, '%s ' . $post_type->labels->name, $published );

				$text = sprintf( $text, number_format_i18n( $published ) );

				if ( current_user_can( $post_type->cap->edit_posts ) )
				{
					echo '<li class="post-count ' . $post_type->name . '-count">' . '<a href="edit.php?post_type=' . $post_type->name . '">' . $text . '</a>' . '</li>';
				}
				else
				{
					echo '<li class="post-count ' . $post_type->name . '-count">' . '<span>' . $text . '</span>' . '</li>';
				}
			}

			//$items[] = $output;
		}

		return $items;
	}

	function admin_init()
	{
		$cap = acf_get_setting( 'capability' );

		register_post_type( $this->post_type, array(
			'labels'          => array(
				'name'               => __( 'Content Types', 'acpt' ),
				'singular_name'      => __( 'Content Type', 'acpt' ),
				'add_new'            => __( 'Add New', 'acpt' ),
				'add_new_item'       => __( 'Add New Content Type', 'acpt' ),
				'edit_item'          => __( 'Edit Content Type', 'acpt' ),
				'new_item'           => __( 'New Content Type', 'acpt' ),
				'view_item'          => __( 'View Content Type', 'acpt' ),
				'search_items'       => __( 'Search Content Types', 'acpt' ),
				'not_found'          => __( 'No Content Types found', 'acpt' ),
				'not_found_in_trash' => __( 'No Content Types found in Trash', 'acpt' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'_builtin'        => false,
			'capability_type' => 'post',
			'capabilities'    => array(
				'edit_post'    => $cap,
				'delete_post'  => $cap,
				'edit_posts'   => $cap,
				'delete_posts' => $cap,
			),
			'hierarchical'    => true,
			'rewrite'         => false,
			'query_var'       => false,
			'supports'        => array( 'title' ),
			'show_in_menu'    => false
		) );
	}

	function acf_init()
	{
		require dirname( __FILE__ ) . '/admin-custom-fields.php';
	}

	function post_updated_messages( $messages )
	{
		global $post;

		$messages[ $this->post_type ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Content Type updated.' ),
			2  => __( 'Custom field updated.' ),
			3  => __( 'Custom field deleted.' ),
			4  => __( 'Content Type updated.' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Content Type restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Content Type published.' ),
			7  => __( 'Content Type saved.' ),
			8  => __( 'Content Type submitted.' ),
			9  => sprintf(
				__( 'Content Type scheduled for: <strong>%1$s</strong>.' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Content Type draft updated.' )
		);
	}

	function admin_enqueue_scripts( $hook )
	{
		if ( $this->post_type !== get_post_type() )
		{
			return;
		}

		if ( 'post-new.php' === $hook || 'post.php' === $hook )
		{
			wp_enqueue_script( 'advanced_custom_post_types', plugin_dir_url( __FILE__ ) . 'advanced-custom-post-types.js', array( 'jquery' ) );
			wp_enqueue_style( 'advanced_custom_post_types', plugin_dir_url( __FILE__ ) . 'advanced-custom-post-types
				.css' );
		}
	}

	function admin_menu()
	{

		$slug = 'edit.php?post_type=' . $this->post_type;
		$cap  = acf_get_setting( 'capability' );


		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_menu_page( __( "Custom Types", 'acpt' ), __( "Custom Types", 'acpt' ), $cap, $slug, false,
			'dashicons-feedback',
			'81.026' );

		add_submenu_page( $slug, __( 'Content Types', 'acpt' ), __( 'Content Types', 'acpt' ), $cap, $slug );

		add_submenu_page( $slug, __( 'Add New', 'acpt' ), __( 'Add New', 'acpt' ), $cap, 'post-new.php?post_type=' . $this->post_type );

	}

	function save_post()
	{
		global $post;

		if ( ! $post || $this->post_type !== $post->post_type )
		{
			return;
		}

		$plural_name = get_post_meta( $post->ID, 'acpt_plural_name', 1 );

		remove_action( 'save_post', array( $this, 'save_post' ) );

		wp_update_post( array( 'ID' => $post->ID, 'post_title' => $plural_name ) );

		$post_type_meta = $this->get_post_type_meta( $post->ID );

		update_option( 'acpt_post_type_' . $post_type_meta['post_type'], json_encode( $post_type_meta ) );

		add_action( 'save_post', array( $this, 'save_post' ) );

	}

	function acf_load_field_name_acpt_taxonomies( $field )
	{
		$field['choices'] = array();

		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		foreach ( $taxonomies as $value => $taxonomy )
		{
			$field['choices'][ $value ] = $taxonomy->labels->name;
		}

		unset( $field['choices']['post_format'] );

		// return the field
		return $field;
	}

	function acf_load_field_name_acpt_menu_icon( $field )
	{
		$field['choices'] = array(
			'' => 'Select Icon'
		);

		$dashicons =
			(array) json_decode( file_get_contents( dirname( __FILE__ ) . '/dashicons.json' ) );

		foreach ( $dashicons as $icon )
		{
			$field['choices'][ 'dashicons-' . $icon->class ] = $icon->class;
		}

		// return the field
		return $field;
	}


	function get_post_type_meta( $post_id )
	{
		global $wpdb;

		$acpt_fields = array(
			'supports',
			'taxonomies',
			'plural_name',
			'singular_name',
			'description',
			'hierarchical',
			'auto_generate_additional_labels',
			'public',
			'exclude_from_search',
			'publicly_queryable',
			'can_export',
			'show_in_rest',
			'show_in_ui',
			'show_in_menu',
			'show_in_admin_menu_under_parent',
			'show_in_admin_bar',
			'menu_position',
			'menu_icon',
			'label_add_new',
			'label_add_new_item',
			'label_edit_item',
			'label_new_item',
			'label_view_item',
			'label_search_items',
			'label_not_found',
			'label_not_found_in_trash',
			'label_parent_item_colon',
			'label_all_items',
			'label_archives',
			'label_insert_into_item',
			'label_uploaded_to_this_item',
			'label_featured_image',
			'label_set_featured_image',
			'label_remove_featured_image',
			'label_use_featured_image',
			'label_menu_name',
			'label_filter_items_list',
			'label_items_list_navigation',
			'label_items_list',
			'label_name_admin_bar'
		);

		$sql = 'SELECT ';

		foreach ( $acpt_fields as $field )
		{
			$sql .= "\r\nmeta_$field.meta_value AS $field,";
		}

		$sql .= "\r\n$wpdb->posts.ID FROM $wpdb->posts";

		foreach ( $acpt_fields as $field )
		{
			$sql .= "\r\nLEFT JOIN $wpdb->postmeta AS meta_$field 
	ON meta_$field.post_id = $wpdb->posts.ID AND meta_$field.meta_key = 'acpt_$field'";
		}

		$sql .= "
		WHERE $wpdb->posts.ID = $post_id
		AND $wpdb->posts.post_type = 'acpt_content_type' 
		AND $wpdb->posts.post_status = 'publish';";

		$args = $wpdb->get_row( $sql, ARRAY_A );

		$post_type = str_replace( '-', '_', sanitize_title( $args['singular_name'] ) );

		$args['label'] = $args['plural_name'];

		$args['supports'] = unserialize( $args['supports'] );

		$args['labels'] = array(
			'name'          => ucwords( $args['plural_name'] ),
			'singular_name' => ucwords( $args['singular_name'] )
		);

		if ( $args['auto_generate_additional_labels'] )
		{
			$singular_name = $args['labels']['singular_name'];
			$plural_name   = $args['labels']['name'];

			$args['labels'] = array_merge(
				$args['labels'],
				array(
					'add_new'               => 'Add New',
					'add_new_item'          => 'Add New ' . $singular_name,
					'edit_item'             => 'Edit ' . $singular_name,
					'new_item'              => 'New ' . $singular_name,
					'view_item'             => 'View ' . $singular_name,
					'search_items'          => 'Search ' . $plural_name,
					'not_found'             => 'No ' . strtolower( $plural_name ) . ' found',
					'not_found_in_trash'    => 'No ' . strtolower( $plural_name ) . ' found in Trash',
					'parent_item_colon'     => 'Parent ' . $singular_name,
					'all_items'             => 'All ' . $plural_name,
					'archives'              => $plural_name . ' Archives',
					'insert_into_item'      => 'I' . 'nsert into ' . strtolower( $singular_name ),
					'uploaded_to_this_item' => 'Uploaded to this ' . strtolower( $singular_name ),
					'featured_image'        => 'Featured Image',
					'set_featured_image'    => 'Set featured image',
					'remove_featured_image' => 'Remove featured image',
					'use_featured_image'    => 'Use as featured image',
					'menu_name'             => $plural_name,
					'filter_items_list'     => $plural_name,
					'items_list_navigation' => $plural_name,
					'items_list'            => $plural_name,
					'name_admin_bar'        => $singular_name
				)
			);

		}
		else
		{
			foreach ( $args as $field_name => $field_value )
			{
				if ( 'label_' === substr( $field_name, 0, 6 ) )
				{
					$args['labels'][ substr( $field_name, 6 ) ] = $field_value;
					unset( $args[ $field_name ] );
				}
			}

		}

		if ( $args['show_in_admin_menu_under_parent'] )
		{
			$args['show_in_menu'] = $args['show_in_admin_menu_under_parent'];
		}
		else
		{
			// could be a string cast to bool if 1 or 0
			$args['show_in_menu'] = (bool) $args['show_in_menu'];
		}

		$taxonomies = unserialize( $args['taxonomies'] );

		unset(
			$args['ID'],
			$args['plural_name'],
			$args['singular_name'],
			$args['auto_generate_additional_labels'],
			$args['taxonomies'],
			$args['show_in_admin_menu_under_parent']
		);

		$args['menu_position'] = intval( $args['menu_position'] ) . '.17574474777';

		$acpt_menu_icon = get_field( 'acpt_menu_icon', $post_id );

		return array(
			'post_type'                => $post_type,
			'args'                     => $args,
			'taxonomies'               => $taxonomies,
			'menu_icon_unicode_number' => str_replace( array( '&#', ';' ), '', $acpt_menu_icon->unicode ),
			'saved'                    => time()
		);
	}
}


new acpt_admin( $this->post_types_info );