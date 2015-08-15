<?php // no direct access  //
error_reporting(E_ALL);
ini_set('display_errors', 1);
//error_reporting(0);
defined( '_JEXEC' ) or die( 'Restricted access now' . __FILE__ );
$this->setMetaData('generator','');	## added to remove Meta tag info
$showLeftColumn = (bool) $this->countModules('position-7');
$showRightColumn = (bool) $this->countModules('position-6');
$showRightColumn &= JRequest::getCmd('layout') != 'edit';
$headerType = $this->params->get("headerType","1");
$headerlogo = $this->params->get("headerlogo","logo-ligmincha-de.png");
$myimage = $this->params->get("myimage","ligmincha3.jpg");
$myfolder = $this->params->get("myfolder","sampledata");
$duration = $this->params->get("duration","5000");
$delay = $this->params->get("delay","4000");
$imageWidth = $this->params->get("imageWidth","980");
$imageHeight = $this->params->get("imageHeight","200");
$forceresize = $this->params->get("forceresize","0");
$showControl = $this->params->get("showControl", "true");
$display = $this->params->get("display","sequence");
$arrowColor = $this->params->get("arrowColor","white");
$conf =& JFactory::getConfig();
$sitename = $conf->get('config.sitename');
$frontpagediv="0";
if ($headerType == "0" || $headerType == "1") {
	$lang =& JFactory::getLanguage();
	$locale = $lang->getTag();
	$menu = JSite::getMenu();
	if ($menu->getActive() == $menu->getDefault($locale)) {
		$frontpagediv="1";
	} 
} elseif ($headerType == "2" || $headerType == "3") {
	$frontpagediv="1";
}
$margin = 30;	// $margin = 20;
$outermargin = 0;
$logoText	= $this->params->get("logoText","LIGMINCHA");
$slogan		= $this->params->get("slogan","Joomla template for Ligmincha");
$pageWidth	= $this->params->get("pageWidth", "980");
$pageWidth	= $pageWidth - $outermargin;
$rightColumnWidth	= $this->params->get("rightColumnWidth", "190");
$leftColumnWidth	= $this->params->get("leftColumnWidth", "190");
$logoWidth			= $this->params->get("logoWidth", "300");
$removeBanner 		= $this->params->get("removeBanner", "No");
$widthdiff = 30;
if ($forceresize == "1") {
	$imageHeight = round($imageHeight * ($pageWidth + $outermargin - $widthdiff) / $imageWidth);
	$imageHeight = 200;
	$imageWidth = $pageWidth + $outermargin - $widthdiff;
}
$controlPosition = 50 - 2500/$imageHeight;
if($this->countModules('position-0')){
	$searchWidth = 170;
} else {
	$searchWidth = 0;
}
$searchHeight = 32;
$headerrightWidth = $pageWidth + $outermargin - $logoWidth - 50;
if ($showLeftColumn && $showRightColumn) {
   $contentWidth = $pageWidth - $leftColumnWidth - $rightColumnWidth - 3*$margin;} elseif (!$showLeftColumn && $showRightColumn) {
   $contentWidth = $pageWidth - $rightColumnWidth - 2*$margin ;
} elseif ($showLeftColumn && !$showRightColumn) {
   $contentWidth = $pageWidth - $leftColumnWidth - 2*$margin ;
} else {
   $contentWidth = $pageWidth - $margin ;}   ## echo " (".$pageWidth.") showLeftColumn (".$leftColumnWidth.") && NOT showRightColumn, 2*".$margin." ";
$this->title = $frontpagediv ? $sitename : $this->title .= ' | '.$sitename;
JHTML::_('behavior.framework', true);  
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" >
<head>
	<jdoc:include type="head" />
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/basics.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/template.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/local.css" type="text/css" />
	<!--[if IE 6]>
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/ie6.css" type="text/css" />
	<style type="text/css">
		img, div, a, input { behavior: url(<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/iepngfix.htc) }
		#search input.inputbox, #topmenuwrap, #sidebar div.moduletable h3, #sidebar-2 div.moduletable h3, .moduletable_menu h3 { behavior:none;}
	</style>
	<script src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/js/iepngfix_tilebg.js" type="text/javascript"></script>
	<![endif]-->
	<!--[if lte IE 7]>
	<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/ie67.css" type="text/css" />
	<![endif]-->
	<!--[if lte IE 8]>
	<style type="text/css">
		#search input.inputbox, #topmenuwrap, #sidebar div.moduletable h3, #sidebar-2 div.moduletable h3, .moduletable_menu h3 { behavior: url(<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/js/PIE.php) }
	</style>
	<![endif]-->
	<style type="text/css">
		#logo {
			width:<?php echo $logoWidth; ?>px;
		}
		#headerright {
			width:<?php echo $headerrightWidth; ?>px;
			<?php if($this->countModules('banner') || $removeBanner == "Yes") : ?>
				background: none;   
			<?php endif; ?>
		} 
		#search {
			width:<?php echo $searchWidth; ?>px;
			height:<?php echo $searchHeight; ?>px;
		}
</style>
	<link rel="icon" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/favicon.ico" type="image/ico" />
	<link rel='stylesheet' href='<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/orbit-1.2.3.css'/>
</head>
<body>
<div id="bodybottom">
<div id="allwrap" class="gainlayout" style="width:<?php echo $pageWidth + $outermargin; ?>px;">
	<div id="topheader"> 
	<?php if($this->countModules('position-12')) { ?>
		<div id="headersearch" class="gainlayout">
			<jdoc:include type="modules" name="position-12" />
			<div class="clr"></div>
		</div>
	<?php } ?> 
		<div id="header" class="gainlayout">   
		<?php if($this->countModules('position-15')) { ?>
			<div id="headerleft" class="gainlayout">
				<jdoc:include type="modules" name="position-15" />
			</div>
		<?php } ?> 
			<div class="clr"></div>
		</div><!--end of header-->
	</div><!--end of topheader-->
	<div id="wrap" class="gainlayout">
		<div id="topmenuwrap" class="gainlayout">
			<div id="topmenu" class="gainlayout">
				<?php echo file_get_contents( __DIR__ . '/topmenu-' . $this->language . '.html' ); ?>
				<div class="clr"></div>
			</div>
			<?php if($this->countModules('position-0')) { ?>
				<div id="search" class="gainlayout">
					<jdoc:include type="modules" name="position-0" style="xhtml" /> 
					<div class="clr"></div>  
				</div>
			<?php } ?>
			<div class="clr"></div>
		</div> 

		<?php if($this->countModules('position-13')) { ?>
			<div id="headermenu" class="gainlayout">
				<jdoc:include type="modules" name="position-13" />
				<div class="clr"></div>
			</div>
		<?php } ?> 
		<?php if($this->countModules('position-2')) { ?>
			<div id="pathway" class="gainlayout" style="float: left;">
				<jdoc:include type="modules" name="position-2" />
				<div class="clr"></div>
			</div>
		<?php } ?> 
			<div class="clr"></div>
		<?php if($this->countModules('user1')) { ?>
			<div id="topbody" class="gainlayout">
				<jdoc:include type="modules" name="user1" />
				<div class="clr"></div>
			</div>
		<?php } ?> 
		<div id="cbody" class="gainlayout">
			<?php if($showLeftColumn) { ?>
				<div id="sidebar" style="width:<?php echo $leftColumnWidth; ?>px;">     
					<jdoc:include type="modules" name="position-7" style="xhtml" />    
				</div>
			<?php } ?>
			<div id="content60" style="width:<?php echo $contentWidth; ?>px;">    
				<div id="content" class="gainlayout">
					<?php if($this->countModules('user5')) { ?>
						<div id="topcontent" class="gainlayout">
							<jdoc:include type="modules" name="user5" />
							<div class="clr"></div>
						</div>
					<?php } ?> 

					<jdoc:include type="message" />
					<jdoc:include type="component" /> 

					<?php if($this->countModules('user6')) { ?>
						<div class="clr"></div>
						<div id="topcontent" class="gainlayout">
							<jdoc:include type="modules" name="user6" />
							<div class="clr"></div>
						</div>
					<?php } ?> 
				</div>    
			</div>
			
			<?php if($showRightColumn) { ?>
				<div id="sidebar-2" style="width:<?php echo $rightColumnWidth; ?>px;">     
					<jdoc:include type="modules" name="position-6" style="xhtml" />     
				</div>
			<?php } ?>
			<div class="clr"></div>
		</div>

		
		<div class="clr"></div>
	</div><!--end of wrap-->

	<div id="topfooterwrap" class="gainlayout" style="width:<?php echo $pageWidth + $outermargin; ?>px;"> 
		<div id="topfooter" class="gainlayout">  
			<?php if($this->countModules('user2')) { ?>	
				<jdoc:include type="modules" name="user2" style="xhtml" />    
			<?php } ?>
		</div>
		<?php /* <div id="a4j"><a href="http://ligmincha.org/">Ligmincha International<?php  JText::_('TPL_FOOTER_LINK_TEXT');?></a></div> */ ?>
	</div>
</div><!--end of allwrap-->
<div id="footerwrap" class="gainlayout" style="width:<?php echo $pageWidth + $outermargin; ?>px;"> 
	<div id="footer" class="gainlayout">  
		<?php if($this->countModules('position-14')) { ?>	
			<jdoc:include type="modules" name="position-14" style="xhtml" />    
		<?php } ?>
	</div>
	<div class="clr"></div>
	<p style="text-align:right; margin-right:140px;"><a href="http://www.proaspecto.com/" target="_blank" title="Webdesign by ProAspecto.com"><img height="16px" src="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/images/webdesign_proaspecto.png"></a></p>
</div>
</div><!--end of bodybackground-->
</body>
</html>
