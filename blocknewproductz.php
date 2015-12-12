<?php
/**
 * New products block (zapalm version): module for Prestashop 1.2-1.3
 *
 * @author zapalm <zapalm@ya.ru>
 * @copyright (c) 2010-2015, zapalm
 * @link http://prestashop.modulez.ru/en/frontend-features/20-new-products-block-zapalm-version.html The module's homepage
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

class BlockNewProductz extends Module
{
	public function __construct()
	{
		$this->name = 'blocknewproductz';
		$this->version = '1.0';
		$this->tab = 'Blocks';
		$this->author = 'zapalm';
		$this->need_instance = 0;
		$this->bootstrap = false;

		parent::__construct();

		$this->displayName = $this->l('New products block (zapalm version)');
		$this->description = $this->l('Adds a block that displaying the shop\'s newly added products.');
	}

	public function install()
	{
		return parent::install()
			&& $this->registerHook('rightColumn')
			&& Configuration::updateValue('NEW_PRODUCTS_NBR', 4)
			&& Configuration::updateValue('NEW_PRODUCTS_RANDOM', 1);
	}

	public function getContent()
	{
		$output = '<h2>'.$this->displayName.'</h2>';
		if (Tools::isSubmit('submitBlockNewProducts'))
		{
			if (!$productNbr = Tools::getValue('productNbr') || empty($productNbr))
				$output .= '<div class="alert error">'.$this->l('You should fill the "products displayed" field').'</div>';
			elseif (intval($productNbr) == 0)
				$output .= '<div class="alert error">'.$this->l('Invalid number of products.').'</div>';
			else
			{
				Configuration::updateValue('NEW_PRODUCTS_NBR', intval($productNbr));
				Configuration::updateValue('NEW_PRODUCTS_RANDOM', intval(Tools::getValue('NEW_PRODUCTS_RANDOM')));
				$output .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings updated').'</div>';
			}
		}
		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		$output = '
			<fieldset style="width: 400px"><legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Settings').'</legend>
				<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
					<label>'.$this->l('Products displayed').'</label>
					<div class="margin-form">
						<input type="text" name="productNbr" value="'.intval(Configuration::get('NEW_PRODUCTS_NBR')).'" />
						<p class="clear">'.$this->l('Set the number of products to be displayed in this block').'</p>
					</div>
					<label>'.$this->l('Show new products randomly').'</label>
					<div class="margin-form">
						<input type="checkbox" name="NEW_PRODUCTS_RANDOM"  value="1" '.(Configuration::get('NEW_PRODUCTS_RANDOM') ? 'checked="checked"' : '').' />
						<p class="clear">'.$this->l('Check it, if you whant to show new products randomly').'</p>
					</div>
					<center><input type="submit" name="submitBlockNewProducts" value="'.$this->l('Save').'" class="button" /></center>
				</form>
			</fieldset>
			<br class="clear">
		';

		return $output;
	}

	public function getNewProducts($id_lang, $nbProducts = 4, $orderBy = NULL, $orderWay = NULL, $random = true, $randomNumberProducts = 4)
	{
		global $cookie;

		if (empty($orderBy) || $orderBy == 'position')
			$orderBy = 'date_add';
		if (empty($orderWay))
			$orderWay = 'DESC';
		if ($orderBy == 'id_product' || $orderBy == 'price' || $orderBy == 'date_add')
			$orderByPrefix = 'p';
		elseif ($orderBy == 'name')
			$orderByPrefix = 'pl';
		if (!Validate::isOrderBy($orderBy) || !Validate::isOrderWay($orderWay))
			die(Tools::displayError());

		$sql = '
		SELECT p.*, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, p.`ean13`,
			i.`id_image`, il.`legend`, t.`rate`, m.`name` AS manufacturer_name, DATEDIFF(p.`date_add`, DATE_SUB(NOW(), INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY)) > 0 AS new, 
			(p.`price` * ((100 + (t.`rate`))/100) - IF((DATEDIFF(`reduction_from`, CURDATE()) <= 0 AND DATEDIFF(`reduction_to`, CURDATE()) >=0) OR `reduction_from` = `reduction_to`, IF(`reduction_price` > 0, `reduction_price`, (p.`price` * ((100 + (t.`rate`))/100) * `reduction_percent` / 100)),0)) AS orderprice 
		FROM `'._DB_PREFIX_.'product` p
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.intval($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_product` = p.`id_product` AND i.`cover` = 1)
		LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.intval($id_lang).')
		LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.`id_tax` = p.`id_tax`)
		LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
		WHERE p.`active` = 1
		AND p.`date_add` > DATE_SUB(NOW(), INTERVAL '.(Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20).' DAY)
		AND p.`id_product` IN (
			SELECT cp.`id_product`
			FROM `'._DB_PREFIX_.'category_group` cg
			LEFT JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.`id_category` = cg.`id_category`)
			WHERE cg.`id_group` '.(!$cookie->id_customer ? '= 1' : 'IN (SELECT id_group FROM '._DB_PREFIX_.'customer_group WHERE id_customer = '.intval($cookie->id_customer).')').'
		)';

		if ($random === true)
		{
			$sql .= ' ORDER BY RAND()';
			$sql .= ' LIMIT 0, '.intval($randomNumberProducts);
		}
		else
		{
			$sql .= ' ORDER BY '.(isset($orderByPrefix) ? $orderByPrefix.'.' : '').'`'.pSQL($orderBy).'` '.pSQL($orderWay).'
			LIMIT 0, '.intval($nbProducts);
		}

		$result = Db::getInstance()->ExecuteS($sql);

		if ($orderBy == 'price')
			Tools::orderbyPrice($result, $orderWay);

		if (!$result)
			return false;

		return Product::getProductsProperties(intval($id_lang), $result);
	}

	public function hookRightColumn($params)
	{
		global $smarty;

		$nb = intval(Configuration::get('NEW_PRODUCTS_NBR'));
		if (intval(Configuration::get('NEW_PRODUCTS_RANDOM')))
			$newProducts = $this->getNewProducts(intval($params['cookie']->id_lang), ($nb ? $nb : 4), NULL, NULL, true, ($nb ? $nb : 4));
		else
			$newProducts = Product::getNewProducts(intval($params['cookie']->id_lang), 0, ($nb ? $nb : 4));

		$smarty->assign(array ('new_products' => $newProducts, 'mediumSize' => Image::getSize('medium')));

		return $this->display(__FILE__, 'blocknewproductz.tpl');
	}

	public function hookLeftColumn($params)
	{
		return $this->hookRightColumn($params);
	}
}