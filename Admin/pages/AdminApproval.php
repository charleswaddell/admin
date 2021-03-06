<?php

/**
 * Generic admin approval page
 *
 * This class is intended to be a convenience base class. For a fully custom
 * approval page, inherit directly from AdminPage instead.
 *
 * @package   Admin
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class AdminApproval extends AdminPage
{
	// {{{ protected properties

	protected $id;
	protected $data_object;
	protected $pending_ids = array();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->getUiXml());

		$this->pending_ids = $this->getPendingIds();

		if (count($this->pending_ids) === 0) {
			$this->relocate();
		}

		$this->id = $this->app->initVar('id');
		if ($this->id === null) {
			$this->id = $this->getNextId();
		} else {
			$this->id = (integer)$this->id;
		}

		$this->initDataObject($this->id);
	}

	// }}}
	// {{{ abstract protected function initDataObject()

	abstract protected function initDataObject($id);

	// }}}
	// {{{ abstract protected function getPendingIds()

	abstract protected function getPendingIds();

	// }}}
	// {{{ protected function getNextId()

	protected function getNextId()
	{
		$found = ($this->data_object === null);

		foreach ($this->pending_ids as $id) {
			if ($found) {
				return $id;
			} elseif ($id === $this->id) {
				$found = true;
			}
		}

		return null;
	}

	// }}}
	// {{{ protected function getRemainingCount()

	protected function getRemainingCount()
	{
		$count = 0;
		$found = false;

		foreach ($this->pending_ids as $id) {
			if ($found) {
				$count++;
			} elseif ($id === $this->id) {
				$found = true;
			}
		}

		return $count;
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return __DIR__.'/approval.xml';
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('form');

		if ($form->isProcessed()) {
			$this->save();
			$this->relocate();
		}
	}

	// }}}
	// {{{ protected function save()

	protected function save()
	{
		if ($this->ui->getWidget('approve_button')->hasBeenClicked()) {
			$this->approve();
		} elseif ($this->ui->getWidget('delete_button')->hasBeenClicked()) {
			$this->delete();
		}
	}

	// }}}
	// {{{ abstract protected function approve()

	abstract protected function approve();

	// }}}
	// {{{ protected function delete()

	protected function delete()
	{
		$this->data_object->delete();
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$next_id = $this->getNextId();

		$relocate_uri = ($next_id === null)
			? ''
			: sprintf(
				'%s/%s?id=%d',
				$this->component,
				$this->subcomponent,
				$next_id
			);

		$this->app->relocate($relocate_uri);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('form');
		$form->action = $this->source.'?id='.$this->id;

		ob_start();
		$this->displayContent();
		$this->ui->getWidget('content')->content = ob_get_clean();

		$remaining = $this->getRemainingCount();
		if ($remaining > 0) {
			$locale = SwatI18NLocale::get();
			$this->ui->getWidget('status')->content = sprintf(
				Admin::_('%s%s%s still pending'),
				'<span class="pending">',
				SwatString::minimizeEntities($locale->formatNumber($remaining)),
				'</span>'
			);
		}
	}

	// }}}
	// {{{ abstract protected function displayContent()

	abstract protected function displayContent();

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(
			'packages/admin/styles/admin-approval-page.css'
		);
	}

	// }}}
}

?>
