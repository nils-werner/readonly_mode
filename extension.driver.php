<?php

	Class extension_readonly_mode extends Extension{

		public function about(){
			return array('name' => 'Readonly Mode',
						 'version' => '1.0',
						 'release-date' => '2010-01-08',
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
							'page' => '/backend/',
							'delegate' => 'AdminPagePreGenerate',
							'callback' => 'preGenerate'
						),
						
					);
		}
		
		public function __construct() {
		}
		
		public function install(){
			Administration::instance()->Configuration->set('enabled', 'no','readonly_mode');
			Administration::instance()->saveConfig();
			return true;
		}
		
		public function uninstall(){
			Administration::instance()->Configuration->remove('readonly_mode');
			Administration::instance()->saveConfig();
		}
		
		public function __appendAlert($context){
			
			if(!is_null($context['alert'])) return;
			
			if(Administration::instance()->Configuration->get('enabled', 'readonly_mode') == 'yes'){
				Administration::instance()->Page->pageAlert(__('This site is currently in readonly mode.') . ' <a href="' . URL . '/symphony/system/preferences/?action=toggle-readonly-mode&amp;redirect=' . getCurrentPage() . '">' . __('Restore?') . '</a>', Alert::NOTICE);
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
			if(Administration::instance()->Configuration->get('enabled', 'readonly_mode') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Enable readonly mode'));
			$group->appendChild($label);
						
			$group->appendChild(new XMLElement('p', __('Radonly mode will prevent all authors and events from editing or creating entries.'), array('class' => 'help')));
									
			$context['wrapper']->appendChild($group);
						
		}
		
		
				
		public function preGenerate($page) {
			if(Administration::instance()->Configuration->get('enabled', 'readonly_mode') == 'yes') {
				if(in_array($page["oPage"]->_context["page"], array("edit", "index", "new"))) {
					$this->disableInputs(&$page["oPage"]->Form);
				}
			}
		}
		
		private function disableInputs($element) {
			if(in_array($element->getName(), array("input", "select", "textarea", "button")))
				$element->setAttribute("disabled","disabled");
				
			if($element->getAttribute("name") == "action[delete]") {
				$element->setAttribute("style", "cursor: default;");
				$element->setAttribute("class", NULL);
			}
			
			foreach($element->getChildren() AS $child)
				$this->disableInputs(&$child);
		}
	}
