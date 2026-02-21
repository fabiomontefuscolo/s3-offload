<?php
/**
 * Mock WP_CLI class for testing.
 *
 * @package S3_Offloader
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Mock WP_CLI class for unit tests.
	 * Supports both command registration and CLI output testing.
	 */
	class WP_CLI {
		/**
		 * Storage for registered commands.
		 *
		 * @var array
		 */
		private static $commands = array();

		/**
		 * Storage for mocked log callback.
		 *
		 * @var callable
		 */
		public static $__log;

		/**
		 * Storage for mocked success callback.
		 *
		 * @var callable
		 */
		public static $__success;

		/**
		 * Storage for mocked error callback.
		 *
		 * @var callable
		 */
		public static $__error;

		/**
		 * Register a command.
		 *
		 * @param string          $name     Command name.
		 * @param string|callable $callable Command callable or class name.
		 */
		public static function add_command( $name, $callable ) {
			self::$commands[ $name ] = $callable;
		}

		/**
		 * Check if a command is registered.
		 *
		 * @param string $name Command name.
		 * @return bool True if command exists.
		 */
		public static function has_command( $name ) {
			return isset( self::$commands[ $name ] );
		}

		/**
		 * Get a registered command.
		 *
		 * @param string $name Command name.
		 * @return mixed Command callable or null.
		 */
		public static function get_command( $name ) {
			return self::$commands[ $name ] ?? null;
		}

		/**
		 * Reset all registered commands and callbacks.
		 */
		public static function reset() {
			self::$commands  = array();
			self::$__log     = null;
			self::$__success = null;
			self::$__error   = null;
		}

		/**
		 * Log a message.
		 *
		 * @param string $message Message to log.
		 */
		public static function log( $message ) {
			if ( is_callable( self::$__log ) ) {
				call_user_func( self::$__log, $message );
			}
		}

		/**
		 * Display a success message.
		 *
		 * @param string $message Success message.
		 */
		public static function success( $message ) {
			if ( is_callable( self::$__success ) ) {
				call_user_func( self::$__success, $message );
			}
		}

		/**
		 * Display an error message.
		 *
		 * @param string $message Error message.
		 */
		public static function error( $message ) {
			if ( is_callable( self::$__error ) ) {
				call_user_func( self::$__error, $message );
			}
		}
	}
}

if ( ! class_exists( 'WP_CLI\Utils' ) ) {
	/**
	 * Mock WP_CLI\Utils namespace class.
	 */
	class WP_CLI_Utils {
		/**
		 * Create a mock progress bar.
		 *
		 * @param string $message Message for progress bar.
		 * @param int    $count   Total count.
		 * @return WP_CLI_Progress_Bar Mock progress bar instance.
		 */
		public static function make_progress_bar( $message, $count ) {
			return new WP_CLI_Progress_Bar( $message, $count );
		}
	}

	// Create namespace alias.
	class_alias( 'WP_CLI_Utils', 'WP_CLI\Utils' );
}

if ( ! function_exists( 'WP_CLI\Utils\make_progress_bar' ) ) {
	/**
	 * Mock make_progress_bar function in WP_CLI\Utils namespace.
	 *
	 * @param string $message Message for progress bar.
	 * @param int    $count   Total count.
	 * @return WP_CLI_Progress_Bar Mock progress bar instance.
	 */
	function make_progress_bar( $message, $count ) {
		return new WP_CLI_Progress_Bar( $message, $count );
	}

	// Register in WP_CLI\Utils namespace.
	if ( ! function_exists( '\WP_CLI\Utils\make_progress_bar' ) ) {
		eval( 'namespace WP_CLI\Utils; function make_progress_bar( $message, $count ) { return new \WP_CLI_Progress_Bar( $message, $count ); }' );
	}
}

if ( ! class_exists( 'WP_CLI_Progress_Bar' ) ) {
	/**
	 * Mock WP_CLI progress bar.
	 */
	class WP_CLI_Progress_Bar {
		/**
		 * Message for progress bar.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Total count.
		 *
		 * @var int
		 */
		private $count;

		/**
		 * Current tick.
		 *
		 * @var int
		 */
		private $current = 0;

		/**
		 * Constructor.
		 *
		 * @param string $message Message for progress bar.
		 * @param int    $count   Total count.
		 */
		public function __construct( $message, $count ) {
			$this->message = $message;
			$this->count   = $count;
		}

		/**
		 * Increment progress bar.
		 */
		public function tick() {
			++$this->current;
		}

		/**
		 * Finish progress bar.
		 */
		public function finish() {
			// Nothing to do in mock.
		}
	}
}
