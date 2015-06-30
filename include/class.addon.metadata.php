<?php
if ( ! class_exists( 'SLP_Addon_MetaData' ) ) {

	/**
	 * Handle Add On MetaData.
	 *
	 * Load the metadata from a plugin header only when needed.
	 * This lightens the memory and disk I/O load on normal UI operations.
	 */
	class SLP_Addon_MetaData {

		/**
		 * @var SLP_BaseClass_Addon
		 */
		private $addon;

		/**
		 * Named array of metadata properties.
		 *
		 * @see https://developer.wordpress.org/reference/functions/get_plugin_data/
		 *
		 * @var string[]
		 */
		private $metadata;

		/**
		 * Has the meta data been read from the add on file header?
		 *
		 * @var bool
		 */
		private $meta_read = false;

		/**
		 * @var SLPlus
		 */
		private $slplus;

		/**
		 * @param mixed[] $params
		 */
		function __construct( $params ) {
			if ($params !== null) {
				foreach ($params as $property => $value) {
					if (property_exists($this, $property)) {
						$this->$property = $value;
					}
				}
			}
		}

		/**
		 * Read the plugin header meta.
		 */
		private function read_meta() {
			if ( ! $this->meta_read ) {
				if ( isset( $this->addon->file ) ) {
					$this->metadata = get_plugin_data( $this->addon->file );
				}
				$this->meta_read = true;
			}
		}

		/**
		 * Return the specified metadata property.
		 *
		 * @param string $property
		 *
		 * @return string
		 */
		public function get_meta( $property ) {
			$this->read_meta();
			if ( ! isset( $this->metadata[$property] ) ) {
				$this->metadata[$property] = '';
			}
			return $this->metadata[$property];
		}

	}

}