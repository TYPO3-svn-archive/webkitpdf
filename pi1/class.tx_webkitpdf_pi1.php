<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Dev-Team Typoheads <dev@typoheads.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
/**
 * Plugin 'Webkit PDFs' for the 'webkitpdf' extension.
 *
 * @author Reinhard Führicht <rf@typoheads.at>
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_webkitpdf_pi1 extends tslib_pibase {
	var $prefixId = 'tx_webkitpdf_pi1';
	var $scriptRelPath = 'pi1/class.tx_webkitpdf_pi1.php';	
	var $extKey = 'webkitpdf';	
	
	/**
	 * Init parameters. Reads TypoScript settings.
	 *
	 * @param	array		$conf: The PlugIn configuration
	 * @return	void
	 */
	protected function init($conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->scriptPath = t3lib_extMgm::extPath('webkitpdf') . 'res/';
		if($this->conf['customScriptPath']) {
			$this->scriptPath = $this->conf['customScriptPath'];
		}
		$this->outputPath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/typo3temp/';
		if($this->conf['customTempOutputPath']) {
			$this->outputPath = $this->conf['customTempOutputPath'];
		}
		$this->paramName = 'selected_pages';
		if($this->conf['customParameterName']) {
			$this->paramName = $this->conf['customParameterName'];
		}
		
		$this->filename = t3lib_div::tempnam($this->conf['filePrefix']) . '.pdf';
		$this->filenameOnly = basename($this->filename);
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content,$conf)	{
		$this->init($conf);

		$pids = t3lib_div::_GP($this->paramName);

		$content = '';
		if(!empty($pids)) {
			if(!is_array($pids)) {
				$pids_array = t3lib_div::trimExplode(',', $pids);
			} else {
				$pids_array = $pids;
			}
			$urls = array();
			foreach($pids_array as $pid) {
				$urls[] = $this->getPageURL($pid);
			}
			if(count($urls) > 0) {

				$scriptCall = 	$this->scriptPath. 'wkhtmltopdf ' .
								$this->buildScriptOptions() . ' ' .
								implode(' ', $urls) . ' ' .
								$this->filename;

				exec($scriptCall);

				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="' . $this->filenameOnly . '"');
				readfile($this->filename);
			}
		}
		
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Creates the parameters for the wkhtmltopdf call.
	 *
	 * @return string The parameter string
	 */
	protected function buildScriptOptions() {
		$options = array();
		$options[] = '--header-center [webpage]';
		
		if($this->conf['copyrightNotice']) {
			$options[] = '--footer-left " Copyright ' . date('Y', time()) . $this->conf['copyrightNotice'] . '"';
		}

		$options[] = '--footer-right [page]/[toPage]';
		$options[] = '--footer-font-size 6pt';
		$options[] = '--header-font-size 6pt';
		$options[] = '--margin-left 15mm';
		$options[] = '--margin-right 15mm';
		$options[] = '--margin-top 15mm';
		$options[] = '--margin-bottom 15mm';
		
		if($this->conf['additionalStyleSheet']) {
			$this->conf['additionalStyleSheet'] = $this->sanitizePath($this->conf['additionalStyleSheet']);
			$options[] = '--user-style-sheet ' . t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . $this->conf['additionalStyleSheet'];
				
		}
		
		return implode(' ', $options);
	}

	/**
	 * Makes sure that given path has a slash as first and last character
	 *
	 * @param	string		$path: The path to be sanitized
	 * @return	Sanitized path
	 */
	protected function sanitizePath($path) {
		
		// slash as last character
		if(substr($path, (strlen($path) - 1)) != '/') {
			$path .= '/';
		}
		
		//slash as first character
		if(substr($path, 0, 1) != '/') {
			$path = '/' . $path;
		}
		
		return $path;
	}

	/**
	 * Returns the URL of the page with ID $pid.
	 *
	 * @param	int		$pid: The page ID to be linked
	 * @return	The URL
	 */
	protected function getPageURL($pid) {
		$lang = t3lib_div::_GP('L');
		if($lang) {
			$params = array(
				'L' => $lang
			);
		}
		$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' . $this->cObj->getTypoLink_URL($pid, $params);
		return $url;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webkitpdf/pi1/class.tx_webkitpdf_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/webkitpdf/pi1/class.tx_webkitpdf_pi1.php']);
}

?>
