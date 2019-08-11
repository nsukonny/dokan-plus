<?php

/*
Plugin Name: Dokan Plus
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Plugin for add some custom fields to Dokan
Version: 1.0.1
Author: nickolaysukonny
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

Class DokanPlus {
	public $version = '1.0.1';
	private $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
		add_action( 'wp_enqueue_scripts', array( $this, 'add_plus_scripts' ) );

		add_action( 'show_user_profile', array( $this, 'add_plus_fields' ), 21 );
		add_action( 'edit_user_profile', array( $this, 'add_plus_fields' ), 21 );
		add_action( 'personal_options_update', array( $this, 'save_plus_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_plus_fields' ) );

		add_filter( 'dokan_settings_form_bottom', array( $this, 'extra_fields_plus' ), 12, 2 );
		add_action( 'dokan_store_profile_saved', array( $this, 'save_extra_fields_plus' ), 15 );

		add_action( 'show_user_profile', array( $this, 'add_google_embed_field' ), 21 );
		add_action( 'edit_user_profile', array( $this, 'add_google_embed_field' ), 21 );

		add_filter( 'dokan_settings_form_bottom', array( $this, 'extra_fields_plus_google' ), 1, 2 );
		add_action( 'dokan_store_profile_saved', array( $this, 'save_extra_fields_plus_google' ), 15 );
	}

	public static function init( $wpdb ) {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new DokanPlus( $wpdb );
		}

		return $instance;
	}

	private function getDb() {
		include "WP-ROOT-PATH/wp-config.php";
		$db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
		if ( mysqli_connect_errno() ) {
			return null;
		}

		return $db;
	}

	function add_plus_fields( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! user_can( $user, 'dokandar' ) ) {
			return;
		}

		$dokan_settings      = dokan_get_store_info( $user->ID );
		$seller_locations    = $dokan_settings['seller_locations'];
		$kas_store_info      = dokan_get_store_info( $user->data->ID );
		$classification      = isset( $kas_store_info['address']['classification'] ) ? $kas_store_info['address']['classification'] : '';
		$ignorestate         = isset( $kas_store_info['address']['ignorestate'] ) ? $kas_store_info['address']['ignorestate'] : 'no';
		$ignorecity          = isset( $kas_store_info['address']['ignorecity'] ) ? $kas_store_info['address']['ignorecity'] : 'no';
		$ignorecategory      = isset( $kas_store_info['address']['ignorecategory'] ) ? $kas_store_info['address']['ignorecategory'] : 'no';
		$store_manufacturers = get_user_meta( $user->ID, 'dokan_store_manufacturers', true );
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php esc_html_e( 'Show in all states', 'dokan-plus' ); ?></th>
                <td>
                    <label for="ignorestate">
                        <input type="hidden" name="dokan_store_address[ignorestate]" value="no">
                        <input name="dokan_store_address[ignorestate]" type="checkbox" id="ignorestate"
                               value="yes" <?php checked( $ignorestate, 'yes' ); ?> />
						<?php esc_html_e( 'Show in all states', 'dokan-plus' ); ?>
                    </label>

                    <p class="description"><?php esc_html_e( 'Show this vendor in all states', 'dokan-plus' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Show in all cities', 'dokan-plus' ); ?></th>
                <td>
                    <label for="ignorecity">
                        <input type="hidden" name="dokan_store_address[ignorecity]" value="no">
                        <input name="dokan_store_address[ignorecity]" type="checkbox" id="ignorecity"
                               value="yes" <?php checked( $ignorecity, 'yes' ); ?> />
						<?php esc_html_e( 'Show in all cities', 'dokan-plus' ); ?>
                    </label>

                    <p class="description"><?php esc_html_e( 'Show this vendor in all cities', 'dokan-plus' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Show in all categories', 'dokan-plus' ); ?></th>
                <td>
                    <label for="ignorecategory">
                        <input type="hidden" name="dokan_store_address[ignorecategory]" value="no">
                        <input name="dokan_store_address[ignorecategory]" type="checkbox" id="ignorecategory"
                               value="yes" <?php checked( $ignorecategory, 'yes' ); ?> />
						<?php esc_html_e( 'Show in all categories', 'dokan-plus' ); ?>
                    </label>

                    <p class="description"><?php esc_html_e( 'Show this vendor in all categories', 'dokan-plus' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Classification', 'dokan-plus' ); ?></th>
                <td>
                    <input type="text" name="dokan_store_address[classification]" id="classification"
                           class="regular-text"
                           value="<?php echo esc_attr( $classification ); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Locations', 'dokan-plus' ); ?></th>
                <td>
                    <textarea class="regular-text"
                              name="seller_locations"><?php echo esc_attr( $seller_locations ); ?>
                        </textarea>
                </td>
            </tr>
            </tbody>
        </table>

		<?php
		$plus_classifications = $this->get_classifications();
		if ( $plus_classifications != null ) {
			?>
            <script>
                $(function () {
                    var availableClassifications = [
						<?php
						foreach ( $plus_classifications as $classification ) {
							echo '"' . $classification->meta_value . '", ';
						}
						?>
                    ];
                    $("#classification").autocomplete({
                        source: availableClassifications
                    });
                });
            </script>
			<?php
		}
	}

	function get_classifications() {
		global $wpdb;
		$classifications = $wpdb->get_results( "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = 'classification' GROUP BY meta_value" );

		return count( $classifications ) ? $classifications : null;
	}

	function save_plus_fields( $user_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$post_data = wp_unslash( $_POST );

		if ( isset( $post_data['dokan_update_user_profile_info_nonce'] )
		     && ! wp_verify_nonce( $post_data['dokan_update_user_profile_info_nonce'], 'dokan_update_user_profile_info' ) ) {
			return;
		}

		if ( ! isset( $post_data['dokan_enable_selling'] ) ) {
			return;
		}

		$dokan_settings = dokan_get_store_info( $user_id );

		if ( isset( $post_data['seller_locations'] ) ) {
			$dokan_settings['seller_locations'] = $post_data['seller_locations'];
		}

		if ( isset( $post_data['dokan_store_address']['classification'] ) ) {
			//copy dokan_store_address[country] for speed up find list
			update_user_meta( $user_id, "classification", $post_data['dokan_store_address']['classification'] );
		}

		if ( isset( $post_data['seller_embed_google'] ) ) {
			$dokan_settings['seller_embed_google'] = $post_data['seller_embed_google'];
		}

		update_user_meta( $user_id, 'dokan_profile_settings', $dokan_settings );
	}

	function add_plus_scripts() {
		$plugin_url = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'dokan-plus-style', $plugin_url . 'dokan-plus.css' );
		wp_enqueue_script( 'dokan-plus-script', $plugin_url . 'dokan-plus.js', array( 'jquery' ), '1.0', true );
	}

	function extra_fields_plus( $current_user, $profile_info ) {
		$seller_locations = isset( $profile_info['seller_locations'] ) ? $profile_info['seller_locations'] : '';
		$classification   = isset( $profile_info['address']['classification'] ) ? $profile_info['address']['classification'] : '';
		$ignorestate      = isset( $profile_info['address']['ignorestate'] ) ? $profile_info['address']['ignorestate'] : 'no';
		$ignorecity       = isset( $profile_info['address']['ignorecity'] ) ? $profile_info['address']['ignorecity'] : 'no';
		$ignorecategory   = isset( $profile_info['address']['ignorecategory'] ) ? $profile_info['address']['ignorecategory'] : 'no';


		?>
        <div class="gregcustom dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="reg_seller_about">
				<?php _e( 'Locations', 'dokan-plus' ); ?>
            </label>
            <div class="dokan-w5">
                <textarea class="dokan-form-control input-md valid" name="seller_locations"
                          id="reg_seller_locations"><?php echo $seller_locations; ?></textarea>
                <input type="hidden" name="dokan_address[classification]"
                       value="<?php echo esc_attr( $classification ); ?>">
                <input type="hidden" name="dokan_address[ignorestate]"
                       value="<?php echo $ignorestate == 'yes' ? 'yes' : 'no'; ?>"/>
                <input type="hidden" name="dokan_address[ignorecity]"
                       value="<?php echo $ignorecity == 'yes' ? 'yes' : 'no'; ?>"/>
                <input type="hidden" name="dokan_address[ignorecategory]"
                       value="<?php echo $ignorecategory == 'yes' ? 'yes' : 'no'; ?>"/>
            </div>
        </div>
		<?php
	}

	function save_extra_fields_plus( $store_id ) {
		$dokan_settings = dokan_get_store_info( $store_id );
		if ( isset( $_POST['seller_locations'] ) ) {
			$dokan_settings['seller_locations'] = $_POST['seller_locations'];
		}
		update_user_meta( $store_id, 'dokan_profile_settings', $dokan_settings );
	}

	function add_google_embed_field( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! user_can( $user, 'dokandar' ) ) {
			return;
		}

		$dokan_settings = dokan_get_store_info( $user->ID );
		$seller_google  = $dokan_settings['seller_embed_google'];
		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
					<?php _e( '<a 
                                    href="https://developers.google.com/maps/documentation/embed/start?hl=uk" 
                                    target="_blank">Google map embed code</a> (just src)', 'dokan-plus' ); ?>
                </th>
                <td>
                    <input type="text" name="seller_embed_google" class="regular-text"
                           value="<?php echo esc_attr( $seller_google ); ?>">
                </td>
            </tr>
            </tbody>
        </table>
		<?php
	}
}

function starter_dokan_plus() {
	global $wpdb;

	return DokanPlus::init( $wpdb );
}

starter_dokan_plus();