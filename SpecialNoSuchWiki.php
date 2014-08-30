<?php
/**
 * Special page
 *
 * @file
 * @ingroup Extensions
 *
 * @author Lewis Cawte
 * @copyright Lewis Cawte © 2014
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialNoSuchWiki extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'NoSuchWiki' );
	}

	public function execute( $par ) {
		$out = $this->getOutput();
		$out->addModules( 'ext.nosuchwiki' );

		$reqSite = $this->getSite();
		$logContents = $this->getLogs( $reqSite );
		$this->formatAndOutputLogs( $reqSite, $logContents );
		//$this->addOtherWikis();
	}

	private function getSite() {
		if( isset( $_SERVER['HTTP_REFERER'] ) ) {
			$in = $_SERVER['HTTP_REFERER'];
			$escaped = htmlspecialchars( $in );
			$domain = explode( '/', $escaped );
			$wiki = explode( '.', $domain[2] );
			$reqWiki = $this->determineWiki( $wiki );
		} else {
			$reqWiki = NULL;
		}
		unset( $in, $escaped, $domain, $wiki );
		return $reqWiki;
	}

	private function determineWiki( $wiki ) {
		global $wgLangToCentralMap;

		$x = count($wiki);

		if( $wiki[0] == "www" && $wiki[1] == "shoutwiki" && $wiki[3] == "com" ) {
			// English language hub.
			$requestedWiki = "hub";
		} elseif ( array_key_exists( $wiki[$x-3], $wgLangToCentralMap ) && $wiki[$x-2] == "shoutwiki" ) {
			// Other language hubs.
			$requestedWiki = $wiki[$x-3];
		} elseif( isset( $wiki[$x-3] ) && isset( $wiki[$x-4] ) ) {
			$requestedWiki = $wiki[$x-4] . "." . $wiki[$x-3];
		} else {
			$requestedWiki = $wiki[$x-3];
		}
		return $requestedWiki;
	}
	private function getLogs( $deletedWiki) {
		//SELECT *  FROM `logging` inner join log_search on ls_log_id = log_id WHERE `log_type` = 'createwiki' AND `log_action` = 'delete' AND ls_field = 'deletedwiki' and ls_value = 'foo' ORDER BY `ls_log_id` DESC

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'logging',
			'*',
			array(
				'log_type' => 'createwiki',
				'log_action' => 'delete',
				'ls_field' => 'deletedwiki',
				'ls_value' => $deletedWiki,
			),
			__METHOD__,
			array(
				'LIMIT' => '2',
				'ORDER BY' => 'ls_log_id DESC',
			),
			array(
				'log_search' => array( 'INNER JOIN' => 'ls_log_id = log_id' )
			)
		);
		if ( $dbr->numRows( $res ) === 0 ) {
			return NULL;
		} else {
			return $res;
		}
	}

	private function formatAndOutputLogs( $requestedSite, $logs ) {
		$out = $this->getOutput();

		$out->addHTML( '<div id="nosuchwiki-logwrapper">');
		if( $logs === 0 ) {
			$this->msg( 'nosuchwiki-nevercreated')->parse();
		} else {
			$this->msg( 'nosuchwiki-requestedsite' )->params( $requestedSite )->parse();
			foreach( $logs as $log ) {
				$timestamp = $this->getLanguage()->userTimeAndDate( $log->log_timestamp, $this->getUser() );

				$out->addHTML( '<div class="nosuchwiki-logitem">' );
				$this->msg( 'nosuchwiki-logentry')
					->params( $requestedSite, $log->log_user_text, $timestamp, $log->log_comment )
					->parse();
				$out->addHTML( '</div>' );
			};
		};
		$out->addHTML( '</div>' );
	}
}