<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$extPath    = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY);
$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY);


if (TYPO3_MODE=="BE")	{
	// old: t3lib_extMgm::addModule("tools","txdmchooklistM1","",t3lib_extMgm::extPath($_EXTKEY)."mod1/");
	
  \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
      $_EXTKEY,
      'tools',
      'hooklist',
      '', # Position
      array('BackendHookList' => 'index,listAll,listLocal,listGlobal,listTypo3,listSystem'), # Controller array
      array(
          'access' => 'user,group',
          'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
          'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.hooklist.xml',
      )
  );
}
?>