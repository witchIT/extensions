<?php
/**
 * OrganicDesign MediaWiki skin
 *
 * @file
 * @ingroup Skins
 */

if( !defined( 'MEDIAWIKI' ) ) die( -1 );

/**
 * SkinTemplate class for OrganicDesign skin
 * @ingroup Skins
 */
class SkinOrganicDesign extends SkinTemplate {

	var $skinname = 'organicdesign', $stylename = 'organicdesign',
		$template = 'OrganicDesignTemplate', $useHeadElement = true;

	/**
	 * Load skin and user CSS files in the correct order
	 * fixes bug 22916
	 * @param $out OutputPage object
	 */
	function setupSkinUserCss( OutputPage $out ){
		parent::setupSkinUserCss( $out );
		$out->addModuleStyles( "skins.organicdesign" );
	}
}

/**
 * @todo document
 * @addtogroup Skins
 */
class OrganicDesignTemplate extends BaseTemplate {
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
		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();

		$this->html( 'headelement' );?>
	<table class="pageWrapper" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center">
	<table id="globalWrapper" cellpadding="0" cellspacing="0"><tr><td>
	<table class="pageWrapper" cellpadding="0" cellspacing="0" width="100%"><tr><td id="column-one"><div id="c1-div">
	<script type="<?php $this->text('jsmimetype') ?>"> if (window.isMSIE55) fixalpha(); </script>

	<div class="portlet" id="p-personal">
		<h5><?php $this->msg('personaltools') ?></h5>
		<div class="pBody">
			<ul<?php $this->html('userlangattributes') ?>>
<?php		foreach($this->getPersonalTools() as $key => $item) { ?>
				<?php echo $this->makeListItem($key, $item); ?>

<?php		} ?>
			</ul>
		</div>
	</div>
<?php

// Get avatar image
global $wgUser,$wgUploadDirectory,$wgUploadPath;
if ($wgUser->isLoggedIn()) {
	?><div id="p-avatar"><?php
	$name  = $wgUser->getName();
	$img = wfLocalFile( "$name.png" );
	if( is_object( $img  ) && $img->exists() ) {
		$url = $img->transform( array( 'width' => 50 ) )->getUrl();
		echo "<a href=\"" . $wgUser->getUserPage()->getLocalUrl() . "\"><img src=\"$url\" alt=\"$name\"></a>";
	} else {
		$upload = Title::newFromText( 'Upload', NS_SPECIAL );
		$url = $upload->getLocalUrl( "wpDestFile=$name.png" );
		echo "<a href=\"$url\" class=\"new\"><br>user<br>icon</a>";
	}
	?></div><?php
}

// Donations
global $wgOrganicDesignDonations;
if ( $wgOrganicDesignDonations ) {?>
	<div class="portlet" id="donations" >
		<h2>Tips are welcome</h2>
		<h5>We accept paypal or credit card</h5>
		<div class="pBody">
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="<?php echo $wgOrganicDesignDonations ?>" />
				<input type="hidden" name="item_name" value="Donation">
				<input type="hidden" name="currency_code" value="USD">
				$<input style="width:35px" type="text" name="amount" value="5.00" />&nbsp;<input type="submit" value="Checkout" />
			</form>
		</div>
		<h5 id="btcbest">But <a href="/Bitcoin">Bitcoins</a> are best :-)</h5>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/a/a0/Bitcoin-icon.png) no-repeat 5px 2px;">
			<input style="width:135px;margin-left:23px" readonly="1" value="1ADB7fMcciUxmXsUrQnt6Se2x2Xdvvhv9m" onmouseover="this.select()" />
		</div>
		<h5 id="nmccool">And <a href="/Namecoin">Namecoins</a> are cool too!</h5>
		<div class="pBody" style="white-space:nowrap;vertical-align:top;background:url(/files/c/c6/Namecoin-icon.png) no-repeat 5px 2px;">
			<input style="width:135px;margin-left:23px" readonly="1" value="NB6GUnq7DTPFPyh8Zq6tdFyW4VyRv2HNWD" onmouseover="this.select()" />
		</div>
	</div>
<?php }?>
<div class="fb-like-box" data-href="http://www.facebook.com/organicdesign.co.nz" data-width="200" data-show-faces="false" data-stream="false" data-header="false"></div>

<!-- search -->
<div id="p-search" class="portlet">
	<h5><label for="searchInput"><?php $this->msg('search') ?></label></h5>
	<div id="searchBody" class="pBody">
		<form action="<?php $this->text('wgScript') ?>" id="searchform">
			<input type='hidden' name="title" value="<?php $this->text('searchtitle') ?>"/>
			<?php
			echo $this->makeSearchInput(array( "id" => "searchInput" ));
			echo $this->makeSearchButton("go", array( "id" => "searchGoButton", "class" => "searchButton" ));
			echo $this->makeSearchButton("fulltext", array( "id" => "mw-searchButton", "class" => "searchButton" ));
			?>
		</form>
	</div>
</div><?php

// Sidebar
global $wgUser,$wgTitle,$wgParser;
$title = 'od-sidebar';
$article = new Article( Title::newFromText( $title, NS_MEDIAWIKI ) );
$text = $article->fetchContent();
if( empty( $text ) ) $text = wfMsg( $title );
if( is_object( $wgParser ) ) { $psr = $wgParser; $opt = $wgParser->mOptions; }
else { $psr = new Parser; $opt = NULL; }
if( !is_object( $opt ) ) $opt = ParserOptions::newFromUser( $wgUser );
echo $psr->parse( $text, $wgTitle, $opt, true, true )->getText();
?></div></td>

<!-- Main content area -->
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
					<ul><?php
						foreach($this->data['content_actions'] as $key => $tab) {
							echo $this->makeListItem( $key, $tab );
						} ?>

					</ul>
				</div>
			</div>
	<a id="top"></a>
	<?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>

	<h1 id="firstHeading" class="firstHeading"><span dir="auto"><?php $this->html('title') ?></span></h1>
	<div id="bodyContent" class="mw-body">
		<div id="siteSub"><?php $this->msg('tagline') ?></div>
		<div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
<?php if($this->data['undelete']) { ?>
		<div id="contentSub2"><?php $this->html('undelete') ?></div>
<?php } ?><?php if($this->data['newtalk'] ) { ?>
		<div class="usermessage"><?php $this->html('newtalk')  ?></div>
<?php } ?><?php if($this->data['showjumplinks']) { ?>
		<div id="jump-to-nav" class="mw-jump"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div>
<?php } ?>
		<!-- start content -->
<?php $this->html('bodytext') ?>
		<?php if($this->data['catlinks']) { $this->html('catlinks'); } ?>
		<!-- end content -->
		<?php if($this->data['dataAfterContent']) { $this->html ('dataAfterContent'); } ?>
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

// MediaWiki:Footer
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
