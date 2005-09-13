<?php

require_once 'Admin/Admin/DBOrder.php';
require_once 'Admin/AdminUI.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for AdminComponents
 *
 * @package Admin
 * @copyright silverorange 2004
 */
class AdminComponentsOrder extends AdminDBOrder
{
	private $parent;

	protected function initInternal()
	{
		parent::initInternal();

		$this->parent = SwatApplication::initVar('parent');
		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('parent', $this->parent);
	}

	public function initDisplay()
	{
		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Admin::_('Order Components');
		parent::initDisplay();
	}

	public function loadData()
	{ 
		$where_clause = sprintf('section = %s',
			$this->app->db->quote($this->parent, 'integer'));

		$order_widget = $this->ui->getWidget('order');
		$order_widget->options = SwatDB::getOptionArray($this->app->db, 
			'admincomponents', 'title', 'id', 'displayorder, title', $where_clause);

		$sql = 'select sum(displayorder) from admincomponents where '.$where_clause;
		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}
	
	public function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'admincomponents', 'integer:displayorder',
			$index, 'integer:id', array($id));
	}
}

?>
