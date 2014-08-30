<?php
/**
 * NoSuchWiki extension
 * Let's a user know why the wiki they requested does not exist.
 *
 * @file
 * @ingroup Extensions
 * @version 0.1
 * @date 30 August 2014
 * @author Lewis Cawte <lewis@lewiscawte.me>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @link https://www.mediawiki.org/wiki/Extension:NoSuchWiki Documentation
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'NoSuchWiki',
	'descriptionmsg' => 'nosuchwiki-desc',
	'version' => '0.1',
	'author' => 'Lewis Cawte',
	'url' => 'https://www.mediawiki.org/wiki/Extension:NoSuchWiki',
);

$dir = __DIR__ . '/';
$wgExtensionMessageFiles['NoSuchWiki'] = $dir . 'NoSuchWiki.i18n.php';

$wgAutoloadClasses['SpecialNoSuchWiki'] = $dir . 'SpecialNoSuchWiki.php';
