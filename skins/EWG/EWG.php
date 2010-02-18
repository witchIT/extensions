<?php
/**
 * EWG Skin based on MonoBook
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @file
 * @ingroup Skins
 */

if( !defined( 'MEDIAWIKI' ) ) die( -1 );

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @ingroup Skins
 */
class SkinEWG extends SkinTemplate {
	function initPage( &$out ) {
		SkinTemplate::initPage( $out );
		$this->skinname  = 'ewg';
		$this->stylename = 'ewg';
		$this->template  = 'EWGTemplate';
		# Bug 14520: skins that just include this file shouldn't load nonexis-
		# tent CSS fix files.
		$this->cssfiles = array( 'IE', 'IE50', 'IE55', 'IE60', 'IE70', 'rtl' );
	}
}

/**
 * @todo document
 * @ingroup Skins
 */
class EWGTemplate extends QuickTemplate {
	var $skin;
	/**
	 * Template filter callback for EWG skin.
	 * Takes an associative array of data set from a SkinTemplate-based
	 * class, and a wrapper for MediaWiki's localization database, and
	 * outputs a formatted page.
	 *
	 * @access private
	 */
	function execute() {
		global $wgRequest;
		$this->skin = $skin = $this->data['skin'];
		$action = $wgRequest->getText( 'action' );

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
		<style type="text/css" media="screen, projection">/*<![CDATA[*/
			@import "<?php $this->text('stylepath') ?>/common/shared.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";
			@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/main.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";
		/*]]>*/</style>
		<style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/ewg.css";</style>
		<link rel="stylesheet" type="text/css" <?php if(empty($this->data['printable']) ) { ?>media="print"<?php } ?> href="<?php $this->text('printcss') ?>?<?php echo $GLOBALS['wgStyleVersion'] ?>" />
		<?php if( in_array( 'IE50', $skin->cssfiles ) ) { ?><!--[if lt IE 5.5000]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE50Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<?php } if( in_array( 'IE55', $skin->cssfiles ) ) { ?><!--[if IE 5.5000]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE55Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<?php } if( in_array( 'IE60', $skin->cssfiles ) ) { ?><!--[if IE 6]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE60Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<?php } if( in_array( 'IE70', $skin->cssfiles ) ) { ?><!--[if IE 7]><style type="text/css">@import "<?php $this->text('stylepath') ?>/<?php $this->text('stylename') ?>/IE70Fixes.css?<?php echo $GLOBALS['wgStyleVersion'] ?>";</style><![endif]-->
		<?php } ?><!--[if lt IE 7]><?php if( in_array( 'IE', $skin->cssfiles ) ) { ?><script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath') ?>/common/IEFixes.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"></script>
		<?php } ?><meta http-equiv="imagetoolbar" content="no" /><![endif]-->
		
		<?php print Skin::makeGlobalVariablesScript( $this->data ); ?>
                
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/common/wikibits.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"><!-- wikibits js --></script>
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/<?php $this->text('stylename') ?>/findinpage.js?<?php echo $GLOBALS['wgStyleVersion'] ?>"><!-- find in page js --></script>
		<script type="<?php $this->text('jsmimetype') ?>" src="<?php $this->text('stylepath' ) ?>/<?php $this->text('stylename') ?>/ewg.js ?>"></script>
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
 class="mediawiki <?php $this->text('nsclass') ?> <?php $this->text('dir') ?> <?php $this->text('pageclass') ?>">
	<div id="globalWrapper">
							<div id="p-cactions" class="portlet">
						<h5><?php $this->msg('views') ?></h5>
						<div class="pBody">
							<ul>
					<?php		foreach($this->data['content_actions'] as $key => $tab) {
									echo '
								 <li id="ca-' . Sanitizer::escapeId($key).'"';
									if( $tab['class'] ) {
										echo ' class="'.htmlspecialchars($tab['class']).'"';
									}
									echo'><a href="'.htmlspecialchars($tab['href']).'"';
									# We don't want to give the watch tab an accesskey if the
									# page is being edited, because that conflicts with the
									# accesskey on the watch checkbox.  We also don't want to
									# give the edit tab an accesskey, because that's fairly su-
									# perfluous and conflicts with an accesskey (Ctrl-E) often
									# used for editing in Safari.
									if( in_array( $action, array( 'edit', 'submit' ) )
									&& in_array( $key, array( 'edit', 'watch', 'unwatch' ))) {
										echo $skin->tooltip( "ca-$key" );
									} else {
										echo $skin->tooltipAndAccesskey( "ca-$key" );
									}
									echo '>'.htmlspecialchars($tab['text']).'</a></li>';
								} ?>
							</ul>
						</div>
					</div>
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
					<div class="portlet" id="p-logo">
						<a style="background-image: url(<?php $this->text('logopath') ?>);" <?php
							?>href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href'])?>"<?php
							echo $skin->tooltipAndAccesskey('n-mainpage') ?>></a>
					</div>
		<table id="column-content" width="!00%" cellpadding="0" cellspacing="0">
			<tr>
				<td colspan="2">
					<div id="top-div" />
				</td>
			<tr>
				 <td id="column-one">
			<script type="<?php $this->text('jsmimetype') ?>"> if (window.isMSIE55) fixalpha(); </script>
		
<?php
# MediaWiki:Sidebar
global $wgUser,$wgTitle,$wgParser;
$side = new Article(Title::newFromText('Sidebar',NS_MEDIAWIKI));
if (is_object($wgParser)) { $psr = $wgParser; $opt = $wgParser->mOptions; }
else { $psr = new Parser; $opt = NULL; }
if (!is_object($opt)) $opt = ParserOptions::newFromUser($wgUser);
echo $psr->parse($side->fetchContent(),$wgTitle,$opt,true,true)->getText();
?><br /><?php
$this->searchBox();
?>
				</td><!-- end of the left (by default at least) column -->
				<td id="content" width="100%">
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
						<?php if($this->data['catlinks']) { $this->html('catlinks'); } ?>
						<!-- end content -->
						<div class="visualClear"></div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		
		<div class="visualClear"></div>
		<div id="footer">
<?php
global $wgUser, $wgTitle, $wgParser;
if ( is_object( $wgParser ) ) $psr =& $wgParser; else $psr = new Parser;
$opt = ParserOptions::newFromUser( $wgUser );
$nav = new Article( Title::newFromText( 'Footer', NS_MEDIAWIKI ) );
$out = $psr->parse( $nav->fetchContent( 0, false, false ), $wgTitle, $opt, true, true );
echo $out->getText();
?>
			</div>
	</div>
<?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
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

	/*************************************************************************************************/
	function searchBox() {
?>
	<div id="p-search" class="portlet">
		<h5><label for="searchInput"><?php $this->msg('search') ?></label></h5>
		<div id="searchBody" class="pBody">
			<form name="searchform" action="<?php $this->text('searchaction') ?>" id="searchform"><div>
				<input id="searchInput" name="search" type="text"<?php echo $this->skin->tooltipAndAccesskey('search');
					if( isset( $this->data['search'] ) ) {
						?> value="<?php $this->text('search') ?>"<?php } ?> />
				<input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="Go"<?php echo $this->skin->tooltipAndAccesskey( 'search-fulltext' ); ?> />
				<input type='button' class="searchButton" value="Search" onClick="document.searchform.findthis.value=document.searchform.search.value;findinpage(document.searchform, frametosearch);"
				/>
				<input type='hidden' name='findthis' />
			</div></form>
		</div>
	</div>
<?php
	}

} // end of class


