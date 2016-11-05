<?php

namespace Advanced_Custom_Post_Types\Admin {

	use Advanced_Custom_Post_Types\Load_Main;
	use Advanced_Custom_Post_Types\Load_Base;
	use Advanced_Custom_Post_Types\Settings;

	class Load extends Load_Base {

		private $settings;
		private $loader;
		private $fields;
		private $post_type;

		public function __construct(
			Load_Main $loader,
			Settings $settings,
			Fields $fields,
			Post_Type $post_type
		) {

			$this->loader    = $loader;
			$this->settings  = $settings;
			$this->fields    = $fields;
			$this->post_type = $post_type;

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
				'hierarchical'    => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => false,
				'show_in_menu'    => false
			) );

			$this->add_actions( array(
				'admin_notices',
				'admin_head',
				'save_post',
				'add_meta_boxes',
				'admin_menu',
				'wp_ajax_advanced_custom_post_types'
			) );

			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
			add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ), 10, 1 );
			add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );

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

				Notices::remove_all();
			}
		}

		/**
		 * action callback
		 */
		public function admin_head() {

			?>
			<style>
				/* dashboard_right_now */
				<?php foreach ( $this->loader->get_post_types() as $post_type )
				{
					if ( $post_type['args']['public'] )
					{
				?>
				#dashboard_right_now .<?php echo $post_type['post_type']; ?>-count a:before {
					content: "\f<?php echo $post_type['args']['dashicon_unicode_number']; ?>";
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
		 * action callback
		 *
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
			$this->post_type->save( $post );
			add_action( 'save_post', array( $this, 'save_post' ) );

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

		public function wp_ajax_advanced_custom_post_types() {

			if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'advanced_custom_post_types' ) ) {
				exit( "No naughty business please" );
			}

			if ( isset( $_REQUEST['export'] ) ) {
				$this->export( $_REQUEST['export'] );
			}
		}

		public function export( $post_id ) {

			header( "Content-Type: text/plain" );

			$export_post = get_post( $post_id );

			$post_data = json_decode( $export_post->post_content, true );

			$args = var_export( $post_data['args'], 1 );

			$function_name = "init_register_post_type_{$post_data['post_type']}";

			echo "/* Exported from Advanced_Custom_Post_Types */
			
add_action( 'init', '$function_name' );

function $function_name(){

register_post_type( '{$post_data['post_type']}', {$args});

}";
			exit;

		}

		public function enter_title_here( $title ) {

			$screen = get_current_screen();

			$post_types = $this->loader->get_post_types();

			if ( array_key_exists( $screen->post_type, $post_types ) ) {

				$args = $post_types[ $screen->post_type ]['args'];

				$name = strtolower( $args['singular_name'] );

				$title = "Enter $name name";
			}

			return $title;
		}

		/**
		 * @param $actions
		 * @param $post
		 *
		 * @return mixed
		 */
		public function post_row_actions( $actions, $post ) {

			if ( $post->post_type === ACPT_POST_TYPE ) {

				$nonce = wp_create_nonce( 'advanced_custom_post_types' );
				$url   = admin_url( "admin-ajax.php?action=advanced_custom_post_types&nonce=$nonce&export={$post->ID}" );

				$actions['export_php'] = "<a href=\"$url\" target=\"_blank\">Export</a>";
			}

			return $actions;
		}

		/**
		 * @param $items
		 *
		 * @return mixed
		 */
		public function dashboard_glance_items( $items ) {

			foreach ( $this->loader->get_post_types() as $post_data ) {

				if ( $post_data['args']['public'] ) {

					$num_posts = wp_count_posts( $post_data['post_type'] );

					$published = intval( $num_posts->publish );

					$text = _n( '%s ' . $post_data['args']['singular_name'], '%s ' .
					                                                         $post_data['args']['plural_name'], $published );

					$text = sprintf( $text, number_format_i18n( $published ) );

					$can_view = current_user_can( $this->settings->get( 'capability' ) );

					echo '<li class="post-count ' . $post_data['post_type'] . '-count">';

					if ( $can_view ) {
						echo '<a href="edit.php?post_type=' . $post_data['post_type'] . '">';
					}

					echo $text;

					if ( $can_view ) {
						echo '</a>';
					}

					echo '</li>';
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

			return $messages;
		}

	}
}
