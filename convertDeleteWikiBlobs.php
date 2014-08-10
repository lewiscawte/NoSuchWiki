<?php
/**
 * Set the correct include path for PHP so that we can run this script from
 * $IP/extensions/ShoutWikiMaintenance and we don't need to move this file to
 * $IP/maintenance/.
 */
ini_set( 'include_path', dirname( __FILE__ ) . '/../../maintenance' );

require_once( 'Maintenance.php' );

class ConvertDeleteWikiBlobs extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Adds the relevant relations like setRelations using old log_params and converts them to serialized PHP.';
		$this->addOption( 'run', 'Actually move stuff around instead of doing a dry run', false, false );
	}

	public function execute() {

	$this->output( "Start the script" );	
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			'logging',
			array(
				'log_id',
				'log_params',
			),
			array(
				'log_type' => 'createwiki',
				'log_action' => 'delete',
			),
			__METHOD__,
			array( 'ORDER BY' => 'log_timestamp ASC' )
		);
		
		foreach( $res as $row ) {
			$unblobbed = $this->unserializeRow( $row->log_params, $row->log_id );
			$this->output( "<" .rawurlencode($unblobbed['4::wikiname'] ). ">\n");
			$this->setRelationsData( $unblobbed['4::wikiname'], $row->log_id );
		}
		
	}
	
	private function unserializeRow( $loggingContents, $logID ) {
	wfSuppressWarnings();
		$check = unserialize( $loggingContents );
		wfRestoreWarnings();
		if( $check === false ) {
			$check = $this->convertOldFormat( $loggingContents );
			$this->reserializeRow( $check, $logID );
		}
		
		$loggingContents = $check;
		
		return $loggingContents;
	}
	
	private function convertOldFormat( $oldFormatData ) {
		// Function converts old format to new format.
		//var_dump( "prior convert", rawurlencode( $oldFormatData ) );
		$oldFormatCheck = explode("\n", $oldFormatData)[0];
		//var_dump( "mid convert", rawurlencode( $oldFormatCheck ) );
		$oldFormatCheck = trim( $oldFormatCheck );
		//var_dump( "post convert", rawurlencode( $oldFormatCheck ) );
		$explosion = explode( '.', $oldFormatCheck );
		if( count( $explosion ) === 0 ) {
			$lang = 'en';
		} elseif( 1 < count( $explosion ) ) {
			if( Language::isValidCode( $explosion[0] ) ) {
				$lang = $explosion[0];
			} else {
				$lang = 'en';
			}
		} else {
			$lang = 'en';
		}
		
		$newFormatData = array(
			'4::wikiname' => $oldFormatCheck,
			'5::language' => $lang,
		);
		
		return $newFormatData;	
	}
	
	private function reserializeRow( $blobContents, $id ) {
		// If a format update has occured, write the new format back to the database.
		$blobContents = serialize( $blobContents );

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update( 'logging', array( 'log_params' => $blobContents ), array( 'log_id' => $id ), __METHOD__ );
	}
	
	private function setRelationsData( $wikiname, $logid ) {
		// Act like ManualLogEntry::setRelations.
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insert(
			'log_search',
			array(
				'ls_field' => 'deletedwiki',
				'ls_value' => $wikiname,
				'ls_log_id' => $logid,
			),
			__METHOD__
		);
	}
}

$maintClass = "ConvertDeleteWikiBlobs";
require_once RUN_MAINTENANCE_IF_MAIN;
