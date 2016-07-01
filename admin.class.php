<?php

class acpt_admin
{
	private $post_type = 'acpt_content_type';

	private $post_types_info = null;

	public function __construct( $post_types_info )
	{
		$this->post_types_info = $post_types_info;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'acf/init', array( $this, 'acf_init' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'delete_post', array( $this, 'delete_post' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_filter( 'acf/load_field/name=acpt_taxonomies', array( $this, 'acf_load_field_name_acpt_taxonomies' ) );
		add_filter( 'acf/load_field/name=acpt_menu_icon', array( $this, 'acf_load_field_name_acpt_menu_icon' ) );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ), 10, 1 );

	}

	public function admin_head()
	{
		echo '<style>';

		foreach ( $this->post_types_info as $post_type_info )
		{
			print_r( $post_type_info, 1 );

			echo '#dashboard_right_now .' . $post_type_info['post_type'] . '-count a:before {
                    content: "\f' . $post_type_info['menu_icon_unicode_number'] . '";
				}';
		}

		echo '</style>';

	}

	public function dashboard_glance_items( $items )
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

	public function admin_init()
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

	public function acf_init()
	{
		require_once dirname( __FILE__ ) . '/admin-custom-fields.php';
	}

	public function post_updated_messages( $messages )
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

	public function admin_enqueue_scripts( $hook )
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

	public function admin_menu()
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

	public function save_post()
	{
		global $post;

		if (
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ! $post
			|| $this->post_type !== $post->post_type
			|| 'trash' == $post->post_status
		)
		{
			log_acpt( 'save_post skipped', $post );

			return;
		}

		$post_data = $this->get_post_data( $post );

		remove_action( 'save_post', array( $this, 'save_post' ) );

		wp_update_post( array(
			'ID'          => $post->ID,
			'post_title'  => $post_data->plural_name,
			'post_name'   => $post_data->post_name,
			'post_status' => $post_data->error ? 'draft' : 'publish'
		) );

		add_action( 'save_post', array( $this, 'save_post' ) );

		if ( $post_data->args['labels'] && is_array( $post_data->args['labels'] ) )
		{
			foreach ( $post_data->args['labels'] as $name => $value )
			{
				update_post_meta( $post->ID, 'acpt_label_' . $name, $value );
			}
		}

		if ( ! $post_data->error )
		{
			log_acpt( '$post_data saved', $post_data );
			update_option( $post_data->post_name, json_encode( $post_data ) );
		}
		else
		{
			log_acpt( '$post_data not saved', $post_data->error );
			$this->add_notice( $post_data->error, 'error', false );
		}

	}

	public function delete_post()
	{
		global $post;

		if ( ! $post || $this->post_type !== $post->post_type )
		{
			return;
		}

		pre( $post );

	}

	public function acf_load_field_name_acpt_taxonomies( $field )
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

	private $dashicons = false;

	public function get_dashicons()
	{
		if ( ! $this->dashicons )
		{
			$dashicons =
				(array) json_decode( file_get_contents( dirname( __FILE__ ) . '/dashicons.json' ) );

			$this->dashicons = array();

			foreach ( $dashicons as $dashicon )
			{
				$this->dashicons[ $dashicon->class ] = $dashicon->content;
			}
		}

		return $this->dashicons;

	}

	public function acf_load_field_name_acpt_menu_icon( $field )
	{
		$field['choices'] = array(
			'' => 'Select Icon'
		);

		foreach ( $this->get_dashicons() as $class => $unicode )
		{
			$field['choices'][ 'dashicons-' . $class ] = $class;
		}

		return $field;
	}

	/**
	 * @param $post
	 *
	 * @return object
	 */
	public function get_post_data( $post )
	{
		global $wpdb;

		$acpt_fields = array(
			'supports',
			'taxonomies',
			'plural_name',
			'singular_name',
			'description',
			'hierarchical',
			'auto_generate_labels',
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

		$post_data = (object) array(
			'post_type'                => '',
			'plural_name'              => '',
			'singular_name'            => '',
			'args'                     => array(),
			'taxonomies'               => array(),
			'menu_icon_unicode_number' => 0,
			'valid'                    => null,
			'saved'                    => time()
		);

		$post_meta = array();

		if ( 'get post_meta' )
		{
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
		WHERE $wpdb->posts.ID = $post->ID
		AND $wpdb->posts.post_type = 'acpt_content_type' 
		AND $wpdb->posts.post_status = 'publish';";

			$post_meta = $wpdb->get_row( $sql, ARRAY_A );
		}

		$post_data->plural_name   = $post_meta['plural_name'];
		$post_data->singular_name = $post_meta['singular_name'];
		$post_data->post_type     = str_replace( '-', '_', sanitize_title( $post_meta['singular_name'] ) );
		$post_data->post_name     = 'acpt_post_type_' . $post_data->post_type;
		$post_data->error         = '';

		$unique_metas = array(
			'singular_name' => 'singular name',
			'plural_name'   => 'plural name'
		);

		foreach ( $unique_metas as $meta_key => $meta_title )
		{
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT" . " COUNT(*) 
				FROM $wpdb->posts as posts
				LEFT JOIN $wpdb->postmeta as postmeta ON postmeta.post_id = posts.ID
				AND postmeta.meta_key = 'acpt_$meta_key'
				WHERE 1 = 1
				AND posts.post_type = 'acpt_content_type'
				AND posts.post_status = 'publish'
				AND postmeta.meta_value = %s;", $post_data->$meta_key ) );

			if ( $count > 1 )
			{
				$post_data->error =
					"Another post type has the same label '{$post_data->$meta_key}'. Please change the 
					$meta_title and save again.";

				return $post_data;
			}
		}

		$post_meta['label']    = $post_meta['plural_name'];
		$post_meta['supports'] = unserialize( $post_meta['supports'] );

		$post_meta['labels'] = array(
			'name'          => ucwords( $post_meta['plural_name'] ),
			'singular_name' => ucwords( $post_meta['singular_name'] )
		);

		if ( $post_meta['auto_generate_labels'] )
		{
			$post_meta['labels'] = $this->generate_labels(
				$post_meta['labels']['name'],
				$post_meta['labels']['singular_name'],
				$post_meta['labels'] );
		}
		else
		{
			foreach ( $post_meta as $field_name => $field_value )
			{
				if ( 'label_' === substr( $field_name, 0, 6 ) )
				{
					$post_meta['labels'][ substr( $field_name, 6 ) ] = $field_value;
					unset( $post_meta[ $field_name ] );
				}
			}

		}

		if ( $post_meta['show_in_admin_menu_under_parent'] )
		{
			$post_meta['show_in_menu'] = $post_meta['show_in_admin_menu_under_parent'];
		}
		else
		{
			// could be a string so cast to bool if 1 or 0
			$post_meta['show_in_menu'] = (bool) $post_meta['show_in_menu'];
		}

		$post_data->taxonomies = unserialize( $post_meta['taxonomies'] );

		unset(
			$post_meta['ID'],
			$post_meta['plural_name'],
			$post_meta['singular_name'],
			$post_meta['auto_generate_labels'],
			$post_meta['taxonomies'],
			$post_meta['show_in_admin_menu_under_parent']
		);

		$post_meta['menu_position'] = intval( $post_meta['menu_position'] ) . '.17574474777';

		$dashicons = $this->get_dashicons();

		if ( 10 <= strlen( $post_meta['menu_icon'] )
		     || ! isset( $dashicons[ substr( $post_meta['menu_icon'], 10 ) ] )
		)
		{
			$post_meta['menu_icon'] = 'dashicons-admin-page';
		}

		$post_data->args = $post_meta;

		$post_data->menu_icon_unicode_number = $dashicons[ substr( $post_meta['menu_icon'], 10 ) ];

		$post_data->saved = time();

		// echo '<pre>'; print_r( $post_data );

		return $post_data;
	}

	/**
	 * generates all labels based on the plural and singular names
	 *
	 * @param $plural_name
	 * @param $singular_name
	 * @param $labels
	 *
	 * @return array
	 */
	public static function generate_labels( $plural_name, $singular_name, $labels = array() )
	{
		return array_merge(
			(array) $labels,
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

	/**
	 * adds a notice to set of saved notices
	 *
	 * @param $message
	 * @param string $type
	 * @param bool $is_dismissible
	 */
	function add_notice( $message, $type = 'info', $is_dismissible = true )
	{
		$notices = $this->get_notices();

		$notices[] = (object) compact( 'message', 'type', 'is_dismissible' );

		$this->set_notices( $notices );
	}

	/**
	 * gets saved notices
	 * @return array
	 */
	function get_notices()
	{
		$notices = (array) json_decode( get_option( 'acpt_admin_notices_' . get_current_user_id() ) );

		return $notices;
	}

	/**
	 * saves notices to be later displayed
	 *
	 * @param $notices
	 */
	function set_notices( $notices )
	{
		if ( $notices === false )
		{
			delete_option( 'acpt_admin_notices_' . get_current_user_id() );
		}
		else
		{
			update_option( 'acpt_admin_notices_' . get_current_user_id(), json_encode( $notices ) );
		}

		return;
	}

	/**
	 * Displays admin notices
	 */
	function admin_notices()
	{
		$notices = $this->get_notices();

		if ( count( $notices ) )
		{
			foreach ( $notices as $notice )
			{
				printf(
					'<div class="%1$s"><p>%2$s</p></div>',
					'notice notice-' . $notice->type . ( $notice->is_dismissible ? ' is-dismissible' : '' ),
					$notice->message
				);
			}

			$this->set_notices( false );
		}
	}
}

function log_acpt( $message = '', $data = null )
{
	global $wpdb;

	$debug_backtrace = debug_backtrace();

	$caller = array_shift( $debug_backtrace );

	$wpdb->insert(
		'log_acpt',
		array(
			'message' => $message . "\r\n" . print_r( $data, 1 ),
			'file'    => $caller['file'] . ' on line ' . $caller['line']
		),
		array(
			'%s',
			'%s'
		)
	);
}