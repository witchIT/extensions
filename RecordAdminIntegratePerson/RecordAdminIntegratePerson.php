<?php
/**
 * IntegratePerson extension - Integrates Person records into user preferences and account creation forms
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) )           die( 'Not an entry point.' );
if ( !defined( 'RECORDADMIN_VERSION' ) ) die( 'This extension depends on the RecordAdmin extension' );

define( 'RAINTEGRATEPERSON_VERSION', '0.2.2, 2009-11-10' );

$wgAutoConfirmCount = 10^10;

$wgIPDefaultImage = '';
$wgIPMaxImageSize = 100000;
$wgIPPersonType   = 'Person';

$wgExtensionFunctions[] = 'wfSetupRAIntegratePerson';
$wgExtensionCredits['other'][] = $wgExtensionCredits['specialpage'][] = array(
	'name'        => 'RecordAdminIntegratePerson',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Integrates Person records (see RecordAdmin extension) into user preferences and account creation forms',
	'url'         => 'http://www.organicdesign.co.nz/Extension:IntegratePerson',
	'version'     => RAINTEGRATEPERSON_VERSION
);

# Process posted contact details
if ( isset( $_POST['wpFirstName'] ) ) $_POST['wpRealName'] = $_POST['wpFirstName'] . ' ' . $_POST['wpLastName'];

class RAIntegratePerson {

	function __construct() {
		global $wgHooks, $wgMessageCache, $wgParser;

		# Modify login form messages to say email and name compulsory
#		$wgMessageCache->addMessages(array('prefs-help-email' => '<div class="error">Required</div>'));
#		$wgMessageCache->addMessages(array('prefs-help-realname' => '<div class="error">Required</div>'));

		$wgHooks['PersonalUrls'][] = $this;
		$wgHooks['BeforePageDisplay'][] = $this;

		# Process an uploaded profile image if one was posted
		if ( array_key_exists( 'wpIPImage', $_FILES ) && $_FILES['wpIPImage']['size'] > 0 )
			$this->processUploadedImage( $_FILES['wpIPImage'] );

	}

	/**
	 * Modify personal URL's
	 */
	function onPersonalUrls( &$urls, &$title ) {
		global $wgUser;
		if ( $person = $wgUser->getRealName() ) {
			$userpage = array_shift( $urls );
			$talkpage = array_shift( $urls );
			$mycat    = str_replace( '$1', $person, '/Category:$1' );
			$mywork   = str_replace( '$1', $person, '/wiki/index.php?title=Category:Activities&Person=$1' );
			$urls     = array(
				'userpage' => $userpage,
				'talkpage' => $talkpage,
				'mycat'    => array( 'text' => 'My category', 'href' => $mycat  ),
				'mywork'   => array( 'text' => 'My worklog',  'href' => $mywork )
			) + $urls;
		}
		return true;
	}

	/**
	 * Determine which JS and page modifications should be added
	 */
	function onBeforePageDisplay( &$out, $skin = false ) {
		global $wgHooks, $wgTitle, $wgRequest;

		# Preferences
		if ( $wgTitle->getPrefixedText() == 'Special:Preferences' ) $this->modPreferences( $out );

		# Account-creation
		if ( $wgTitle->getPrefixedText() == 'Special:UserLogin' && $wgRequest->getText( 'type' ) == 'signup' ) $this->modAccountCreate( $out );

		return true;
	}

	/**
	 * Modify the prefs page
	 */
	function modPreferences( &$out ) {
		global $wgJsMimeType;

		# Add JS
		$out->addScript( "<script type='$wgJsMimeType'>
			function ipSubmit() {
				alert('foo');
			}
			function ipOnload() {
				$('#enotifwatchlistpages').attr('checked','yes');
				$('#enotifusertalkpages').attr('checked','yes');
				$('#enotifminoredits').attr('checked','');
				$('#wpEmailFlag').attr('checked','');
				$('#ccmeonemails').attr('checked','');
				$('#mw-preferences-form table tr:nth-child(3)').hide();
				$('#mw-preferences-form table tr:nth-child(4)').hide();
				$('#mw-preferences-form table tr:nth-child(5)').hide();
				$('#mw-preferences-form table tr:nth-child(6)').hide();
				$('#mw-preferences-form table tr:nth-child(7)').hide();
				$('#mw-preferences-form table tr:nth-child(8)').hide();
				$('#mw-preferences-form table tr:nth-child(9)').hide();
				$('#mw-preferences-form table tr:nth-child(10)').hide();
				$('#mw-preferences-form table tr:nth-child(11)').hide();
				$('#mw-preferences-form table tr:nth-child(12)').hide();
				$('#mw-preferences-form table tr:nth-child(15)').hide();
				$('#mw-preferences-form table tr:nth-child(16)').hide();
			}
			addOnloadHook(ipOnload);
		</script>" );

		# Modify the form
		$out->mBodytext = str_replace(
			'<form',
			'<form onsubmit="return ipSubmit(this)" enctype="multipart/form-data"',
			$out->mBodytext
		);

		return true;
	}

	/**
	 * Modify the account-create page
	 */
	function modAccountCreate( &$out ) {
		global $wgJsMimeType;
		
		# Add JS
		$out->addScript( "<script type='$wgJsMimeType'>
			function ipSubmit() {
				alert('foo');
			}
			function ipOnload() {
				alert('bar');
			}
			addOnloadHook(ipOnload);
		</script>" );

		# Modify the form
		$out->mBodytext = str_replace(
			'<form',
			'<form onsubmit="return ipSubmit(this)" enctype="multipart/form-data"',
			$out->mBodytext
		);

		return true;
	}

	/**
	 * Set fields in Person Record from posted form
	 */
	function setOptions( &$user ) {
		global $wgSpecialRecordAdmin;
		$posted = array();
		foreach ( $_REQUEST as $k => $v ) if ( preg_match( '|^ra_(\\w+)|', $k, $m ) ) $posted[$m[1]] = is_array( $v ) ? join( "\n", $v ) : $v;
		$wgSpecialRecordAdmin->filter = $posted;
	}
	
	/**
	 * Process uploaded image file
	 */
	function processUploadedImage( $file ) {
		global $wgUser, $wgDBname, $wgSiteNotice, $wgUploadDirectory, $wgIPMaxImageSize;
		$error = false;
		if ( !ereg( '^image/(jpeg|png|gif)$', $file['type'] ) ) $error = 'Uploaded file was not of a valid type!';
		if ( $file['size'] > $wgIPMaxImageSize )                $error = 'Profile images are restricted to a maximum of 100KBytes';
		if ( $file['error'] > 0 )                               $error = 'Uploaded error number ' . $file['error'] . ' occurred';
		if ( $error ) $wgSiteNotice = "<div class='errorbox'>$error</div>";
		else {
			$name = preg_replace( '%.+(\..+?)$%', "user-{$wgDBname}-{$wgUser->getId()}$1", $file['name'] );
			move_uploaded_file( $file['tmp_name'], "$wgUploadDirectory/$name" );
		}
	}

}

function wfSetupRAIntegratePerson() {
	global $wgRAIntegratePerson, $wgLanguageCode, $wgMessageCache;

	# Add the messages used by the specialpage
	if ( $wgLanguageCode == 'en' ) {
		$wgMessageCache->addMessages( array(
			'ip-preftab'   => "Person Record",
			'ip-prefmsg'   => "<br><b>Fill in your Personal details here...</b><br>"
		) );
	}

	# Instantiate the IntegratePerson singleton now that the environment is prepared
	$wgRAIntegratePerson = new RAIntegratePerson();

}
