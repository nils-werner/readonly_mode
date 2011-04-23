<?php

	Class extension_readonly_mode extends Extension{

		public function about(){
			return array('name' => 'Readonly Mode',
						 'version' => '1.1',
						 'release-date' => '2010-02-02',
						 'author' => array('name' => 'Nils Werner',
										   'website' => 'http://www.phoque.de',
										   'email' => 'nils.werner@gmail.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/system/preferences/',
							'delegate' => 'AddCustomPreferenceFieldsets',
							'callback' => 'appendPreferences'
						),
						
						array(
							'page' => '/system/preferences/',
							'delegate' => 'Save',
							'callback' => '__SavePreferences'
						),
						array(
							'page' => '/backend/',
							'delegate' => 'AppendPageAlert',
							'callback' => '__appendAlert'
						),
						array(
							'page' => '/system/preferences/',
							'delegate' => 'CustomActions',
							'callback' => '__toggleReadonlyMode'
						),

						array(
							'page' => '/backend/',
							'delegate' => 'AdminPagePreGenerate',
							'callback' => 'preGenerate'
						),
						array(
							'page' => '/frontend/',
							'delegate' => 'EventPreSaveFilter',
							'callback' => 'preEvent'
						),
						array(
							'page' => '/frontend/',
							'delegate' => 'FrontendParamsResolve',
							'callback' => '__addParam'
						)
						
					);
		}
		
		public function __construct() {
		}
		
		public function install(){
			Symphony::Configuration()->set('enabled', 'no','readonly_mode');
			Administration::instance()->saveConfig();
			return true;
		}
		
		public function uninstall(){
			Symphony::Configuration()->remove('readonly_mode');
			Administration::instance()->saveConfig();
		}
		
		public function __appendAlert($context){
			
			if(!is_null($context['alert'])) return;
			
			if(Symphony::Configuration()->get('enabled', 'readonly_mode') == 'yes'){
				$text = __('This site is currently in readonly mode.');
				if($this->isDeveloper())
					$text .= ' <a href="' . URL . '/symphony/system/preferences/?action=toggle-readonly-mode&amp;redirect=' . getCurrentPage() . '">' . __('Restore?') . '</a>';
				Administration::instance()->Page->pageAlert($text, Alert::NOTICE);
			}
		}
		
		public function __SavePreferences($context){

			if(!is_array($context['settings'])) $context['settings'] = array('readonly_mode' => array('enabled' => 'no'));
			
			elseif(!isset($context['settings']['readonly_mode'])){
				$context['settings']['readonly_mode'] = array('enabled' => 'no');
			}
			
		}

		public function appendPreferences($context){

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Readonly Mode')));			
			
			$label = Widget::Label();
			$input = Widget::Input('settings[readonly_mode][enabled]', 'yes', 'checkbox');
			if(Symphony::Configuration()->get('enabled', 'readonly_mode') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Enable readonly mode'));
			$group->appendChild($label);
						
			$group->appendChild(new XMLElement('p', __('Radonly mode will prevent all authors and events from editing or creating entries.'), array('class' => 'help')));
									
			$context['wrapper']->appendChild($group);
						
		}
		
		public function __toggleReadonlyMode($context){
			
			if($_REQUEST['action'] == 'toggle-readonly-mode'){			
				$value = (Symphony::Configuration()->get('enabled', 'readonly_mode') == 'no' ? 'yes' : 'no');					
				Symphony::Configuration()->set('enabled', $value, 'readonly_mode');
				Administration::instance()->saveConfig();
				redirect((isset($_REQUEST['redirect']) ? URL . '/symphony' . $_REQUEST['redirect'] : $this->_Parent->getCurrentPageURL() . '/'));
			}
			
		}
		
		
				
		public function preGenerate($page) {
			if(Symphony::Configuration()->get('enabled', 'readonly_mode') == 'yes' && !$this->isDeveloper()) {
				if(in_array($page["oPage"]->_context["page"], array("edit", "index", "new")) || $page["oPage"]->_Parent->Page instanceof contentSystemAuthors) {
					$this->disableInputs($page["oPage"]->Form);
				}
			}
		}
		
		public function preEvent($context) {
			if($context["parent"]->Configuration->get('enabled', 'readonly_mode') == 'yes') {
				$context['messages'][] = array(
					'readonly', FALSE, __("Events have been disabled temporarily.")
				);
			}
		}
		
		private function disableInputs(&$element) {
			if(in_array($element->getName(), array("input", "select", "textarea", "button")))
				$element->setAttribute("disabled","disabled");
				
			if($element->getAttribute("name") == "action[delete]") {
				$element->setAttribute("style", "cursor: default;");
				$element->setAttribute("class", NULL);
			}
			
			foreach($element->getChildren() AS $child)
				$this->disableInputs($child);
		}
		
		private function isDeveloper() {
			return !is_null(Administration::instance()->Author) && Administration::instance()->Author->isDeveloper();
		}

		public function __addParam($context) {
			$context['params']['readonly-mode'] = (Symphony::Configuration()->get('enabled', 'readonly_mode') == 'yes' ? 'yes' : 'no'); 
		}
	}
