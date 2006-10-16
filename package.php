<?php

require_once 'PEAR/PackageFileManager2.php';

i$version = '1.0.4';
$notes = <<<EOT
see ChangeLog
EOT;

$description =<<<EOT
Admin is a Swat based framework for building administration sites.

* An OO-style API
* A set of user-interface widgets
EOT;

$package = new PEAR_PackageFileManager2();
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$result = $package->setOptions(
	array(
		'filelistgenerator' => 'svn',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'Admin' => 'php',
			'www' => 'data',
			'sql' => 'data'
		),
	)
);

$package->setPackage('Admin');
$package->setSummary('Swat based adminstration framework');
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('LGPL', 'http://www.gnu.org/copyleft/lesser.html');

$package->setReleaseVersion($version);
$package->setReleaseStability('stable');
$package->setAPIVersion('0.0.1');
$package->setAPIStability('stable');
$package->setNotes($notes);

$package->addIgnore('package.php');

$package->addMaintainer('lead', 'nrf', 'Nathan Fredrickson', 'nathan@silverorange.com');
$package->addMaintainer('lead', 'gauthierm', 'Mike Gauthier', 'mike@silverorange.com');

$package->setPhpDep('5.1.5');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'Swat', 'pear.silverorange.com', '1.0.6');
$package->addPackageDepWithChannel('required', 'Site', 'pear.silverorange.com', '1.0.9');
$package->addPackageDepWithChannel('required', 'Store', 'pear.silverorange.com', '1.1.4');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.2.2');
$package->addPackageDepWithChannel('required', 'XML_RPCAjax', 'pear.silverorange.com', '0.9.1');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
