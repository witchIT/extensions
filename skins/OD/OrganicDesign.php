<?php
if( !defined( 'MEDIAWIKI' ) ) die( -1 );
/**
 * OrganicDesign skin (for MW1.14+)
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @addtogroup Skins
 */


/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @addtogroup Skins
 */
class SkinOrganicDesign extends SkinTemplate {
	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'organicdesign';
		$this->stylename = 'organicdesign';
		$this->template  = 'OrganicDesignTemplate';
	}
	function setupSkinUserCss( $out ) {
		parent::setupSkinUserCss( $out );
		$out->addStyle( 'organicdesign/main.css', 'screen' );
	}
}
/**
 * @todo document
 * @addtogroup Skins
 */
class OrganicDesignTemplate extends QuickTemplate {
	var $skin;
	/**
	 * Template filter callback for MonoBook skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgRequest, $wgVersion;
		$this->skin = $skin = $this->data['skin'];
		$action = $wgRequest->getText( 'action' );
		$css = $this->data['csslinks'];
		if ( version_compare( substr( $wgVersion, 0, 4 ), '1.16' ) >= 0 ) $css = str_replace( 'main.css', 'main116.css', $css );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="<?php $this->text('xhtmldefaultnamespace') ?>" <?php
	foreach($this->data['xhtmlnamespaces'] as $tag => $ns) {
		?>xmlns:<?php echo "{$tag}=\"{$ns}\" ";
	} ?>xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">
	<head>
		<meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
		<meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
		<?php $this->html('headlinks') ?>
		<title><?php $this->text('pagetitle') ?></title>
		<?php print $css ?>

		<!--[if lt IE 7]><script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath') ?>/common/IEFixes.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>
		<meta http-equiv="imagetoolbar" content="no" /><![endif]-->

		<?php print Skin::makeGlobalVariablesScript( $this->data ); ?>

		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/common/wikibits.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"><!-- wikibits js --></script>
		<!-- Head Scripts -->
<?php $this->html('headscripts') ?>
<?php	if($this->data['jsvarurl']) { ?>
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('jsvarurl') ?>"><!-- site js --></script>
<?php	} ?>
<?php	if($this->data['pagecss']) { ?>
		<style type="text/css"><?php $this->html('pagecss') ?></style>
<?php	}
		if($this->data['usercss']) { ?>
		<style type="text/css"><?php $this->html('usercss') ?></style>
<?php	}
		if($this->data['userjs']) { ?>
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('userjs' ) ?>"></script>
<?php	}
		if($this->data['userjsprev']) { ?>
		<script type="<?php $this->text('jsmimetype') ?>"><?php $this->html('userjsprev') ?></script>
<?php	}
		if($this->data['trackbackhtml']) print $this->data['trackbackhtml']; ?>
	</head>
<body<?php if($this->data['body_ondblclick']) { ?> ondblclick="<?php $this->text('body_ondblclick') ?>"<?php } ?>
<?php if($this->data['body_onload']) { ?> onload="<?php $this->text('body_onload') ?>"<?php } ?>
 class="mediawiki <?php $this->text('dir') ?> <?php $this->text('pageclass') ?> <?php $this->text('skinnameclass') ?>">
	<table class="pageWrapper" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center">
	<table id="globalWrapper" cellpadding="0" cellspacing="0"><tr><td>
	<table class="pageWrapper" cellpadding="0" cellspacing="0" width="100%"><tr><td id="column-one"><div id="c1-div">
	<script type="<?php $this->text('jsmimetype') ?>"> if (window.isMSIE55) fixalpha(); </script>

	<div class="portlet" id="p-personal">
		<h5><?php $this->msg('personaltools') ?></h5>
		<div class="pBody">
			<ul>
<?php 			foreach($this->data['personal_urls'] as $key => $item) { ?>
				<li id="pt-<?php echo Sanitizer::escapeId($key) ?>"<?php
					if ($item['active']) { ?> class="active"<?php } ?>><a href="<?php
				echo htmlspecialchars($item['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-'.$key) ?><?php
				if(!empty($item['class'])) { ?> class="<?php
				echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
				echo htmlspecialchars($item['text']) ?></a></li>
<?php			} ?>
			</ul>
		</div>
	</div>

<?php
# Get avatar image
global $wgUser,$wgUploadDirectory,$wgUploadPath;
if ($wgUser->isLoggedIn()) {
	?><div id="p-avatar"><?php
	$user  = $wgUser->getName();
	$title = Title::newFromText("$user.png",NS_IMAGE);
	$image = Image::newFromTitle($title);
	if ($image && $image->exists()) {
		echo "<a href=\"".$title->getLocalUrl()."\"><img src=\"".$image->getThumbnail(50,50)->getUrl()."\" alt=\"$user\"></a>";
	} else {
		$upload = Title::newFromText('Upload',NS_SPECIAL);
		$url = $upload->getLocalUrl("wpDestFile=$user.png");
		echo "<a href=\"$url\" class=\"new\"><br>user<br>icon</a>";
	}
	?></div><?php
}
?>

<?php
# Donations
global $wgOrganicDesignDonations;
if ( $wgOrganicDesignDonations ) {?>
	<div class="portlet" id="donations" >
		<h5>Help keep us online!</h5>
		<div class="pBody">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="<?php echo $wgOrganicDesignDonations ?>" />
				<input type="hidden" name="item_name" value="Donation">
				<input type="hidden" name="currency_code" value="USD">
				$<input style="width:35px" type="text" name="amount" value="5.00" />&nbsp;<input type="submit" value="Paypal" />
			</form>
		</div>
		<br />
		<h5>We accept <a href="/Bitcoin">Bitcoins</a>!</h5>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/a/a0/Bitcoin-icon.png) no-repeat 2px 2px;">
			<input style="width:135px;margin-left:25px" value="1PVoayGWHbShwM6m5r2qACURBMMfTmR5jT" onmouseover="this.select()" />
		</div>
	</div>
<?php }?>

<div id="p-search" class="portlet">
	<h5><label for="searchInput"><?php $this->msg('search') ?></label></h5>
	<div id="searchBody" class="pBody">
		<form action="<?php $this->text('searchaction') ?>" id="searchform"><div>
			<input id="searchInput" name="search" type="text"<?php echo $skin->tooltipAndAccesskey('search');
				if( isset( $this->data['search'] ) ) {
					?> value="<?php $this->text('search') ?>"<?php } ?> />
			<input type='submit' name="go" class="searchButton" id="searchGoButton"	value="<?php $this->msg('searcharticle') ?>" />&nbsp;
			<input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg('searchbutton') ?>" />
		</div></form>
	</div>
</div>

<?php
# MediaWiki:Sidebar
global $wgUser,$wgTitle,$wgParser;
$title = 'od-sidebar';
$article = new Article( Title::newFromText( $title, NS_MEDIAWIKI ) );
$text = $article->fetchContent();
if ( empty( $text ) ) $text = wfMsg( $title );
if ( is_object( $wgParser ) ) { $psr = $wgParser; $opt = $wgParser->mOptions; }
else { $psr = new Parser; $opt = NULL; }
if ( !is_object( $opt ) ) $opt = ParserOptions::newFromUser( $wgUser );
echo $psr->parse( $text, $wgTitle, $opt, true, true )->getText();
?>
	</div></td><!-- end of the left (by default at least) column -->
	<td id="contentWrapper">
		<table cellpadding="0" cellspacing="0" width="100%"><tr>
		<tr>
			<td><div id="shadow-tl"></div></td>
			<td id="shadow-t" align="right"><div id="logo-t"></div></td>
			<td align="left"><div id="shadow-tr"></div></td>
		</tr>
		<td id="shadow-l">
		<td width="100%" id="content">
			<div id="p-cactions" class="portlet">
				<h5><?php $this->msg('views') ?></h5>
				<div class="pBody">
					<ul>
			<?php			foreach($this->data['content_actions'] as $key => $tab) { ?>
						 <li id="ca-<?php echo Sanitizer::escapeId($key) ?>"<?php
								if($tab['class']) { ?> class="<?php echo htmlspecialchars($tab['class']) ?>"<?php }
							 ?>><a href="<?php echo htmlspecialchars($tab['href']) ?>"<?php echo $skin->tooltipAndAccesskey('ca-'.$key) ?>><?php
							 echo htmlspecialchars($tab['text']) ?></a></li>
			<?php			 } ?>
					</ul>
				</div>
			</div>
			<a name="top" id="top"></a>
			<?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>
			<h1 class="firstHeading"><?php $this->html('title') ?></h1>
			<div id="bodyContent">
				<h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
				<div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
				<?php if($this->data['undelete']) { ?><div id="contentSub2"><?php     $this->html('undelete') ?></div><?php } ?>
				<?php if($this->data['newtalk'] ) { ?><div class="usermessage"><?php $this->html('newtalk')  ?></div><?php } ?>
				<?php if($this->data['showjumplinks']) { ?><div id="jump-to-nav"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div><?php } ?>
				<!-- start content -->
				<?php $this->html('bodytext') ?>
				<?php if($this->data['catlinks']) { ?><?php       $this->html('catlinks') ?><?php } ?>
				<!-- end content -->
				<div class="visualClear"></div>
			</div>
		</td>
			<td valign="top" id="shadow-r"><div id="logo-r"></div></td>
		</tr>
		<tr>
			<td><div id="shadow-bl"></div></td>
			<td><div id="shadow-b"></div></td>
			<td><div id="shadow-br"></div></td>
		</tr>
		</table>
	</td></tr>
	</table>
	</td></tr>
	<tr><td colspan="2">
	
<?php
# MediaWiki:Footer
global $wgUser,$wgTitle,$wgParser;
$title = 'footer';
$article = new Article( Title::newFromText( $title, NS_MEDIAWIKI ) );
$text = $article->fetchContent();
if ( empty( $text ) ) $text = wfMsg( $title );
if ( is_object( $wgParser ) ) { $psr = $wgParser; $opt = $wgParser->mOptions; }
else { $psr = new Parser; $opt = NULL; }
if ( !is_object( $opt ) ) $opt = ParserOptions::newFromUser( $wgUser );
echo $psr->parse( $text, $wgTitle, $opt, true, true )->getText();
?>
	
	</td></tr>
	</table>
	<?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
</div>
<?php $this->html('reporttime') ?>
<?php if ( $this->data['debug'] ): ?>
<!-- Debug output:
<?php $this->text( 'debug' ); ?>

-->
<?php endif; ?>
</body></html>
<?php
	wfRestoreWarnings();
	} // end of execute() method
} // end of class
