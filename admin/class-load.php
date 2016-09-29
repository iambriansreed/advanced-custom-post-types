<?php

namespace Advanced_Custom_Post_Types\Admin {

	use Advanced_Custom_Post_Types\Load_Base;
	use Advanced_Custom_Post_Types\Settings;
	use Advanced_Custom_Post_Types\Post_Types;

	class Load extends Load_Base {

		private $settings;
		private $post_types;
		private $fields;
		private $field_groups;
		private $post_type_manage;

		public function __construct(
			Settings $settings,
			Post_Types $post_types,
			Fields $fields,
			Field_Groups $field_groups,
			Post_Type_Manage $post_type_manage
		) {

			$this->settings     = $settings;
			$this->post_types   = $post_types;
			$this->fields       = $fields;
			$this->field_groups = $field_groups;
			$this->post_type_manage = $post_type_manage;

			$cap = $settings->get( 'capability' );

			register_post_type( ACPT_POST_TYPE, array(
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
				'public'          => true,
				'show_ui'         => true,
				'_builtin'        => false,
				'capability_type' => 'post',
				'capabilities'    => array(
					'edit_post'    => $cap,
					'delete_post'  => $cap,
					'edit_posts'   => $cap,
					'delete_posts' => $cap,
				),
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => array(),
				'show_in_menu'    => false
			) );

			$this->add_actions( array( 'admin_notices', 'admin_head', 'admin_footer', 'save_post', 'add_meta_boxes' ) );

			add_action( 'admin_menu', array( $this, 'admin_menu' ), 999 );

			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
			add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ), 10, 1 );
		}

		/**
		 * action callback
		 */
		public function add_meta_boxes() {

			if ( ACPT_POST_TYPE !== get_post_type() ) {
				return;
			}

			new Meta_Boxes( $this->fields );
		}

		/**
		 * action callback
		 */
		public function admin_head() {

			?>
			<style>
				/* dashboard_right_now */
				<?php foreach ( $this->post_types as $post_type )
				{
					if ( $post_type['args']['public'] )
					{
				?>
				#dashboard_right_now .<?php echo $post_type['post_type'] ;?>-count a:before {
					content: "\f<?php echo $post_type['dashicon_unicode_number']; ?>";
				}

				<?php
					}
				}
				?>
			</style>
			<script>
				var acpt;
				acpt = new function acptClass() {

					return {
						conditional_logic: new List()
					};

					function List() {

						var items = [];
						this.Add = function addItem(item) {
							items.push(item);
						};
						this.Items = function getItems() {
							return jQuery.extend({}, items);
						};
					}

				};
			</script>
			<?php
		}

		/**
		 *
		 */
		public function admin_footer() {
		}

		/**
		 * @param $items
		 *
		 * @return mixed
		 */
		public function dashboard_glance_items( $items ) {

			foreach ( $this->post_types as $post_type ) {

				if ( $post_type['args']['public'] ) {

					$type = $post_type['post_type'];

					$num_posts = wp_count_posts( $type );

					$published = intval( $num_posts->publish );

					$post_type = get_post_type_object( $type );

					$text = _n( '%s ' . $post_type->args->labels->singular_name, '%s ' . $post_type->args->labels->name, $published );

					$text = sprintf( $text, number_format_i18n( $published ) );

					if ( current_user_can( $post_type->args->cap->edit_posts ) ) {

						echo '<li class="post-count ' . $post_type->args->labels->name . '-count">' . '<a href="edit.php?post_type=' . $post_type->name . '">' . $text . '</a>' . '</li>';
					} else {
						echo '<li class="post-count ' . $post_type->args->labels->name . '-count">' . '<span>' . $text . '</span>' . '</li>';
					}
				}

				//$items[] = $output;
			}

			return $items;
		}

		/**
		 * @param $messages
		 */
		public function post_updated_messages( $messages ) {

			global $post;

			$messages[ ACPT_POST_TYPE ] = array(
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

		/**
		 * action callback
		 */
		public function admin_menu() {

			if ( $this->settings->get( 'show_admin' ) ) {

				$slug = 'edit.php?post_type=' . ACPT_POST_TYPE;

				$capability = $this->settings->get( 'capability' );

				add_menu_page( __( "Content Types", 'acpt' ), __( "Content Types", 'acpt' ),
					$capability, $slug, '',
					'dashicons-feedback',
					'81.026' );

				add_submenu_page( $slug, __( 'Content Types', 'acpt' ), __( 'Content Types', 'acpt' ),
					$capability,
					$slug );

				add_submenu_page( $slug,
					__( 'Add New', 'acpt' ),
					__( 'Add New', 'acpt' ),
					$capability,
					'post-new.php?post_type=' . ACPT_POST_TYPE
				);
			}
		}

		/**
		 * @param $post_id
		 */
		public function save_post( $post_id ) {

			global $post;

			$is_doing_autosave = defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE;
			$is_acpt_post_type = ACPT_POST_TYPE === (string) get_post_type( $post_id );
			$is_published      = 'publish' === (string) get_post_status( $post_id );

			if ( ! $is_acpt_post_type ) {
				// not an post type to edit

				return;
			} else if ( $is_doing_autosave || ! $is_published ) {
				// is a post type to edit but it's an autosave or not published

				// post has been saved before
				if ( is_a( $post, 'WP_Post' ) ) {
					delete_option( $post->post_name );
				}

				return;
			}

			remove_action( 'save_post', array( $this, 'save_post' ) );
			$this->post_type_manage->save( $post );
			add_action( 'save_post', array( $this, 'save_post' ) );

		}

		/**
		 * @param $singular_name
		 *
		 * @return mixed
		 */
		public static function sanitize_post_type( $singular_name ) {
			return str_replace( '-', '_', sanitize_title( $singular_name ) );
		}

		/**
		 * is meta value meta key combination unique
		 *
		 * @param $post_id
		 * @param $field_name
		 * @param $value
		 *
		 * @return bool
		 * @internal param $key
		 */
		public static function is_unique( $post_id, $field_name, $value ) {

			global $wpdb;

			$sql = $wpdb->prepare(
				"SELECT" . " COUNT(*) 
			FROM $wpdb->posts as posts
			LEFT JOIN $wpdb->postmeta as postmeta ON postmeta.post_id = posts.ID
			AND postmeta.meta_key = %s
			WHERE 1 = 1
			AND posts.ID != %d
			AND posts.post_type = 'acpt_content_type'
			AND posts.post_status = 'publish'
			AND postmeta.meta_value = %s; ", "acpt_$field_name", $post_id, $value );

			return 0 === intval( $wpdb->get_var( $sql ) );
		}

		/**
		 * generate all labels based on the plural and singular names
		 *
		 * @param $plural_name
		 * @param $singular_name
		 *
		 * @return array
		 * @internal param $labels
		 *
		 */
		public static function generate_labels( $plural_name, $singular_name ) {

			return array(
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
			);
		}

		/**
		 * action callback: display all user's notices
		 */
		public function admin_notices() {

			$notices = Notices::get_all();

			if ( count( $notices ) ) {

				foreach ( $notices as $notice ) {
					printf(
						'<div class="%1$s"><p>%2$s</p></div>',
						'notice notice-' . $notice->type . ( $notice->is_dismissible ? ' is-dismissible' : '' ),
						$notice->message
					);
				}

				Notices::set( false );
			}
		}
	}
}
