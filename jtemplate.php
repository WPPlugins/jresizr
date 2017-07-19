<?php
/**
 * Base template class for all Admin Menu (metabox, admin setting, widget setting)
 * and other need like additional style, email template. etc.
 * This template may also used on Front end. 
 * 
 * @author jegbagus
 */
if ( !class_exists( 'JTemplate' ) ) :

	class JTemplate 
	{
		private $vars = array();
		private $templateDir;
		private $templatePostfix;
		
		public function __construct($directory = 'view/', $postfix = '.phtml')
		{
			$this->templateDir = $directory;
			$this->templatePostfix = $postfix;
		}
		
		public function __get($name)
		{
			return $this->vars[$name];
		}
		
		public function __set($name, $value)
		{
			$this->vars[$name] = $value;
		}
		
		public function assign_array($arr){
			foreach($arr as $key => $value){
				$this->vars[$key] = $value;
			}
		}
		
		public function clear_prev_data() 
		{
			if(!empty($this->vars)) {
				foreach ($this->vars as $key => $val) {
					$this->$key = NULL;
				}
			}
		}
		
		public function render($templateName, $var = array(), $output = FALSE)
		{
			$this->clear_prev_data();
			
			if(!empty($var)) {
				if(is_array($var)) {
					$this->assign_array($var);
				}
			}
			
			extract($this->vars);
			if(!$output) ob_start();
			include $this->templateDir . $templateName . $this->templatePostfix;
			if(!$output) return ob_get_clean();
		}
	}
	
endif;