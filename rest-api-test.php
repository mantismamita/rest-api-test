<?php
/**
 * Plugin Name: REST API Test
 * Version: 0.1-alpha
 * Description: Test functions for using the WP REST API
 * Author: Kirsten Cassidy
 * Author URI: kirstencassidy.com
 * Plugin URI: github.com/mantismamita/
 * Text Domain: rest-api-test
 * Domain Path: /languages
 * @package REST API Test
 */

define('RSTTST_PATH', plugin_dir_path(__FILE__));

if(is_admin()){

	include( RSTTST_PATH . 'admin/scripts.php');

}


function rsttst_load_styles() {

	if ( ! is_admin() ) {
		wp_enqueue_style( 'rest-styles', plugins_url( 'assets/rest-styles.css', __FILE__ ) );
		wp_enqueue_script( 'rest-scripts', plugins_url( 'assets/rest-scripts.js', __FILE__ ), array( 'jquery' ), 1.0 );
	}
}
add_action('wp_enqueue_scripts', 'rsttst_load_styles');

function rsttst_add_menu_page() {
	add_menu_page('REST Test', 'REST Test', 'edit_pages', 'rest-test', 'rsttst_render_admin', '', 77);
	add_management_page('REST Settings', 'REST Settings', 'Settings', 'edit_pages', 'rsttst_render_admin_settings');
}

add_action( 'admin_menu', 'rsttst_add_menu_page' );

function rsttst_render_admin(){
	echo 'This is the plugin top level page';
}

function rsttst_render_admin_settings(){
	echo 'This is the "Settings" page';
}




class RSTTST  {
	/**
	 * @var Singleton The reference to *Singleton* instance of this class
	 */
	public static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function getInstance()
	{
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct()
	{
	}


	private function load(){
		add_action('admin_notices', array($this, 'admin_notice'));
	}

	private function admin_notice(){
		echo '<div class="updated"><p>'. __('Here is a notice', 'rest-test'). '</p></div>';
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup()
	{
	}

	private function ajax(){
		add_action( 'wp_ajax_import_post', array( $this, 'import_posts' ));
		add_action( 'wp_ajax_nopriv_import_post', array($this, 'import_posts'));
	}


	public function import_posts() {
		$response = wp_remote_get( 'http://deep-thoughts.dev/wp-json/wp/v2/posts/' );
		if( is_wp_error( $response ) ) {
			return;
		}
		$post_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( compare_keys() ) {
			insert_or_update( $post_data );
		}

		wp_die();

	}

	public function get_remote_posts() {

		$response = wp_remote_get( 'http://deep-thoughts.dev/wp-json/wp/v2/posts/' );
		if( is_wp_error( $response ) ) {
			return;
		}

		$posts = json_decode( wp_remote_retrieve_body( $response ) );

		if( empty( $posts ) ) {
			return;
		}


		return $posts;
	}

	function insert_or_update($post_data) {

		if ( ! $post_data)
			return false;

		$args = array(
			'meta_query' => array(
				array(
					'key'   => 'post_id',
					'value' => $post_data->id
				)
			),
			'post_type'      => 'post',
			'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'),
			'posts_per_page' => 1
		);

		$post = get_posts( $args );

		$post_id = '';

		if ( $post )
			$post_id = $post[0]->ID;

		$post_post = array(
			'ID'            => $post_id,
			'post_title'    => $post_data->full_name,
			'post_content'  => $post_data->bio,
			'post_type'     => 'post',
			'post_status'   => ( $post ) ? $post[0]->post_status : 'publish'
		);

		$post_id = wp_insert_post( $post_post );

		if ( $post_id ) {
			update_post_meta( $post_id, 'post_id', $post_data->id );

			update_post_meta( $post_id, 'json', addslashes( file_get_contents( 'php://input' ) ) );

			wp_set_object_terms( $post_id, $post_data->tags, 'post_tag' );
		}

		print_r( $post_id );

	}

}

class RSTTST_Child extends RSTTST
{
}

$rstest_obj = RSTTST::getInstance();

$post_list= $rstest_obj->get_remote_posts();
//var_dump($obj === RSTTST::getInstance());             // bool(true)

$rstest_child_obj = RSTTST_Child::getInstance();
//var_dump($anotherObj === RSTTST::getInstance());      // bool(false)

//var_dump($anotherObj === RSTTST_Child::getInstance()); // bool(true)

//modifying WP_Query for posts


function rsttst_the_content_filter($content) {


	$content .= rstest_get_remote_posts();

	return $content;
}

function rsttst_get_remote_posts() {

	$response = wp_remote_get( 'http://deep-thoughts.dev/wp-json/wp/v2/posts/1' );
	if( is_wp_error( $response ) ) {
		return;
	}

	$posts = json_decode( wp_remote_retrieve_body( $response ) );

	if( empty( $posts ) ) {
		return;
	}


	return $posts;
}

//var_dump($rstest_obj);

//add_filter( 'the_content', 'rsttst_the_content_filter' );