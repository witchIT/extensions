<?php
/**
 * HapuTimes skin (for MW1.14+)
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @addtogroup Skins
 */

if( !defined( 'MEDIAWIKI' ) ) die( -1 );

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @addtogroup Skins
 */
class SkinHapuTimes extends SkinTemplate {
	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'haputimes';
		$this->stylename = 'haputimes';
		$this->template  = 'HapuTimesTemplate';
	}
	function setupSkinUserCss( $out ) {
		parent::setupSkinUserCss( $out );
		$out->addStyle( 'haputimes/main.css', 'screen' );
	}
}
/**
 * @todo document
 * @addtogroup Skins
 */
class HapuTimesTemplate extends QuickTemplate {
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
		if ( version_compare( substr( $wgVersion, 0, 4 ), '1.16' ) >= 0 ) $css = str_replace( 'main.css', 'main116.css', $this->data['csslinks'] );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="<?php $this->text('xhtmldefaultnamespace') ?>" <?php
	foreach($this->data['xhtmlnamespaces'] as $tag => $ns) {
		?>xmlns:<?php echo "{$tag}=\"{$ns}\" ";
	} ?>xml:lang="<?php $this->text('lang') ?>" lang="<?php $this->text('lang') ?>" dir="<?php $this->text('dir') ?>">
	<head>
		<meta http-equiv="Content-Type" content="<?php $this->text('mimetype') ?>; charset=<?php $this->text('charset') ?>" />
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

<table id="globalWrapper" cellpadding="0" cellspacing="0" border><tr><td colspan="3" id="header">
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

	<div id="menubar">
		<?php
		global $wgUser, $wgTitle, $wgParser;
		$a = new Article( Title::newFromText( 'Menubar', NS_MEDIAWIKI ) );
		if ( is_object( $wgParser ) ) { $psr = $wgParser; $opt = $wgParser->mOptions; }
		else { $psr = new Parser; $opt = NULL; }
		if ( !is_object( $opt ) ) $opt = ParserOptions::newFromUser( $wgUser );
		echo $psr->parse( $a->fetchContent(), $wgTitle, $opt, true, true )->getText();
		?>
	</div>

</td></tr>

<tr><td id="sidebar" valign="top">
	<?php
	$a = new Article( Title::newFromText( 'Sidebar', NS_MEDIAWIKI ) );
	echo $psr->parse( $a->fetchContent(), $wgTitle, $opt, true, true )->getText();
	?>
</td>

<td id="contentWrapper" valign="top">
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
	<h1 class="firstHeading"><?php $this->data['displaytitle']!=""?$this->html('title'):$this->text('title') ?></h1>
	<div id="bodyContent">
		<h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
		<div id="contentSub"><?php $this->html('subtitle') ?></div>
		<?php if($this->data['undelete']) { ?><div id="contentSub2"><?php     $this->html('undelete') ?></div><?php } ?>
		<?php if($this->data['newtalk'] ) { ?><div class="usermessage"><?php $this->html('newtalk')  ?></div><?php } ?>
		<?php if($this->data['showjumplinks']) { ?><div id="jump-to-nav"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div><?php } ?>
		<!-- start content -->
		<?php $this->html('bodytext') ?>
		<?php if($this->data['catlinks']) { ?><div id="catlinks"><?php       $this->html('catlinks') ?></div><?php } ?>
		<!-- end content -->
		<div class="visualClear"></div>
	</div>
</td>

<td id="rightnav" valign="top">
	<?php
	$a = new Article( Title::newFromText( 'Rightnav', NS_MEDIAWIKI ) );
	echo $psr->parse( $a->fetchContent(), $wgTitle, $opt, true, true )->getText();
	?>
</td></tr>

<tr><td colspan="3" id="footer">
	<?php
	$a = new Article( Title::newFromText( 'Footer', NS_MEDIAWIKI ) );
	echo $psr->parse( $a->fetchContent(), $wgTitle, $opt, true, true )->getText();
	?>
</td></tr>
</table>

<?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
<?php $this->html('reporttime') ?>

</body></html>
<?php
	wfRestoreWarnings();
	} // end of execute() method
} // end of class
