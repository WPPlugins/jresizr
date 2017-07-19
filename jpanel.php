<?php

/** we need to include jtemplate for templating purpose **/
require_once ( dirname(__FILE__) . '/jtemplate.php');

if ( !class_exists( 'JPanel' ) ) :
	
	abstract class JPanel {
		
		/**		
		 * @since 1.0.0
		 * @var JPSetting	
		 */
		protected $jps;		
		
		/**
		 * @since 1.0.0
		 * @var array
		 */
		protected $data;
		
		/**
		 * @since 1.0.0
		 * @var array
		 */
		protected $option;
		
		/**		
		 * @since 1.0.0
		 * @var JTemplate	
		 */
		protected $jtemplate;
		
		/**
		 * setup option panel
		 * @since 1.0.0
		 * @return void
		 */
		public function jpanel_init() 
		{
			$this->jps = $this->jps_init();
			$this->option = get_option($this->jps->get_pluginoption());
			$this->jtemplate = new JTemplate( dirname($this->jps->get_filepath()) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR);
						
			add_action( 'admin_menu', array( &$this, 'add_setting_menu' ) );
		}
		
		/**
		 * add menu on setting page
		 * @since 1.0.0
		 * @return void
		 */
		public function add_setting_menu ()
		{
			if($this->current_user_can_edit_menu()) 
			{
				// add plugin action link (only shown when plugin activated)
				add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2);
				
				// add additional menu under setting menu
				$page = add_options_page( ucfirst($this->jps->get_basename()),  ucfirst($this->jps->get_basename()),
					'manage_options', $this->jps->get_basename(), array(&$this, 'show_panel')
				);
			}
		}
		
		public function plugin_action_links($links, $file) 
		{
			if ($this->file_basename($file) == $this->jps->get_basename()) {
	            $link = "<a href='options-general.php?page=" . $this->jps->get_basename() . "'>" . __('Settings') . "</a>";
	            array_unshift($links, $link);
			}
	        return $links;
		}
		
		/**
		 * get file name without extension		 
		 * @since 1.0.0		 
		 * @param String $file		 
		 * @return String
		 */
		public function file_basename($file) 
		{
			$info = pathinfo($file);
			$filebasename =  basename($file, '.' . $info['extension']);		
			return $filebasename;
		}
		
		/**
		 * Determine if the current user may use the menu editor.
		 * @since 1.0.0
		 * @return bool
		 */
		protected function current_user_can_edit_menu()
		{
			return current_user_can('manage_options');
		}
		
		/**
		 * get spesific option
		 * @since 1.0.0
		 * @param unknown_type $name
		 * @param unknown_type $default
		 * @return void
		 */
		protected function get_option($name, $default = null) 
		{
			if(!empty($this->option[$name])) {
				return $this->option[$name];
			} else {
				return $default;
			}
		}		
		
		/**
		 * Save jplugin option
		 * @since 1.0.0
		 * @return void
		 */
		protected function save_option () 
		{
			if(isset($_POST['submit']) && $_POST['submit'] == 'true') {
				
				// verify wp nonce
				if ( !wp_verify_nonce($_POST['admin_setting_nonce'], $this->jps->get_basename())) {					
					wp_die('Invalid Nonce');					
				}
								
				// get option panel
				$optionpanel = $this->get_panel();
				
				// loop to save option panel
				foreach($optionpanel as $item) :
					// pass item when type is heading
					if($item['type'] == 'heading') { 						
						continue;						
					}
					
					$value = isset($_POST[$item['id']]) ? $_POST[$item['id']] : NULL ; 
					
					if(empty($value)) {
						if($item['type'] == 'switchtoogle'){
							$this->option[$item['id']] = 0;
						}
					} else {
						$this->option[$item['id']] = $value;
					}
				endforeach;
				
				// save option
				update_option($this->jps->get_pluginoption() , $this->option);
				$this->data['savemsg']	 = TRUE;
			}
		}
		
		/**
		 * get html version of panel option
		 * @since 1.0.0
		 * @return String of html option
		 */
		protected function get_panel_option() 
		{
			$html = '';
			
			$options = $this->get_panel();	
			foreach($options as $option) {
				if($option['type'] == 'heading'){
					$html .= $this->jtemplate->render('option-heading', $option);
				}
				if($option['type'] == 'switchtoogle') {
					$html .= $this->jtemplate->render('option-switchtoogle', $option);
				}
				if($option['type'] == 'colorpicker'){
					$html .= $this->jtemplate->render('option-colorpicker', $option);
				}
			}
			
			return $html;
		}
		
		/**
		 * build entire option and display it to user
		 * @since 1.0.0
		 * @return void
		 */
		protected function build_panel() 
		{					
			// plugin meta
			$this->data['pluginname'] 	= ucfirst( $this->jps->get_basename() );
			$this->data['version']		= $this->jps->get_version();
			$this->data['wpnonce']		= wp_nonce_field( $this->jps->get_basename(), 'admin_setting_nonce');

			// build content panel
			$this->data['bodycontent'] = $this->get_panel_option();
			
			// render skeleton file
			$this->jtemplate->render('skeleton', $this->data, true);
		}
		
		/**
		 * we need to disable magic quote, so we can read json entries from input
		 * @since 1.0.0
		 * @return voids 
		 */
		public function jeg_disable_magic_quote() {	
		    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
			while (list($key, $val) = each($process)) {
				foreach ($val as $k => $v) {
					unset($process[$key][$k]);
					if (is_array($v)) {
						$process[$key][stripslashes($k)] = $v;
						$process[] = &$process[$key][stripslashes($k)];
					} else {
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset($process);
		}
		
		/**
		 * show panel
		 * @since 1.0.0
		 * @return void
		 */
		public function show_panel() 
		{
			/** enqueue style **/
			wp_enqueue_style('jpanel-style', plugins_url('css/style.css', $this->jps->get_filepath()), array(), '20123009');
			
			/** enqueue js **/
			wp_enqueue_script('jquery');
			wp_enqueue_script('jeg-colorpicker'	, 	plugins_url('js/colorpicker.js', $this->jps->get_filepath()) 		, null, '20123009' );			
			wp_enqueue_script('jeg-json'		, 	plugins_url('js/json2.js', $this->jps->get_filepath()) 				, null, '20123009' );
			wp_enqueue_script('jeg-ibutton'		, 	plugins_url('js/jquery.ibutton.min.js', $this->jps->get_filepath()) , null, '20123009' );
			wp_enqueue_script('jeg-js-admin'	, 	plugins_url('js/script.js', $this->jps->get_filepath())				, null, '20123009' );
			
			/** save option & build panel **/
			$this->save_option();
			$this->build_panel();
		}
		
		/**
		 * Convert a hexa decimal color code to its RGB equivalent (http://php.net/manual/en/function.hexdec.php)
		 *
		 * @param string $hexStr (hexadecimal color value)
		 * @param boolean $returnAsString (if set true, returns the value separated by the separator character. Otherwise returns associative array)
		 * @param string $seperator (to separate RGB values. Applicable only if second parameter is true.)
		 * @return array or string (depending on second parameter. Returns False if invalid hex color value)
		 */                            
		public function j_hex2RGB($hexStr, $returnAsString = false, $seperator = ',') 
		{
			$hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
			$rgbArray = array();
		    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
		        $colorVal = hexdec($hexStr);
		        $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
		        $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
		        $rgbArray['blue'] = 0xFF & $colorVal;
		    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
		        $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
		        $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
		        $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
		    } else {
		        return false; //Invalid hex color code
		    }
		    return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
		}
		
		abstract public function plugin_init();
		abstract public function get_panel();
		abstract public function jps_init();
	}

endif;


if ( !class_exists( 'JPSetting' )) :
	/** 
	 * Setting class for plugin 
	 * 
	 * @author agung
	 *
	 */
	class JPSetting {
		private $basename;
		private $pluginoption;
		private $filepath;
		private $panel;
		private $version;
		
		public function __construct($basename, $pluginoption, $filepath, $version, $panel) {
			$this->basename 	= $basename;
			$this->pluginoption = $pluginoption;
			$this->filepath		= $filepath;
			$this->version		= $version;
			$this->panel		= $panel;			
		}
				
		public function get_basename () { return $this->basename; }		
		public function get_pluginoption () { return $this->pluginoption; }	
		public function get_filepath () { return $this->filepath; }	
		public function get_version () { return $this->version; }
		public function get_panel () { return $this->panel; }
	}

endif;