<?php

/**
 * Admin menu sub component
 *
 * Internal data class used internally within {@link AdminMenuStore}.
 *
 * @package   Admin
 * @copyright 2005-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class AdminMenuSubcomponent
{
	// {{{ public properties

	public $shortname;
	public $title;

	// }}}
	// {{{ public function __construct()

	public function __construct($shortname, $title)
	{
		$this->shortname = $shortname;
		$this->title = $title;
	}

	// }}}
}

?>
