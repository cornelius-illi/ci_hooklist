<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "dmc_hooklist".
 *
 * Auto generated 12-05-2014 11:15
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Hooklist',
	'description' => 'Checks the installed codebase for existing hook-points which enable you to extend the functionality of TYPO3 without using XCLASS. The hook-points are presented in a list.',
	'category' => 'module',
	'shy' => 0,
	'version' => '1.1.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Cornelius Illi, Dominique Stender',
	'author_email' => 'mail@corneliusilli.de, dst@dmc.de',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' => 
	array (
		'depends' => 
		 array (
			'typo3' => '6.0.0-6.2.99',
			'' => '',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

?>