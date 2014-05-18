<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Markus Blaschke (TEQneers GmbH & Co. KG) <blaschke@teqneers.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class Tx_CiHooklist_Controller_BackendHookListController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * Initializes the Module
	 *
	 * @access	public
	 * @return	void
	 */
	function initializeAction()	{
    // RENDERING
		$this->template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
    $pageRenderer = $this->template->getPageRenderer();

    // $pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ci_hooklist') . 'Resources/Public/x.js');
    // $pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ci_hooklist') . 'Resources/Public/Backend/Css/x.css');
	}

  /**
   * Main action
   */
  public function indexAction() {
		$this->moduleContent(1);
  }
	
  public function listAllAction() {
		$this->moduleContent(2);
  }
	
  public function listTypo3Action() {
		$this->moduleContent(3);
  }
	
  public function listLocalAction() {
		$this->moduleContent(4);
  }
	
  public function listGlobalAction() {
		$this->moduleContent(5);
  }
	
  public function listSystemAction() {
		$this->moduleContent(6);
  }
	
  /**
   * Translate key
   *
   * @param   string      $key        Translation key
   * @param   NULL|array  $arguments  Arguments (vsprintf)
   * @return  NULL|string
   */
  protected function _translate($key, $arguments = NULL) {
  	 return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, $this->extensionName, $arguments);
	}

	/**
	 * Generates the module content
	 *
	 * @access	protected
	 * @return	void
	 */
	function moduleContent($functionNum=1) {
		$baseDir		= '';
		switch($functionNum) {
			case 2:
				// all sources
        $baseDir = PATH_site;
				break;

      case 3:
				// the Typo3 sources
				$baseDir = PATH_site . 'typo3_src';
				break;

      case 4:
				// local extensions
				$baseDir = PATH_site . 'typo3conf/ext';
				break;

      case 5:
				// global extensions
				$baseDir = PATH_site . 'typo3/ext';
				break;

      case 6:
				// system extensions
				$baseDir = PATH_site . 'typo3/sysext';
				break;

      case 1:
			default:
				if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['moduleFunction'])) {

					foreach($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['moduleFunction'] as $funcRef) {
						$params = array(&$baseDir);
						GeneralUtility::callUserFunction($funcRef, $params, $this);
					} // end: foreach
				} // end: if

				$this->view->assign('hasTokenizer', function_exists('token_get_all'));
		} // end: switch

		// if a basedir is set, start fetching files and generate the tokens
		if ($baseDir != '') {

			if (ini_get('safe_mode') == false
				|| strtolower(ini_get('safe_mode')) != 'off') {
				// if safemode is NOT active, make sure the analysis runs through
				set_time_limit(0);
			} // end: if

			$hookList 	= '';
			$files		= $this->recFind($baseDir, '\.php$');
			$content	= array();
			$message = "";

			foreach ($files as $file) {
				$content[] = $this->generateHookList($file);
			}

			if (!empty($content)) {
				$message = $this->_translate('hooksFound') . $this->_translate('function' . $functionNum);
			} else {
				$message = $this->_translate('noHooksFound') . $this->_translate('function' . $functionNum);
			} 
			
			$this->view->assign('message', $message);
			$this->view->assign('content', $content);
		} // end: if
	}


	/**
	* Reads in the requested file and starts the tokenizer to generate
	* the list of hooks.
	*
	* @param	string	$file	file to find tokens in
	*
	* @access	protected
	* @return	string
	*/
	function generateHookList($file) {
		$returnValue	= '';
		$hooks			= array();

		// tokenize the file content

		if (is_file($file)
			&& is_readable($file)) {

			$tokens	= token_get_all(file_get_contents($file));

			// remove all whitespace tokens
			$tokens = $this->removeWhitespace($tokens);

			// walk through the tokens and find hooks
			$hooks	= $this->findHooks($tokens);

			// append list of hooks to content
			foreach ($hooks as $hookPoint) {

				if (count($hookPoint['hooks']) > 0) {

					if ($hookPoint['class'] != '') {
						$returnValue .= '<strong>class:</strong> ' . $hookPoint['class'] . "<br />\n";
					} // end: if

					if ($hookPoint['function'] != '') {
						$returnValue 	.= '<strong>function:</strong> ' . $hookPoint['function'] . "()<br />\n";
					} // end: if

					$returnValue .= "<ul>\n";

					foreach ($hookPoint['hooks'] as $hook) {
						$returnValue	.= '<li>' . $hook . "</li>\n";
					} // end: foreach

					$returnValue .= "</ul>\n";
				} // end: if
			} // end: foreach
		} // end: if

		return $returnValue;
	}

	/**
	* Iterates over an array of tokens and removes the tokens
	* that happen to be whitespace.
	*
	* @param	array		$tokens		Array of tokens
	* @access	protected
	* @return	array
	*/
	function removeWhitespace($tokens) {
		$returnValue	= array();
		$tokenNum		= count($tokens);

		for ($i = 0; $i < $tokenNum; $i++) {

			if (is_array($tokens[$i])) {
				// complex token

				switch ($tokens[$i][0]) {
					// http://us3.php.net/manual/en/tokens.php
					case T_ENCAPSED_AND_WHITESPACE:
					case T_WHITESPACE:
						// not doing anything here, get rid of whitespace
						break;

					default:
						// keep this token
						$returnValue[] = $tokens[$i];
				} // end: switch

			} else {
				// character token, always keep it
				$returnValue[] = $tokens[$i];
			} // end: if
		} // end: for

		return $returnValue;
	}

	/**
	* Walks through an array of PHP tokens and identifies every hook
	* according with the class and fundtion it resides in.
	*
	* @param	array		$tokens		array of PHP tokens
	 * @access	protected
	* @return	array
	*/
	function findHooks($tokens) {
		$returnValue		= array();
		$returnCount		= -1;
		$inClass			= '';
		$inClassDepth		= 0;
		$inFunction			= '';
		$inFunctionDepth	= 0;
		$nestingDepth		= 0;
		$hookString			= '';
		$numOfTokens		= count($tokens);

		for ($i = 0; $i < $numOfTokens; $i++) {

			if (is_array($tokens[$i])) {
				// complex token
				list($index, $value) = $tokens[$i];

				switch ($index) {

					case T_CLASS:
						// entering a class
						$i++;

						if (is_array($tokens[$i])
							&& $tokens[$i][0] == T_STRING) {
							$inClass		= $tokens[$i][1];
							$inClassDepth	= $nestingDepth;
						} // end: if
						break;

					case T_FUNCTION:
						// entering a function
						$i++;

						if (is_array($tokens[$i])
							&& $tokens[$i][0] == T_STRING) {
							$inFunction			= $tokens[$i][1];
							$inFunctionDepth	= $nestingDepth;
						} // end: if

						$returnCount++;
						$returnValue[$returnCount] = array(
							'class' 	=> $inClass,
							'function'	=> $inFunction,
							'hooks'		=> array(),
						);
						break;

					case T_FOREACH:
						/* probably a loop over hooks, we have to check
							...and do some probability guessing */

						if (
								is_array($tokens[$i+2]) && $tokens[$i+2][0] == T_VARIABLE && $tokens[$i+2][1] == '$GLOBALS'
							&&	is_string($tokens[$i+3]) && $tokens[$i+3] == '['
							&&	is_array($tokens[$i+4]) && $tokens[$i+4][0] == T_CONSTANT_ENCAPSED_STRING && $tokens[$i+4][1] == '\'TYPO3_CONF_VARS\''
							&&	is_string($tokens[$i+5]) && $tokens[$i+5] == ']'
							&&	is_string($tokens[$i+6]) && $tokens[$i+6] == '['
							&&	is_array($tokens[$i+7]) && $tokens[$i+7][0] == T_CONSTANT_ENCAPSED_STRING && $tokens[$i+7][1] == '\'SC_OPTIONS\''
							&&	is_string($tokens[$i+8]) && $tokens[$i+8] == ']'
							&&	is_string($tokens[$i+9]) && $tokens[$i+9] == '['
							&&	is_array($tokens[$i+10]) && $tokens[$i+10][0] == T_CONSTANT_ENCAPSED_STRING
							&&	is_string($tokens[$i+11]) && $tokens[$i+11] == ']'
							&&	is_string($tokens[$i+12]) && $tokens[$i+12] == '['
							&&	is_array($tokens[$i+13]) && $tokens[$i+13][0] == T_CONSTANT_ENCAPSED_STRING
							&&	is_string($tokens[$i+14]) && $tokens[$i+14] == ']'
						) {
							// hooks that are triggered via $GLOBALS
							$returnValue[$returnCount]['hooks'][] = '$TYPO3_CONF_VARS'
																	. $tokens[$i+6]
																	. $tokens[$i+7][1]
																	. $tokens[$i+8]
																	. $tokens[$i+9]
																	. $tokens[$i+10][1]
																	. $tokens[$i+11]
																	. $tokens[$i+12]
																	. $tokens[$i+13][1]
																	. $tokens[$i+14];

						} elseif (
								is_array($tokens[$i+2]) && $tokens[$i+2][0] == T_VARIABLE && $tokens[$i+2][1] == '$this'
							&&	is_array($tokens[$i+3]) && $tokens[$i+3][0] == T_OBJECT_OPERATOR
							&&  is_array($tokens[$i+4]) && $tokens[$i+4][0] == T_STRING && $tokens[$i+4][1] == 'TYPO3_CONF_VARS'
							&&	is_string($tokens[$i+5]) && $tokens[$i+5] == '['
							&&	is_array($tokens[$i+6]) && $tokens[$i+6][0] == T_CONSTANT_ENCAPSED_STRING && $tokens[$i+6][1] == '\'SC_OPTIONS\''
							&&	is_string($tokens[$i+7]) && $tokens[$i+7] == ']'
							&&	is_string($tokens[$i+8]) && $tokens[$i+8] == '['
							&&	is_array($tokens[$i+9]) && $tokens[$i+9][0] == T_CONSTANT_ENCAPSED_STRING
							&&	is_string($tokens[$i+10]) && $tokens[$i+10] == ']'
							&&	is_string($tokens[$i+11]) && $tokens[$i+11] == '['
							&&	is_array($tokens[$i+12]) && $tokens[$i+12][0] == T_CONSTANT_ENCAPSED_STRING
							&&	is_string($tokens[$i+13]) && $tokens[$i+13] == ']'
						) {
							// hooks that are triggered via $this->
							$returnValue[$returnCount]['hooks'][] = '$'
																	. $tokens[$i+4][1]
																	. $tokens[$i+5]
																	. $tokens[$i+6][1]
																	. $tokens[$i+7]
																	. $tokens[$i+8]
																	. $tokens[$i+9][1]
																	. $tokens[$i+10]
																	. $tokens[$i+11]
																	. $tokens[$i+12][1]
																	. $tokens[$i+13];

						} elseif (
								is_array($tokens[$i+2]) && $tokens[$i+2][0] == T_VARIABLE && $tokens[$i+2][1] == '$TYPO3_CONF_VARS'
							&&	is_string($tokens[$i+3]) && $tokens[$i+3] == '['
							&&	is_array($tokens[$i+4]) && $tokens[$i+4][0] == T_CONSTANT_ENCAPSED_STRING && $tokens[$i+4][1] == '\'SC_OPTIONS\''
							&&	is_string($tokens[$i+5]) && $tokens[$i+5] == ']'
							&&	is_string($tokens[$i+6]) && $tokens[$i+6] == '['
							&&	is_array($tokens[$i+7]) && $tokens[$i+7][0] == T_CONSTANT_ENCAPSED_STRING
							&&	is_string($tokens[$i+8]) && $tokens[$i+8] == ']'
							&&	is_string($tokens[$i+9]) && $tokens[$i+9] == '['
							&&	is_array($tokens[$i+10]) && $tokens[$i+10][0] == T_CONSTANT_ENCAPSED_STRING
							&&	is_string($tokens[$i+11]) && $tokens[$i+11] == ']'
						) {
							// hooks where $TYPO3_CONF_VARS is accessible directly
							$returnValue[$returnCount]['hooks'][] = $tokens[$i+2][1]
																	. $tokens[$i+3]
																	. $tokens[$i+4][1]
																	. $tokens[$i+5]
																	. $tokens[$i+6]
																	. $tokens[$i+7][1]
																	. $tokens[$i+8]
																	. $tokens[$i+9]
																	. $tokens[$i+10][1]
																	. $tokens[$i+11];
						} // end: if

						// add your own hook-detection logic here
						if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['hookDetection'])) {

							foreach($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['hookDetection'] as $funcRef) {
								$params = array(&$returnValue, $tokens, $returnCount, $i);
								GeneralUtility::callUserFunction($funcRef, $params, $this);
							} // end: foreach
						} // end: if
						break;

					default:
					// add additional hook-detection logics here
					if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['hookDetectionDefault'])) {

						foreach($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['hookDetectionDefault'] as $funcRef) {
							$params = array(&$returnValue, $tokens, $returnCount, $i);
							GeneralUtility::callUserFunction($funcRef, $params, $this);
						} // end: foreach
					} // end: if
				} // end: switch

			} else {
				// character token - we need to count curly braces
				switch ($tokens[$i]) {

					case '{':
						$nestingDepth++;
						break;

					case '}':
						// decrease nesting
						$nestingDepth--;

						if ($nestingDepth < $inClassDepth) {
							$inClass		= '';
							$inClassDepth	= 0;

						} elseif ($nestingDepth < $inFunctionDepth) {
							$inFunction 		= '';
							$inFunctionDepth 	= 0;
						} // end: if
						break;

					default:
					// add additional hook-detection logics here
					if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['charToken'])) {

						foreach($TYPO3_CONF_VARS['SC_OPTIONS']['ci_hooklist/Classes/Controller/BackendHookListController.php']['charToken'] as $funcRef) {
							$params = array(&$returnValue, $tokens, $returnCount, $i);
							GeneralUtility::callUserFunction($funcRef, $params, $this);
						} // end: foreach
					} // end: if
				} // end: switch

			} // end: if
		} // end: foreach

		return $returnValue;
	}

    /**
    * Generates an array with all files within the given directory and it's subdirectories
    * that match the pattern. If the second parameter is skipped, all files are included
    * in the array.
    *
	* @access	protected
    * @param  	string  $s_directory Directorytree to search the files in
    * @param  	string  $s_pattern Pattern (Perl syntax) the files must match. Default is (.*?) (all files)
    * @return 	array
    */
    function recFind($directory, $pattern = "(.*?)") {
		clearstatcache();
		$files = array();

		if ($dh = @opendir($directory)) {
			$i = 0;

			while ($file = readdir($dh)) {

				if (($file != '.') && ($file != '..')) {
					$tempdir = $directory . '/' . $file;

					if (is_dir($tempdir)) {
						$files = array_merge($files, $this->recFind($tempdir, $pattern));

					} else {

						if (preg_match('/' . $pattern . '/', $file)) {
							$files[] = $tempdir;
						} // end: if
					} // end: if
					$i++;
				} // end: if
			} // end: while
		} // end: if
		@closedir($dh);

		return $files;
    }
}

?>