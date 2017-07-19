<?php
/*
	Plugin Name: Jresizr
	Plugin URI: http://jegtheme.com/
	Description: Give abilty to uncrop image and put background color behind the image.
	Version: 1.0.0
	Author: Agung Bayu Iswara
	Author URI: http://jegtheme.com
	License: GPL2
*/

/*  
	Copyright 2012  Agung Iswara  (email : agungbayuiswara@gmail.com)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once ( dirname(__FILE__) . '/jpanel.php');

if ( !class_exists( 'JResizr' ) ) :

	class JResizr extends JPanel {
				
		/**
		 * @since 1.0.0
		 * @var JPanel
		 */
		private $jpanel;		
		
		/**
		 * constructor
		 * @since 1.0.0		 
		 */
		public function __construct() 
		{
			$this->plugin_init();				
		}
				
		/**
		 * setup plugin
		 * @since 1.0.0
		 * @return void
		 */		
		public function plugin_init () 
		{
			$this->jpanel_init();
			register_activation_hook( __FILE__, array( &$this, 'activation' ) );
			
			if( $this->get_option('enablejresizr') ) {
				add_filter('wp_generate_attachment_metadata', array(&$this, 'generate_post_thumbnail'), 10, 2);
			}
		}
				
		/**
		 * 
		 * @since 1.0.0
		 * @return void
		 */
		public function generate_post_thumbnail ($metadata, $attachment_id) 
		{
			$file = $metadata['file'];
			
			//define upload path & dir
			$upload_info = wp_upload_dir();
			$upload_dir = $upload_info['basedir'];
			$upload_url = $upload_info['baseurl'];
			
			// get the real directory and file path
			$filepath 		= $upload_dir . DIRECTORY_SEPARATOR . $file;
			$info 			= pathinfo($filepath );
			$filedir 		= $info['dirname'];			
			$fillcolor 		= $this->get_option('colorjresizr', '000000');
						
			// delete previous file
			foreach ($metadata['sizes'] as $size) {
				$dest = $filedir . DIRECTORY_SEPARATOR . $size['file'];
				if (file_exists($dest)) {
					unlink($dest);
				}
			}
			
			// empty sizes file meta data
			$metadata['sizes'] = null;
			
			// make thumbnails and other intermediate sizes
			global $_wp_additional_image_sizes;
						
			foreach ( get_intermediate_image_sizes() as $s ) {
				$sizes[$s] = array( 'width' => '', 'height' => '');
				
				/** width **/
				if ( isset( $_wp_additional_image_sizes[$s]['width'] ) )
					$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] ); // For theme-added sizes
				else
					$sizes[$s]['width'] = get_option( "{$s}_size_w" ); // For default sizes set in options
				
				/** height **/
				if ( isset( $_wp_additional_image_sizes[$s]['height'] ) )
					$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] ); // For theme-added sizes
				else
					$sizes[$s]['height'] = get_option( "{$s}_size_h" ); // For default sizes set in options
				
				/** file name **/
				$suffix = "{$sizes[$s]['width']}x{$sizes[$s]['height']}";
				
				$info = pathinfo($filepath);
				$dir = $info['dirname'];
				$ext = $info['extension'];
				$name = wp_basename($file, ".$ext");
				
				$sizes[$s]['file'] = "{$dir}/{$name}-{$suffix}.{$ext}";
			}
			
			// resize actual file
			foreach ($sizes as &$size_data ) {
				$this->resize_nocrop( $filepath, $size_data['file'], $size_data['width'], $size_data['height'] , 100,  $fillcolor);
				$size_data['file'] = wp_basename($size_data['file']);
			}
			
			$metadata['sizes'] = $sizes;
			return $metadata;
		}
		
		public function resize_nocrop ($source_image, $destination, $width, $height, $quality = 100, $fillcolor) 
		{
			$info = getimagesize($source_image);
		    $imgtype = image_type_to_mime_type($info[2]);
			
		    switch ($imgtype) {
		        case 'image/jpeg':
		            $source = imagecreatefromjpeg($source_image);
		            break;
		        case 'image/gif':
		            $source = imagecreatefromgif($source_image);
		            break;
		        case 'image/png':
		            $source = imagecreatefrompng($source_image);
		            break;
		        default:
		        	return;
		    }
		    
		    #Figure out the dimensions of the image and the dimensions of the desired thumbnail
		    $src_w = imagesx($source);
		    $src_h = imagesy($source);
			
		    #Do some math to figure out which way we'll need to crop the image
		    #to get it proportional to the new size, then crop or adjust as needed
		    $x_ratio = $width  / $src_w;
		    $y_ratio = $height / $src_h;
		    
			if (($src_w <= $width) && ($src_h <= $height)) {
		        $new_w = $src_w;
		        $new_h = $src_h;
		    } elseif (($x_ratio * $src_h) < $height) {
		        $new_h = ceil($x_ratio * $src_h);
		        $new_w = $width;
		    } else {
		        $new_w = ceil($y_ratio * $src_w);
		        $new_h = $height;
		    }
		    
		    $newpic = imagecreatetruecolor(round($new_w), round($new_h));
		    imagecopyresampled($newpic, $source, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
		    $final = imagecreatetruecolor($width, $height);
		    
		    $fillbg = $this->j_hex2RGB($fillcolor);
		    $backgroundColor = imagecolorallocate($final, $fillbg['red'], $fillbg['green'], $fillbg['blue']);
		    imagefill($final, 0, 0, $backgroundColor);
		    imagecopy($final, $newpic, (($width - $new_w)/ 2), (($height - $new_h) / 2), 0, 0, $new_w, $new_h);
		       
		    if (imagejpeg($final, $destination, $quality)) {
		        return true;
		    }
		    return false;
		}
		
		/**
		 * Handles activation tasks, such as registering the uninstall hook.
		 * @since 1.0.0
		 * @return void
		 */
		public function activation() 
		{
			// delete option for activated first
			delete_option( $this->jps->get_pluginoption() );
		}
		
		/**
		 * Handles uninstallation tasks, such as deleting plugin options.
		 * @since 1.0.0
		 * @return void
		 */
		public function uninstall() 
		{
			delete_option( $this->jps->get_pluginoption() );
		}
		
			
		/**
		 * Setup JPSetting value
		 * @since 1.0.0
		 * @return JPSetting
		 */
		public function jps_init() 
		{
			// meta setting for this plugin			
			return new JPSetting( $this->file_basename(__FILE__)	, 
				$this->file_basename(__FILE__) . '_option' , 
				__FILE__ , '1.0.0', $this->get_panel() );
		}
		
		/**
		 * Get panel option used for this plugin
		 * @since 1.0.0
		 * return Array
		 */
		public function get_panel () 
		{
			$panel = array(
				array(
					'type'			=> 'heading',
					'title'			=> 'Jresizr Option'
				),
				array (
					'id' 			=> 'enablejresizr',
					'type'			=> 'switchtoogle',
					'title'			=> 'Enable JResizr',
					'description'	=> __('Turn on jresizr, non croping image with filled background will take affect after you turn on this option. '),
					'value'			=> $this->get_option('enablejresizr', 0)
				),
				array (
					'id' 			=> 'colorjresizr',
					'type'			=> 'colorpicker',
					'title'			=> 'Background Color for Jresizr',
					'description'	=> __('Set background color for jresizr.'),
					'value'			=> $this->get_option('colorjresizr', '000000')
				)
			);
			return $panel;
		}
		
		
	}
endif;

/*
 * make sure we running this plugin inside admin dashboard
 */ 
if ( is_admin() ) {
	$jresizr = new JResizr();
}
