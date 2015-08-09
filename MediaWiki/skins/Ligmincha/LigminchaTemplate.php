<?php
class LigminchaTemplate extends BaseTemplate {

	/**
	 * @var Skin Cached skin object
	 */
	var $skin;
	var $mSuccess = '';
	var $mError = '';

	/**
	 * Outputs the entire contents of the XHTML page
	 */
	public function execute() {
		global $wgUser, $wgOut, $wgTitle, $wgJsMimeType, $wgStylePath, $wgLogo;
		$this->html( 'headelement' );
		?><div id="globalWrapper">
	<div class="header">
		<div class="user_info">
			<ul id="user_nav" class="dropdown_nav">
			<?php
			?>
			</ul>
		</div>
	</div>
	<div class="cleaner"></div>
	<table id="page-layout"><tr>
	<?php

	// Sidebar tree
	$title = Title::newFromText( 'SidebarTree', NS_MEDIAWIKI );
	$article = new Article( $title, 0 );
	echo "<td><div id=\"qar-sidebar\">\n";
	//echo DcsSearch::searchBox();
	//echo $wgOut->parse( DcsCommon::getContent( $article ) );
	echo "</div></td><td id=\"layout-content\">\n";

	// Actions - filtered by DcsCommon::onSkinTemplateNavigation
	echo "<div class=\"top_navigation\">\n<ul id=\"main_nav\" class=\"dropdown_nav\">\n";
	$talk = false;
	foreach( $this->data['content_actions'] as $key => $tab ) {
		echo '<li><a href="' . $tab['href'] . '" class="' . $tab['class'] . '">' . $tab['text'] . '</a></li>';
	}
	echo "</ul><div class=\"cleaner\"></div></div>\n";

	// Page content
	echo "<div id=\"content\">\n";
	if( $this->data['sitenotice'] ) echo "<div id=\"siteNotice\">" . $this->data['sitenotice'] . "</div>\n";
	echo "<a id=\"top\"></a><h1 id=\"firstHeading\" class=\"firstHeading\">" . $this->data['title'] . "</h1>\n";
	echo "<div id=\"bodyContent\">\n";
	if( $sysop && $this->data['undelete'] ) echo "<div id=\"contentSub2\">" . $this->data['undelete'] . "</div>\n";
	$this->html( 'bodycontent' );
	if( $this->data['catlinks'] ) echo $this->data['catlinks'] . "\n";
	if( $this->data['dataAfterContent'] ) echo $this->data['dataAfterContent'] . "\n";
	echo "<div class=\"cleaner\"></div>\n";
	echo "</div>\n";
	echo "</div>\n";
	?>

	</td></tr>
	<tr><td>&nbsp;</td><td>
	<div class="footer">
		<div class="copyright">
			<p>©2013-<?php echo strftime('%Y');?> Ligmincha Brasil</p>
		</div>
	</div>
	</td></tr></table>
</div>
<?php
		// Closing scripts and elements
		echo "<script type=\"$wgJsMimeType\"> if ( window.isMSIE55 ) fixalpha(); </script>\n";
		$this->printTrail();
		echo "\n</body>\n</html>\n";
	}
}
