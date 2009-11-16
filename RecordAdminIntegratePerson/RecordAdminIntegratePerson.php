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

define( 'RAINTEGRATEPERSON_VERSION', '0.2.5, 2009-11-16' );

$wgAutoConfirmCount = 10^10;

$wgIPDefaultImage = '';
$wgIPMaxImageSize = 100000;
$wgIPPersonType   = 'Person';

$wgExtensionFunctions[] = 'wfSetupRAIntegratePerson';
$wgExtensionCredits['other'][] = array(
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

				// Defaults for email options
				$('#mw-input-enotifwatchlistpages').attr('checked','yes');
				$('#mw-input-enotifusertalkpages').attr('checked','yes');
				$('#mw-input-enotifminoredits').attr('checked','');
				$('#wpEmailFlag').attr('checked','');
				$('#mw-input-ccmeonemails').attr('checked','');

				$('fieldset#prefsection-0 fieldset:nth-child(6)').hide(); // internationalisation
				$('fieldset#prefsection-0 fieldset:nth-child(7)').hide(); // signature
				$('fieldset#prefsection-0 fieldset:nth-child(8)').hide(); // email options
				
				$('table#mw-htmlform-info tr:nth-child(6)').hide();       // real name
				$('table#mw-htmlform-info tr:nth-child(7)').hide();       // real name comment
				$('table#mw-htmlform-info tr:nth-child(8)').hide();       // gender
				$('table#mw-htmlform-info tr:nth-child(9)').hide();       // gender comment
				
			}
			addOnloadHook(ipOnload);
		</script>" );

		# Modify the forms enctype to allow uploaded image
		$out->mBodytext = str_replace(
			'<form',
			'<form onsubmit="return ipSubmit(this)" enctype="multipart/form-data"',
			$out->mBodytext
		);

		# Integrate the Person record
		$form = $this->getForm();
		$out->mBodytext = preg_replace(
			"|(<fieldset>\s*<legend>Internationalisation)|s",
			"$form$1",
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
				$('fieldset#login table tr:nth-child(4)').hide(); // email
				$('fieldset#login table tr:nth-child(5)').hide(); // real name
				$('fieldset#login table tr:nth-child(7)').hide(); // submit buttons
			}
			addOnloadHook(ipOnload);
		</script>" );

		# Modify the forms enctype to allow uploaded image
		$out->mBodytext = str_replace(
			'<form',
			'<form onsubmit="return ipSubmit(this)" enctype="multipart/form-data"',
			$out->mBodytext
		);

		# Integrate the Person record
		$submit = '<input type="submit" name="wpCreateaccount" id="wpCreateaccount" value="Create account" />
					<input type="submit" name="wpCreateaccountMail" id="wpCreateaccountMail" value="by e-mail" />';

		$form = $this->getForm();
		$out->mBodytext = preg_replace(
			"|(<table.+?</table>)|s",
			"<fieldset id='login'><legend>Login details</legend>$1</fieldset>$form$submit",
			$out->mBodytext
		);

		return true;
	}

	/**
	 * Get the HTML for the Person form from RecordAdmin
	 */
	function getForm() {
		global $wgSpecialRecordAdmin;
		$wgSpecialRecordAdmin->preProcessForm( 'Person' );
		$form = $wgSpecialRecordAdmin->form ;
		$form = preg_replace( "|(^.+)<tr.+?Administration.+$|ms", "$1</table></td></tr></table></fieldset>", $form );
		return $form;
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
