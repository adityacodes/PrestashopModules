<?php
/*
* 2016	Aditya
*
*  @author Aditya Padhi <adityapadhi91@gmail.com>
*  @copyright  2016 Aditya
*/

if (!defined('_CAN_LOAD_FILES_'))
	exit;

class DateTimeCustomizer extends Module
{
	private $_html = '';
	private $order;
	private $delivery_date;
	private $delivery_time;

	public function __construct()
	{
		$this->name = 'datetimecustomizer';
		$this->tab = 'shipping_logistics';
		$this->version = '1.0.0';
		$this->author = 'Aditya';

		$this->bootstrap = true;
		parent::__construct();	

		
		$this->displayName = $this->l('Date And Time of delivery');
		$this->description = $this->l('Allows user to choose a delivery date and preffered time slot.');
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall Time Customizer from your Prestashop?');

		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}


	public function install()
	{
		if (!parent::install()
			|| !$this->registerHook('beforeCarrier')
			|| !$this->registerHook('orderDetailDisplayed')
			|| !$this->registerHook('actionCarrierUpdate')
			|| !$this->registerHook('displayPDFInvoice')
			|| !$this->registerHook('displayPaymentTop')
			|| !$this->registerHook('orderConfirmation')
			|| !$this->registerHook('displayAdminOrder')
			|| !$this->registerHook('header'))
				return false;

			// This table stores the carrier rules or the time slots
		if (!Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'datetimecustomizer_carrier_rule` (
			`id_carrier_rule` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`minimal_time` VARCHAR(20) NOT NULL,
			`maximal_time` VARCHAR(20) NOT NULL
		) ENGINE ='._MYSQL_ENGINE_.';
		'))
			return false;

		// This stores the orderid and the order detail from the carrier rule and the delivery date from Input.
		if (!Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'datetimecustomizer_order_detail` (
			`id_order_detail` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`id_order` INT NOT NULL,
			`id_cart` INT NOT NULL,
			`minimal_time` VARCHAR(20) NOT NULL,
			`maximal_time` VARCHAR(20) NOT NULL,
			`delivery_date` VARCHAR(20) NOT NULL
		) ENGINE ='._MYSQL_ENGINE_.';
		'))
			return false;

		//This return is for the else part. Donot remove this.
		return true;

		Configuration::updateValue('DTC_MAX_ORDER_DATE', '+1M +10D');
		Configuration::updateValue('DTC_DATE_FORMAT', 'l j F Y');
	}

	public function uninstall()
	{
		if(!parent::uninstall())
			return false;

		// Remove any variables updated using Configuration::deleteByName('DDT_EXTRA_TIME_PREPARATION');
		Configuration::deleteByName('DTC_DATE_FORMAT');
		Configuration::deleteByName('DTC_MAX_ORDER_DATE');
		// Remove dB instance created while uninstall!

		Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`');
		Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'datetimecustomizer_order_detail`');


		return true;
	}


/* To display the configuration page for the module */
	public function getContent()
	{
		$this->_html .= '';

		$this->_postProcess();


		if (Tools::isSubmit('addCarrierRule') || (Tools::isSubmit('updatedatetimecustomizer') && Tools::isSubmit('id_carrier_rule')))
			$this->_html .= $this->renderAddForm();
		else
		{
			$this->_html .= $this->renderList();
			$this->_html .= $this->renderForm();
		}
		return $this->_html;
	}


	



	protected function _postProcess()
	{
		$errors = array();


		
		if (Tools::isSubmit('submitCarrierRule'))
		{
			$min_time = (string)Tools::getValue('minimal_time');
			$max_time = (string)Tools::getValue('maximal_time');

			if (!Validate::isUnsignedInt((int)$min_time))
				$errors[] = $this->l('Minimum time is invalid');

			if (!Validate::isUnsignedInt((int)$max_time))
				$errors[] = $this->l('Maximum time is invalid');

			if ($this->_isAlreadyDefinedForCarrier($min_time, $max_time))
				$errors[] = $this->l('This rule has already been defined for this carrier.');

			if($this->_isNotInRange($min_time, $max_time))
				$errors[] = $this->l('Please choose a value between 0-23 hours.');

			if (!count($errors))
			{
				if (Tools::isSubmit('addCarrierRule'))
				{
					if (Db::getInstance()->execute('
					INSERT INTO `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`(`minimal_time`, `maximal_time`)
					VALUES ('.pSQL($min_time).', '.pSQL($max_time).')
					'))
						Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&confirmAddCarrierRule');
					else
						$this->_html .= $this->displayError($this->l('An error occurred while adding carrier rule.'));
				}
				else
				{

					// For updating we need id_carrier_rule

					if (Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`
					SET `minimal_time` = '.pSQL($min_time).', `maximal_time` = '.pSQL($max_time).'
					WHERE `id_carrier_rule` = '.(int)Tools::getValue('id_carrier_rule')
					))
						Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&confirmupdatedatetimecustomizer');
					else
						$this->_html .= $this->displayError($this->l('An error occurred on updating of carrier rule.'));
				}

			}
			else
				$this->_html .= $this->displayError(implode('<br />', $errors));
		}

		if (Tools::isSubmit('deletedatetimecustomizer') && Tools::isSubmit('id_carrier_rule') && (int)Tools::getValue('id_carrier_rule') && $this->_isCarrierRuleExists((int)Tools::getValue('id_carrier_rule')))
		{
			$this->_deleteByIdCarrierRule((int)Tools::getValue('id_carrier_rule'));
			$this->_html .= $this->displayConfirmation($this->l('Carrier rule deleted successfully'));
		}

		if (Tools::isSubmit('confirmAddCarrierRule'))
			$this->_html = $this->displayConfirmation($this->l('Carrier rule added successfully'));

		if (Tools::isSubmit('confirmupdatedatetimecustomizer'))
			$this->_html = $this->displayConfirmation($this->l('Carrier rule updated successfully'));


		if (Tools::isSubmit('submitMoreOptions'))
		{
			if (Tools::getValue('date_format') == '' OR !Validate::isCleanHtml(Tools::getValue('date_format')))
				$errors[] = $this->l('Date format is invalid');

			if (!count($errors))
			{
				Configuration::updateValue('DTC_MAX_ORDER_DATE', Tools::getValue('max_order_date'));
				Configuration::updateValue('DTC_DATE_FORMAT', Tools::getValue('date_format'));
				$this->_html .= $this->displayConfirmation($this->l('Settings are updated'));
			}
			else
				$this->_html .= $this->displayError(implode('<br />', $errors));
		}
	}

		protected function _isAlreadyDefinedForCarrier($minimal_time, $maximal_time)
		{
			if (!(string)($minimal_time) && !(string)$maximal_time)
				return false;

			return (bool)Db::getInstance()->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`
			WHERE `minimal_time` = '.((string)($minimal_time)).' AND `maximal_time` = '.((string)$maximal_time));
		}

		protected function _isNotInRange($min_time, $max_time){

			if( ((int)$min_time) > 23 || ((int)$max_time) > 23)
				return true;
		}


		protected function _isCarrierRuleExists($id_carrier_rule)
		{
			if (!(int)($id_carrier_rule))
				return false;
			return (bool)Db::getInstance()->getValue('
			SELECT COUNT(*)
			FROM `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`
			WHERE `id_carrier_rule` = '.(int)$id_carrier_rule
			);
		}

		protected function _deleteByIdCarrierRule($id_carrier_rule)
		{
			if (!(int)($id_carrier_rule))
				return false;
			return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`
			WHERE `id_carrier_rule` = '.(int)$id_carrier_rule
			);
		}

	


	public function renderAddForm()
	{

		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings - (Please choose a value between 0 - 23)'),
					'icon' => 'icon-cogs'
				),
				'input' => array( 
					array(
						'type' => 'text',
						'label' => $this->l('Delivery between'),
						'name' => 'minimal_time',
						'suffix' => $this->l('time(s)'),
						),
					array(
						'type' => 'text',
						'label' => $this->l(''),
						'name' => 'maximal_time',
						'suffix' => $this->l('time(s)'),
						)
				),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right',
				'name' => 'submitCarrierRule',
				),

			)
		);

		if (Tools::getValue('id_carrier_rule') && $this->_isCarrierRuleExists(Tools::getValue('id_carrier_rule')))
			$fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_carrier_rule');

		$helper = new HelperForm();

		$helper->show_toolbar = false;
		
		$helper->table = $this->table;
		
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		
		$helper->default_form_language = $lang->id;
		
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		
		$this->fields_form = array();

		$helper->identifier = $this->identifier;

		if (Tools::getValue('id_carrier_rule'))
			$helper->submit_action = 'updatedatetimecustomizer';
		else
			$helper->submit_action = 'addCarrierRule';
		
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		
		$helper->tpl_vars = array(

			'fields_value' => $this->getCarrierRuleFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

			public function getCarrierRuleFieldsValues()
			{
				$fields = array(
					'id_carrier_rule' => Tools::getValue('id_carrier_rule'),
					'minimal_time' => Tools::getValue('minimal_time'),
					'maximal_time' => Tools::getValue('maximal_time'),
					);

				if (Tools::isSubmit('updatedatetimecustomizer') && $this->_isCarrierRuleExists(Tools::getValue('id_carrier_rule')))
				{
					$carrier_rule = $this->_getCarrierRule(Tools::getValue('id_carrier_rule'));

					$fields['id_carrier_rule'] = Tools::getValue('id_carrier_rule', $carrier_rule['id_carrier_rule']);
					$fields['minimal_time'] = Tools::getValue('minimal_time', $carrier_rule['minimal_time']);
					$fields['maximal_time'] = Tools::getValue('maximal_time', $carrier_rule['maximal_time']);
				}

				return $fields;
			}
			protected function _getCarrierRule($id_carrier_rule)
			{
				if (!(int)$id_carrier_rule)
					return false;
				return Db::getInstance()->getRow('
				SELECT *
				FROM `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`
				WHERE `id_carrier_rule` = '.(int)$id_carrier_rule
				);
			}




	public function renderList()
	{
		$add_url = $this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&addCarrierRule=1';

		$fields_list = array(
			'name' => array(
				'title' => $this->l('Name of carrier'),
				'type' => 'text',
			),
			'delivery_between' => array(
				'title' => $this->l('Delivery between'),
				'type' => 'text',
				'align' =>'left',
			),
		);
		$list = $this->_getCarrierRules();

		foreach ($list as $key => $val)
		{
			$list[$key]['name'] = Configuration::get('PS_SHOP_NAME');
			//Convert 24hour to 12 hour here and then display
			$val['minimal_time'] = $this->_getTwelveHourTime($val['minimal_time']);
			$val['maximal_time'] = $this->_getTwelveHourTime($val['maximal_time']);

			$list[$key]['delivery_between'] = sprintf($this->l('%1s - %2s'), $val['minimal_time'], $val['maximal_time']);
		}

		$helper = new HelperList();

		$helper->shopLinkType = '';

		$helper->simple_header = true;

		$helper->identifier = 'id_carrier_rule';

		$helper->actions = array('edit', 'delete');

		$helper->show_toolbar = false;

		$helper->title = $this->l('List Rules');

		$helper->table = $this->name;

		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		$this->context->smarty->assign(array('add_url' => $add_url));

		return $this->display(__FILE__, 'smarty/button.tpl').$helper->generateList($list, $fields_list).$this->display(__FILE__, 'smarty/button.tpl');
	}


			protected function _getTimesOfDelivery()
			{
				$carrier_rules = $this->_getCarrierRules();
				if (empty($carrier_rules))
					return false;

			    return $carrier_rules;
				
			}

			protected function _getTwelveHourTime($time){
				if( ((int)$time) > 12 )
					return	$time = ( ((int)$time) - 12 )." PM";

				elseif( ((int)$time) < 12 && ((int)$time) != 0 )
					return	$time = $time." AM";

				elseif( ((int)$time) == 12 )
					return $time = $time." PM";

				else
					return $time = "12 AM";
			}

			protected function _getCarrierRules()
			{
				return Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'datetimecustomizer_carrier_rule`');
			}

	public function renderForm()
	{
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Static Settings'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Maximum Date Of Accepting Orders'),
						'name' => 'max_order_date',
						'suffix' => $this->l('FORMAT'),
						),
					array(
						'type' => 'text',
						'label' => $this->l('Date format:'),
						'name' => 'date_format',
						'desc' => $this->l('You can see all parameters available at:').' <a href="http://www.php.net/manual/en/function.date.php">http://www.php.net/manual/en/function.date.php</a>',
						),
				),
			'submit' => array(
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right')
			),
		);

		$helper = new HelperForm();

		$helper->show_toolbar = false;

		$helper->table = $this->table;

		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

		$helper->default_form_language = $lang->id;

		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

		$this->fields_form = array();

		$helper->identifier = $this->identifier;

		$helper->submit_action = 'submitMoreOptions';

		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;

		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

		public function getConfigFieldsValues()
		{
			return array(
				'max_order_date' => Tools::getValue('max_order_date', Configuration::get('DTC_MAX_ORDER_DATE')),
				'date_format' => Tools::getValue('date_format', Configuration::get('DTC_DATE_FORMAT')),
			);
		}


	public function hookBeforeCarrier($params)
	{
		$id_cart = $this->context->cookie->id_cart;
		var_dump($id_cart);
		$results = $this->_getTimesOfDelivery();
		
		foreach ($results as &$rs ) {
			$rs['minimal_time'] = $this->_getTwelveHourTime($rs['minimal_time']);
			$rs['maximal_time'] = $this->_getTwelveHourTime($rs['maximal_time']);
		}

		$max_order_date = Configuration::get('DTC_MAX_ORDER_DATE');

		if( ((int)date('H')) > 21 ){
			$tempdate = ((int)date('d')) + 1;
			$serverdate = $tempdate.'/'.date('m/Y');
		}else{
			$serverdate = date('d/m/Y');
		}

		if($id_cart){
			$result = $this->_getOrderDetailByCartId($id_cart);
			if(empty($result)){
				$dateval="";
				$timeval="";
			}
			else{
				$tempdate = date("d/m/Y", $result[0]['delivery_date']);
				$dateval=$tempdate;
				$timeval = $this->_getTwelveHourTime($result[0]['minimal_time']).'-'.$this->_getTwelveHourTime($result[0]['maximal_time']);
			}
		}


		$this->smarty->assign(array(
			'rules' => $results,
			'servertime' => date('H'),
			'serverdate' => $serverdate,
			'maxDate' => $max_order_date,
			'datenotset' => Tools::getValue('datenotset'),
			'timenotset' => Tools::getValue('timenotset'),
			'dateval' => $dateval,
			'timeval' => $timeval,
			'timeicon' => $this->_path."assets/time.png"
		));	
		return $this->display(__FILE__, 'smarty/beforeCarrier.tpl');

	}
			protected function _getOrderDetailByCartId($id_cart){
				return Db::getInstance()->ExecuteS('
					SELECT * FROM `'._DB_PREFIX_.'datetimecustomizer_order_detail` WHERE `id_cart` = '.(int)$id_cart);
			}


	public function hookActionBeforePayment($params)
	{
		if ( Tools::getValue('delivery_date') ) {
            
            if (!Validate::isDate(Tools::getValue('delivery_date'))) {
                $this->errors[] = Tools::displayError('Invalid date.');
            }
            else{
	        	$this->errors[] = Tools::displayError('Correct');
	        }

        }else{
        	$this->errors[] = Tools::displayError('Not checking delivery date.');
        }
	}

	public function hookHeader($params)
	{
		$this->context->controller->addJqueryUi('ui.datepicker');

		$this->context->controller->addJs($this->_path.'assets/timeoptimizer.js');



		if(Tools::getValue('step') == 1){
			$this->context->cookie->__unset('delivery_date');
			$this->context->cookie->__unset('delivery_time');
		}

		if(Tools::getValue('step') == 3)
		{
			$id_cart = $this->context->cookie->id_cart;
			if( !Tools::getValue('delivery_date') )
			{
				Tools::redirect('index.php?controller=order&step=2&datenotset=1');
			}
			else if(!Tools::getValue('delivery_time'))
			{
				Tools::redirect('index.php?controller=order&step=2&timenotset=1');
			}
			else
			{
				$this->delivery_date = Tools::getValue('delivery_date');
				$this->delivery_time = Tools::getValue('delivery_time');
				$this->context->cookie->__set('delivery_date',$this->delivery_date);
				$this->context->cookie->__set('delivery_time',$this->delivery_time);

				$id_cart = $this->context->cookie->id_cart;

				$time = $this->_getSanitizedTime($this->delivery_time);
				$minimal_time = $time[0];
				$maximal_time = $time[1];


				$date = str_replace("/","-", $this->delivery_date);
				$this->delivery_date = strtotime($date);

				if ($this->_isAlreadyDefinedOrder($id_cart)){
					$id_order_detail = $this->_getIdOrderDetail($id_cart);
					$this->updateOrderValues($id_cart, $id_order_detail, $this->delivery_date, $minimal_time, $maximal_time);
				}	
				else{
					$this->addOrderValues($id_cart, $this->delivery_date, $minimal_time, $maximal_time);
				}
			}
		}
	}
			public function updateOrderValues($id_cart, $id_order_detail, $delivery_date, $minimal_time, $maximal_time){
				Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'datetimecustomizer_order_detail`
					SET `id_cart`= '.(int)$id_cart.', `delivery_date`= '.$delivery_date.', `minimal_time` = '.pSQL($minimal_time).', `maximal_time` = '.pSQL($maximal_time).'
					WHERE `id_order_detail` = '.(int)($id_order_detail)
					);
			}

			public function addOrderValues($id_cart, $delivery_date, $minimal_time, $maximal_time){
					Db::getInstance()->execute('
						INSERT INTO `'._DB_PREFIX_.'datetimecustomizer_order_detail`(`id_cart`,`delivery_date`,`minimal_time`, `maximal_time`)
						VALUES ('.(int)$id_cart.','.$delivery_date.','.pSQL($minimal_time).', '.pSQL($maximal_time).')
						');
			}
			
			public function _getSanitizedTime($time){
				$list = explode("-", $time);

				if($list){
					foreach ($list as &$l) {
						if(strpos($l, 'PM'))
							$l = intval($l) + 12;
						else
							$l = intval($l);
					}
				}
				return $list;
			}

			public function _isAlreadyDefinedOrder($id_cart){
			
				return (bool)Db::getInstance()->getValue('
					SELECT COUNT(*) FROM `'._DB_PREFIX_.'datetimecustomizer_order_detail` WHERE `id_cart` = '.(int)$id_cart);
			}

			public function _getIdOrderDetail($id_cart){
				return Db::getInstance()->getValue('
					SELECT `id_order_detail` FROM `'._DB_PREFIX_.'datetimecustomizer_order_detail` WHERE `id_cart` = '.(int)$id_cart);
			}


	public function hookOrderConfirmation($params)
	{
		$id_cart = Tools::getValue('id_cart');
		$id_order = Order::getOrderByCartId($id_cart);

		$this->changeOrderId($id_cart, $id_order);

		

	}
		public function changeOrderId($id_cart, $id_order)
		{
			Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'datetimecustomizer_order_detail`
					SET `id_order`= '.$id_order.'
					WHERE `id_cart` = '.(int)($id_cart)
					);
		}


	public function hookAdminOrder($params){

		$sql = 'SELECT * FROM `'._DB_PREFIX_.'datetimecustomizer_order_detail` WHERE `id_order`='.(int)(Tools::getValue('id_order'));
		if($result = Db::getInstance()->ExecuteS($sql))
		{
		        $timestamp = $result[0]['delivery_date'];
		        $min_time = $this->_getTwelveHourTime($result[0]['minimal_time']);
		        $max_time = $this->_getTwelveHourTime($result[0]['maximal_time']);
		        $orderid = $result[0]['id_order'];
		}
		$time = $min_time.'-'.$max_time;
		$date = date("d/m/Y", $timestamp);

		$this->smarty->assign(array(
			'deliverydate' => $date,
			'deliverytime' => $time,
			'orderid' => $orderid
		));	
		return $this->display(__FILE__, 'smarty/adminorders.tpl');
	}

}