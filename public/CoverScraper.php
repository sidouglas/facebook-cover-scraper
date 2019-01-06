<?php

class CoverScraper {

	private $name = '';
	private $plugin_name = 'facebook-public-cover';

	protected static $instance = null;

	protected function __construct() {
	}

	protected function __clone() {
	}

	public static function getInstance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	public static function run() {
		$instance = self::getInstance();
		add_shortcode( 'fb_public_cover', [ $instance, 'shortcode' ] );
	}

	/**
	 *
	 * @param $atts
	 * @param null $content
	 *
	 */
	public function shortcode( $atts, $content = null ) {
		$url    = '';
		$src    = null;
		$expiry = null;

		extract( shortcode_atts( array(
			'url'    => '',
			'expiry' => '',
		), $atts ) );

		if ( $url ) {
			$this->set_name( $url );
		}

		$this->refresh_saved_images( $url, $expiry );

		echo $this->get_public_cover_file_path( $url );
	}

	public function get_public_cover_file_path( $url ) {
		if ( ! ( $src = $this->has_scraped() ) ) {
			$src = $this->scrape_fb( $url );
		}

		return $src;
	}

	/**
	 * @param $url
	 * @param null $expiry
	 *
	 * @return bool|string
	 * @throws Exception
	 */
	public function get_public_cover_src( $url, $expiry = null ) {
		$this->refresh_saved_images( $url, $expiry );

		$src = $this->get_public_cover_file_path( $url );

		if ( $src ) {
			return wp_upload_dir()['baseurl'] . '/' . $this->plugin_name . '/' . basename( $src );
		}

		return false;
	}

	/**
	 *
	 * @param $url string
	 * @param null $expiry
	 *
	 * @return bool
	 * @throws Exception
	 */
	protected function refresh_saved_images( $url, $expiry = null ) {
		$this->set_name( $url );

		if ( ! $expiry ) {
			return false;
		}

		if ( $image = $this->has_scraped() ) {
			$file_part = (int) trim( str_replace( $this->get_name() . '-', '', pathinfo( $image )['filename'] ) );

			$compare = new DateTime( '-' . $expiry );

			// we don't know what the user has entered in as $expiry param so bail
			if ( ! $compare ) {
				return false;
			}

			$file_date = new DateTime();
			$file_date->setTimestamp( $file_part );

			if ( $compare >= $file_date ) {
				wp_delete_file( $image );

				return true;
			}
		}

		return false;
	}

	protected function get_name() {
		return $this->name;
	}

	protected function set_name( $name ) {
		$this->name = strtolower( basename( $name ) );
	}

	protected function get_wp_upload_dir() {
		$path = wp_upload_dir()['basedir'] . '/' . $this->plugin_name;
		wp_mkdir_p( $path );

		return trailingslashit( $path );
	}

	protected function has_scraped() {
		foreach ( glob( $this->get_wp_upload_dir() . '*.{jpg,jpeg,png,gif}', GLOB_BRACE ) as $image ) {
			if ( strpos( $image, $this->get_name() ) !== false ) {
				return $image;
			}
		}

		return false;
	}

	protected function scrape_fb( $url ) {

		$have_image = false;

		$content = $this->curl_file( $url );

		$cover_url = $this->extract_cover_url( $content );

		if ( $cover_url ) {
			$have_image = $this->normalize_string( $cover_url );
		}

		if ( $have_image ) {
			$now  = date_timestamp_get( date_create() );
			$path = $this->get_wp_upload_dir() . "{$this->get_name()}-{$now}.jpg";
			$this->save_image( $have_image, $path );

			return $path;
		}

		return false;
	}

	/**
	 * extract_cover_url
	 * here is where the fun begins.
	 *
	 * @param $content
	 *
	 * @return bool|string
	 */
	protected function extract_cover_url( $content ) {
		$explode_on = false;
		$regex      = null;

		if ( strpos( $content, 'coverPhotoData' ) !== false ) {
			$regex      = '/uri"\s*:\s*\"([^"]+)/';
			$explode_on = 'coverPhotoData';
		} else if ( strpos( $content, 'coverPhotoImg' ) !== false ) {
			$regex      = '/src=\"([^"]+)/';
			$explode_on = 'coverPhotoImg';
		}
		if ( $explode_on ) {
			$parts = explode( $explode_on, $content );
			preg_match( $regex, $parts[1], $matches );

			if ( is_array( $matches ) ) {
				return htmlspecialchars_decode( $matches[1] );
			}
		}

		return false;
	}

	protected function normalize_string( $string ) {
		return str_replace( '\/', '/', $string );
	}

	/**
	 * @param $url string
	 * @param $saveto string
	 */
	public static function save_image( $url, $saveto ) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_BINARYTRANSFER, 1 );
		$raw = curl_exec( $ch );
		curl_close( $ch );
		if ( file_exists( $saveto ) ) {
			unlink( $saveto );
		}
		$fp = fopen( $saveto, 'x' );
		fwrite( $fp, $raw );
		fclose( $fp );
	}

	/**
	 * @param $url
	 *
	 * @return bool|string
	 */
	public static function curl_file( $url ) {

		$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36';

		// create curl resource
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_VERBOSE, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, $agent );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		// $output contains the output string
		$output = curl_exec( $ch );

		curl_close( $ch );

		return $output;
	}

}



