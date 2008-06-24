<?php
	if (!defined("INCLUDES_DIR")) define("INCLUDES_DIR", dirname(__FILE__));

	/**
	 * Class: Config
	 * Holds all of the configuration variables for the entire site, as well as Module settings.
	 */
	class Config {
		/**
		 * The class constructor is private so there is only one instance and config is guaranteed to be kept in sync.
		 */
		private function __construct() {}

		/**
		 * Variable: $yaml
		 * Holds all of the YAML settings as a $key => $val array.
		 */
		private $yaml = array();

		/**
		 * Function: load
		 * Loads a given configuration YAML file.
		 *
		 * Parameters:
		 *     $file - The YAML file to load into <Config>.
		 */
		public function load($file) {
			$this->yaml = Spyc::YAMLLoad($file);
			$arrays = array("enabled_modules", "enabled_feathers", "routes");
			foreach ($this->yaml as $setting => $value)
				if (in_array($setting, $arrays) and empty($value))
					$this->$setting = array();
				elseif (!is_int($setting)) # Don't load the "---"
					$this->$setting = (is_string($value)) ? stripslashes($value) : $value ;

			fallback($this->url, $this->chyrp_url);
		}

		/**
		 * Function: set
		 * Sets a variable's value.
		 *
		 * Parameters:
		 *     $setting - The setting name.
		 *     $value - The new value. Can be boolean, numeric, an array, a string, etc.
		 */
		public function set($setting, $value, $overwrite = true) {
			global $errors;
			if (isset($this->$setting) and ($this->$setting == $value or !$overwrite))
				return false;

			# Add the PHP protection!
			$contents = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

			# Add the setting
			$this->yaml[$setting] = $this->$setting = $value;

			if (isset($this->yaml['<?php header("Status']))
				unset($this->yaml['<?php header("Status']);

			# Generate the new YAML settings
			$contents.= Spyc::YAMLDump($this->yaml, 2, 60);

			if (!@file_put_contents(INCLUDES_DIR."/config.yaml.php", $contents) and is_array($errors))
				$errors[] = _f("Could not set \"<code>%s</code>\" configuration setting because <code>%s</code> is not writable.", array($setting, "/includes/config.yaml.php"));
		}

		/**
		 * Function: remove
		 * Removes a configuration setting.
		 *
		 * Parameters:
		 *     $setting - The name of the variable to remove.
		 */
		public function remove($setting) {
			# Add the PHP protection!
			$contents = "<?php header(\"Status: 403\"); exit(\"Access denied.\"); ?>\n";

			# Add the setting
			unset($this->yaml[$setting]);

			if (isset($this->yaml[0]) and $this->yaml[0] == "--")
				unset($this->yaml[0]);

			# Generate the new YAML settings
			$contents.= Spyc::YAMLDump($this->yaml, 2, 60);

			file_put_contents(INCLUDES_DIR."/config.yaml.php", $contents);
		}

		/**
		 * Function: current
		 * Returns a singleton reference to the current configuration.
		 */
		public static function & current() {
			static $instance = null;
			return $instance = (empty($instance)) ? new self() : $instance ;
		}
	}
	$config = Config::current();
