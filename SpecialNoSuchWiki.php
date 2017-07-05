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

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'NoSuchWiki' );
	}

	public function execute( $par ) {
		$out = $this->getOutput();
		$out->addModuleStyles( 'ext.nosuchwiki' );

		$reqSite = $this->getSite( $par );
		$this->formatAndOutputLogs( $reqSite );
		//$this->addOtherWikis();
	}

	/**
	 * Get and parse the requested site.
	 *
	 * @return string
	 */
	private function getSite( $par ) {
		$par = htmlspecialchars( $par );

		if( isset( $par ) ) {
			if( $this->requestedWikiCheck( $par ) ) {
				$reqWiki = $par;
			} else { $reqWiki = null; }
		} else {
			$reqWiki = null;
		}
		unset( $par );
		return $reqWiki;
	}

	private function requestedWikiCheck( $wiki ) {
		if( isset( $wiki ) ) {
			$wikiArray = explode( '.', $wiki );
			$count = count( $wikiArray );
			if ( 0 < $count && $count < 3 ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	private function getLogs( $deletedWiki ) {
		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			array( 'logging', 'log_search' ),
			'*',
			array(
				'log_type' => 'createwiki',
				'log_action' => 'delete',
				'ls_field' => 'deletedwiki',
				'ls_value' => $deletedWiki
			),
			__METHOD__,
			array(
				'LIMIT' => 2,
				'ORDER BY' => 'ls_log_id DESC',
			),
			array(
				'log_search' => array( 'INNER JOIN', 'log_id = ls_log_id' )
			)
		);
		if ( $dbr->numRows( $res ) === 0 ) {
			return null;
		} else {
			return $res;
		}
	}

	private function formatAndOutputLogs( $requestedSite ) {
		$out = $this->getOutput();
		$logs = $this->getLogs( $requestedSite );

		$out->addHTML( '<div id="nosuchwiki-logwrapper">');
		if( $logs === null ) {
			$out->addHTML( $this->msg( 'nosuchwiki-nevercreated')->parse() );
		} else {
			$out->addHTML( $out->msg( 'nosuchwiki-requestedsite' )->params( $requestedSite )->parse() );
			foreach( $logs as $log ) {
				$timestamp = $this->getLanguage()->userTimeAndDate( $log->log_timestamp, $this->getUser() );

				$out->addHTML( '<div class="nosuchwiki-logitem">' );
				$out->addHTML( $out->msg( 'nosuchwiki-logentry')
					->params( $requestedSite, $log->log_user_text, $timestamp, $log->log_comment )
					->parse() );
				$out->addHTML( '</div>' );
			};
		};
		$out->addHTML( '</div>' );

		unset( $logs );
	}
}
