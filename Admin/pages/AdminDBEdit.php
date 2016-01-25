<?php

require_once 'Admin/pages/AdminEdit.php';
require_once 'Swat/SwatMessage.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'SwatDB/exceptions/SwatDBException.php';

/**
 * Generic admin database edit page
 *
 * This class is intended to be a convenience base class. For a fully custom
 * edit page, inherit directly from AdminPage instead.
 *
 * @package   Admin
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class AdminDBEdit extends AdminEdit
{
	// process phase
	// {{{ protected function saveData()

	protected function saveData()
	{
		$relocate = true;
		$message = null;

		try {
			$transaction = new SwatDBTransaction($this->app->db);
			$relocate = $this->saveDBData();
			$transaction->commit();

			// saveDBData() is not historically expected to return a value, so
			// set relocate to true if it does not return a boolean.
			if (!is_bool($relocate)) {
				$relocate = true;
			}
		} catch (SwatDBException $e) {
			$transaction->rollback();

			$message = new SwatMessage(
				Admin::_(
					'A database error has occurred. The item was not saved.'
				),
				'system-error'
			);

			$e->processAndContinue();
			$relocate = false;
		} catch (SwatException $e) {
			$message = new SwatMessage(
				Admin::_(
					'An error has occurred. The item was not saved.'
				),
				'system-error'
			);

			$e->processAndContinue();
			$relocate = false;
		}

		if ($message !== null) {
			$this->app->messages->add($message);
		}

		return $relocate;
	}

	// }}}
	// {{{ abstract protected function saveDBData()

	/**
	 * Save the data from the database
	 *
	 * This method is called to save data from the widgets after processing.
	 * Sub-classes should implement this method and perform whatever actions
	 * are necessary to store the data. Widgets can be accessed through the
	 * $ui class variable.
	 *
	 * @return boolean true if saveDBData was successful.
	 */
	abstract protected function saveDBData();

	// }}}

	// build phase
	// {{{ protected function loadData()

	protected function loadData()
	{
		$this->loadDBData();
		return true;
	}

	// }}}
	// {{{ abstract protected function loadDBData()

	/**
	 * Load the data from the database
	 *
	 * This method is called to load data to be edited into the widgets.
	 * Sub-classes should implement this method and perform whatever actions
	 * are necessary to obtain the data. Widgets can be accessed through the
	 * $ui class variable.
	 */
	abstract protected function loadDBData();

	// }}}
}

?>
