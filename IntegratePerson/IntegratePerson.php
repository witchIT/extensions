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

define( 'INTEGRATEPERSON_VERSION', '0.0.1, 2009-11-01' );

$wgAutoConfirmCount = 10^10;

$awcDefaultImage = '';
$awcMaxImageSize = 100000;

$wgExtensionFunctions[] = 'wfSetupIntegratePerswon';
$wgExtensionCredits['other'][] = $wgExtensionCredits['specialpage'][] = array(
	'name'        => 'AWCmod',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Integrates Person records into user preferences and account creation forms',
	'url'         => 'http://www.organicdesign.co.nz/Extension:IntegratePerson',
	'version'     => INTEGRATEPERSON_VERSION
);

# Process posted contact details
if ( isset( $_POST['wpFirstName'] ) ) $_POST['wpRealName'] = $_POST['wpFirstName'] . ' ' . $_POST['wpLastName'];

class wgIntegratePerson {

	var $JS = '';
	var $searchPrefs = array();
	var $skillsOptions = '';
	var $stateOptions  = '';
	var $countyOptions = '';
	
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
	 * Prepare the dropdown list content if using a special page containing one of our forms
	 */
	function buildOptionLists( &$user = false ) {
		include( dirname( __FILE__ ) . "/states.php" );
		$this->JS = $awcJS;
		
		# Build skills option list
		$skills = $this->getOption( $user, 'skills' );
		$this->skillsOptions = '<option/>';
		foreach ( array(
			'Concerned Citizen',
			'Accountant',
			'Lawyer',
			'Tax Advisor',
			'Tax Agent',
			'Assessor',
			'Realtor'
		) as $s ) {
			$selected = $skills == $s ? ' selected' : '';
			$this->skillsOptions .= "<option$selected>$s</option>\n";
		}

		# Build state options list
		$state = $this->getOption( $user, 'state' );
		$this->stateOptions = '<option value="">Enter state...</option>';
		foreach ( array_keys( $awcStates ) as $s ) {
			$selected = $state == $s ? ' selected' : '';
			$this->stateOptions .= "<option$selected>$s</option>\n";
		}

		# Build county options list
		$county = $this->getOption( $user, 'county' );
		$this->countyOptions = '<option value="">Enter county...</option>';
		if ( $state ) {
			foreach ( split( ',', $awcStates[$state] ) as $c ) {
				$c = substr( $c, 1, -1 );
				$selected = $county == $c ? ' selected' : '';
				$this->countyOptions .= "<option$selected>$c</option>\n";
			}
		}
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
				'<form onsubmit="return awcValidate(this)" enctype="multipart/form-data"',
				$wgOut->mBodytext
			);
		}
		return true;
	}
	
	/**
	 * Add the new prefs to a new tab in the preferences form
	 */
	function onRenderPreferencesForm( &$form, &$out ) {
		$out->addHTML( '<fieldset><legend>' . wfMsg( 'awc-preftab' ) . '</legend>' . wfMsg( 'awc-prefmsg' ) . $this->renderAWCprefs() . '</fieldset>' );
		return true;
	}

	/**
	 * Add the new prefs to the "create new account" form
	 */
	function onUserCreateForm( &$template ) {
		$template->data['header'] = $this->renderAWCprefs();
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
	 * Set user options from posted form
	 */
	function setOptions( &$user ) {
		global $wgRequest;
		$user->setOption('firstname', $wgRequest->getVal('wpFirstName'));
		$user->setOption('lastname',  $wgRequest->getVal('wpLastName'));
		$user->setOption('phone',     $wgRequest->getVal('wpPhone'));
		$user->setOption('mobile',    $wgRequest->getVal('wpMobile'));
		$user->setOption('business',  $wgRequest->getVal('wpBusiness'));
		$user->setOption('skills',    $wgRequest->getVal('wpSkills'));
		$user->setOption('website',   $wgRequest->getVal('wpWebsite'));
		$user->setOption('fax',       $wgRequest->getVal('wpFax'));
		$user->setOption('address',   $wgRequest->getVal('wpAddress'));
		$user->setOption('address2',  $wgRequest->getVal('wpAddress2'));
		$user->setOption('city',      $wgRequest->getVal('wpCity'));
		$user->setOption('state',     $wgRequest->getVal('wpState'));
		$user->setOption('county',    $wgRequest->getVal('wpCounty'));
		$user->setOption('zipcode',   $wgRequest->getVal('wpZipCode'));
		$user->setOption('logo',      $wgRequest->getVal('wpProfileImage'));
		$user->setOption('notes',     $wgRequest->getVal('wpNotes'));
	}
	
	/**
	 * Return the HTML for the AWC preference inputs
	 */
	function renderAWCprefs() {
		global $wgUser, $wgOut, $wgHooks;
		$this->buildOptionLists($wgUser);
		$wgOut->addScript( "<script type='text/javascript'>{$this->JS}</script>" );

		$html = "<div class=awc-loginprefs><table>" .
		$this->addRow(
			wfLabel('First Name', 'wpFirstName'),
			wfInput('wpFirstName', 20, $wgUser->getOption('firstname'), array('id' => 'wpFirstName'))
		) .
		$this->addRow(
			wfLabel('Last Name', 'wpLastName'),
			wfInput('wpLastName', 20, $wgUser->getOption('lastname'), array('id' => 'wpLastName'))
		) .
		$this->addRow(
			wfLabel('Voice Phone', 'wpPhone'),
			wfInput('wpPhone', 10, $wgUser->getOption('phone'), array('id' => 'wpPhone'))
		) .
		$this->addRow(
			wfLabel('Mobile Phone', 'wpMobile'),
			wfInput('wpMobile', 10, $wgUser->getOption('mobile'), array('id' => 'wpMobile'))
		) .
		$this->addRow(
			wfLabel('Fax', 'wpFax'),
			wfInput('wpFax', 10, $wgUser->getOption('fax'), array('id' => 'wpFax'))
		) .
		$this->addRow(
			wfLabel('Expertise', 'wpSkills'),
			'<select name="wpSkills" id="wpSkills">'.$this->skillsOptions.'</select>'
		) .
		$this->addRow(
			wfLabel('Business Name', 'wpBusiness'),
			wfInput('wpBusiness', 20, $wgUser->getOption('business'), array('id' => 'wpBusiness'))
		) .
		$this->addRow(
			wfLabel('Website', 'wpWebsite'),
			wfInput('wpWebsite', 20, $wgUser->getOption('website'), array('id' => 'wpWebsite'))
		) .
		$this->addRow(
			wfLabel('Address', 'wpAddress'),
			wfInput('wpAddress', 20, $wgUser->getOption('address'), array('id' => 'wpAddress'))
		) .
		$this->addRow(
			wfLabel('Address 2', 'wpAddress2'),
			wfInput('wpAddress2', 20, $wgUser->getOption('address2'), array('id' => 'wpAddress2'))
		) .
		$this->addRow(
			wfLabel('City', 'wpCity'),
			wfInput('wpCity', 20, $wgUser->getOption('city'), array('id' => 'wpCity'))
		) .
		$this->addRow(
			wfLabel('State', 'wpState'),
			'<select name="wpState" id="wpState" onchange="awcUpdateCounty(this.value)">'.$this->stateOptions.'</select>',
			'Required'
		) .
		$this->addRow(
			wfLabel('County', 'wpCounty'),
			'<select name="wpCounty" id="wpCounty">'.$this->countyOptions.'</select>',
			'Required'
		) .
		$this->addRow(
			wfLabel('Zip Code', 'wpZipCode'),
			wfInput('wpZipCode', 10, $wgUser->getOption('zipcode'), array('id' => 'wpZipCode'))
		) .
		$this->addRow(
			wfLabel('Logo', 'wpProfileImage'),
			wfInput('wpProfileImage', 10, $wgUser->getOption('logo'), array('id' => 'wpProfileImage', 'type' => 'file'))
		) .
		$this->addRow(wfLabel('Additional Information (1000 Characters Max)', 'wpNotes'), '')
		. '<tr><td colspan="3"><textarea cols=30 rows="7" id="wpNotes" name="wpNotes">'.$wgUser->getOption('notes').'</textarea></td></tr>'
		. '</table></div>';

		
		
		$html .= '<script type="text/javascript">awcAddValidation()</script>';
		return $html;
	}
	
	/**
	 * Add a table row to the form
	 */
	function addRow( $td1, $td2, $td3 = '' ) {
		return "<tr>
			<td class='mw-label'>$td1:</td>
			<td class='mw-input'>$td2</td>
			<td><div class='prefsectiontip'><div class='error'>$td3</div></div></td>
			</tr>";
	}

	/**
	 * Process uploaded image file
	 */
	function processUploadedImage( $file ) {
		global $wgUser, $wgDBname, $wgSiteNotice, $wgUploadDirectory, $awcMaxImageSize;
		$error = false;
		if ( !ereg( '^image/(jpeg|png|gif)$', $file['type'] ) ) $error = 'Uploaded file was not of a valid type!';
		if ( $file['size'] > $awcMaxImageSize )                 $error = 'Profile images are restricted to a maximum of 100KBytes';
		if ( $file['error'] > 0 )                               $error = 'Uploaded error number '.$file['error'].' occurred';
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
			'profilesearch' => "Search by profile",
			'awc-preftab'   => "Extended Profile",
			'awc-prefmsg'   => "<br><b>Let others find you by filling in this information!</b><br>",
			'awc-searchmsg' => "\n\n<b>Search for other Property Tax pros here! If you want to be added, edit your Extended Profile in User Preferences</b>\n\n"
		) );
	}

	# Instantiate the IntegratePerson singleton now that the environment is prepared
	$wgIntegratePerson = new IntegratePerson();

}
