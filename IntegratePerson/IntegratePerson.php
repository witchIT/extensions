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

define( 'INTEGRATEPERSON_VERSION', '0.0.2, 2009-11-02' );

$wgAutoConfirmCount = 10^10;

$wgIPDefaultImage = '';
$wgIPMaxImageSize = 100000;
$wgIPPersonType   = 'Person';

$wgExtensionFunctions[] = 'wfSetupIntegratePerswon';
$wgExtensionCredits['other'][] = $wgExtensionCredits['specialpage'][] = array(
	'name'        => 'IntegratePerson',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Integrates Person records (see RecordAdmin extension) into user preferences and account creation forms',
	'url'         => 'http://www.organicdesign.co.nz/Extension:IntegratePerson',
	'version'     => INTEGRATEPERSON_VERSION
);

# Process posted contact details
if ( isset( $_POST['wpFirstName'] ) ) $_POST['wpRealName'] = $_POST['wpFirstName'] . ' ' . $_POST['wpLastName'];

class wgIntegratePerson {

	var $JS = '';
	
	function __construct() {
		global $wgUser, $wgHooks, $wgMessageCache, $wgParser, $awcSearchByPref, $wgGroupPermissions, $wgWhitelistRead, $wgRequest;

		#$wgHooks['AbortNewAccount'][]            = $this;   # Save the extra prefs after posting account-create
		#$wgHooks['UserCreateForm'][]             = $this;   # Modify the create-account form to add new prefs
		$wgHooks['RenderPreferencesForm'][]       = $this;   # Add the new pref tab
		$wgHooks['SavePreferences'][]             = $this;   # Save the extra posted data to the user object's options
		$wgHooks['SpecialPageExecuteAfterPage'][] = $this;   # Modify preferences forms enctype and onsubmit
		$wgHooks['OutputPageBeforeHTML'][]        = $this;   # Add profile info to user pages

		# Modify login form messages to say email and name compulsory
#		$wgMessageCache->addMessages(array('prefs-help-email' => '<div class="error">Required</div>'));
#		$wgMessageCache->addMessages(array('prefs-help-realname' => '<div class="error">Required</div>'));

		# Process an uploaded profile image if one was posted
		if ( array_key_exists( 'wpProfileImage', $_FILES ) && $_FILES['wpProfileImage']['size'] > 0 )
			$this->processUploadedImage( $_FILES['wpProfileImage'] );

	}

	/**
	 * Get an option from the passed user or array (or it can be false)
	 */
	function getOption( &$obj, $opt, $default = '' ) {
		if ( is_object( $obj ) ) return $obj->getOption( $opt );
		if ( is_array( $obj ) && array_key_exists( $opt, $obj ) ) return $obj[$opt];
		return $default;
	}

	/**
	 * Hack to add onSubmit for validation and enctype for image upload to form tag
	 */
	function onSpecialPageExecuteAfterPage( &$special, $par, $func ) {
		global $wgOut;
		if ( $special->mName == 'Preferences' ) {
			$wgOut->mBodytext = str_replace(
				'<form',
				'<form onsubmit="return ipValidate(this)" enctype="multipart/form-data"',
				$wgOut->mBodytext
			);
		}
		return true;
	}
	
	/**
	 * Add the new prefs to a new tab in the preferences form
	 */
	function onRenderPreferencesForm( &$form, &$out ) {
		$out->addHTML( '<fieldset><legend>' . wfMsg( 'ip-preftab' ) . '</legend>' . wfMsg( 'ip-prefmsg' ) . $this->renderPrefs() . '</fieldset>' );
		return true;
	}

	/**
	 * Add the new prefs to the "create new account" form
	 */
	function onUserCreateForm( &$template ) {
		$template->data['header'] = $this->renderPrefs();
		return true;
	}
	
	/**
	 * Update the user object when the prefs from the form are saved
	 */
	function onSavePreferences( &$form, &$user ) {
		$this->setOptions( $user );
		return true;
	}
	
	/**
	 * Update the user object with extra prefs in the account-creation form
	 */
	function onAbortNewAccount( &$user, &$error ) {
		$this->setOptions( $user );
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
	 * Return the HTML for the Person preference tab
	 */
	function renderPrefs() {
		global $wgUser, $wgOut, $wgHooks, $wgSpecialRecordAdmin, $wgIPPersonType;
		$wgOut->addScript( "<script type='text/javascript'>{$this->JS}</script>" );
		$html = "<div class=ip-prefs><table>" .

		$wgSpecialRecordAdmin->preProcessForm( $wgIPPersonType );
		$wgSpecialRecordAdmin->examineForm();

		$html .= "</table></div>";
		$html .= "<script type='text/javascript'>ipAddValidation()</script>";
		return $html;
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

function wfSetupIntegratePerson() {
	global $wgIntegratePerson, $wgLanguageCode, $wgMessageCache;

	# Add the messages used by the specialpage
	if ( $wgLanguageCode == 'en' ) {
		$wgMessageCache->addMessages( array(
			'ip-preftab'   => "Person Record",
			'ip-prefmsg'   => "<br><b>Fill in your Personal details here...</b><br>"
		) );
	}

	# Instantiate the IntegratePerson singleton now that the environment is prepared
	$wgIntegratePerson = new IntegratePerson();

}
