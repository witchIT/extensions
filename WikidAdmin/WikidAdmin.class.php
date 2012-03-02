<?php
/**
 * Define a new class based on the SpecialPage class
 */
class WikidAdmin extends SpecialPage {

	function WikidAdmin() {
		SpecialPage::SpecialPage( 'WikidAdmin', 'sysop', true, false, false, false );
	}

	function execute( $param ) {
		global $wgWikidWork, $wgWikidTypes, $wgParser, $wgOut, $wgHooks, $wgRequest, $wgJsMimeType;
		$wgParser->disableCache();
		$this->setHeaders();
		wfWikidAdminLoadWork();

		# Add some script for auto updating the job info
		$wgOut->addScript( "<script type='$wgJsMimeType'>
			function wikidAdminCurrentTimer() {
				setTimeout('wikidAdminCurrentTimer()',1000);
				sajax_do_call('wfWikidAdminRenderWork',[],document.getElementById('current-work'));
			}
			function wikidAdminHistoryTimer() {
				setTimeout('wikidAdminHistoryTimer()',10000);
				sajax_do_call('wfWikidAdminRenderWorkHistory',[],document.getElementById('work-history'));
			}</script>" );

		# Start a new job instance if wpStart & wpType posted
		if( $wgRequest->getText( 'wpStart' ) ) {
			$type = $wgRequest->getText( 'wpType' );
			$start = true;
			$args  = array();
			wfRunHooks( "WikidAdminTypeFormProcess_$type", array( &$args, &$start ) );
			if ( $start ) $this->startJob( $type, $args );
		}

		# Cancel a job
		if( $wgRequest->getText( 'action' ) == 'stop' ) {
			$this->stopJob( $wgRequest->getText( 'id' ) );
		}

		# Pause/continue a job
		if( $wgRequest->getText( 'action' ) == 'pause' ) {
			$this->pauseJob( $wgRequest->getText( 'id' ) );
		}

		# Render ability to start a new job and supply optional args
		$wgOut->addWikiText( "== Start a new job ==\n" );
		if( count( $wgWikidTypes ) ) {
			$url = Title::newFromText( 'WikidAdmin', NS_SPECIAL )->getLocalUrl();
			$html = "<form action=\"$url\" method=\"POST\" enctype=\"multipart/form-data\">";
			$html .= "<table><tr valign=\"top\">\n";
			$html .= '<td>Type: <select name="wpType" id="wpType" onchange="wikidAdminShowTypeForm()" ><option />';
			foreach( $wgWikidTypes as $type ) $html .= "<option>$type</option>";
			$html .= "</select></td><td>";

			# Render forms for types
			$forms = array();
			foreach( $wgWikidTypes as $type ) {
				$hook = "WikidAdminTypeFormRender_$type";
				if( isset( $wgHooks[$hook] ) ) {
					$form = '';
					wfRunHooks( $hook, array( &$form ) );
					$html .= "<div id=\"form-$type\" style=\"display:none\" >$form</div>";
					$forms[] = "'$type'";
				}
			}

			# and the script to switch the visible one
			$forms = join( ',', $forms );
			$wgOut->addScript("<script type='$wgJsMimeType'>
			function wikidAdminShowTypeForm() {
				var type = document.getElementById('wpType').value;
				var forms = [$forms];
				for( i in forms ) document.getElementById('form-'+forms[i]).style.display = forms[i] == type ? '' : 'none';
			}</script>");

			$html .= '</td><td><input name="wpStart" type="submit" value="Start" /></td>';
			$html .= '</tr></table></form><br />';
			$wgOut->addHtml( $html );
		} else $wgOut->addHtml( '<i>There are no job types defined</i><br />' );

		# Render as a table with pause/continue/cancel for each
		$wgOut->addWikiText( "\n== Currently executing work ==\n" );
		$wgOut->addHtml( '<div id="current-work">' . wfWikidAdminRenderWork() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminCurrentTimer();</script><br />" );

		# Render a list of previously run jobs from the job log
		$wgOut->addWikiText( "== Work history ==\n" );
		$wgOut->addHtml( '<div id="work-history">' . wfWikidAdminRenderWorkHistory() . '</div>' );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikidAdminHistoryTimer();</script>" );

	}

	/**
	 * Send a start job request to the local peer
	 */
	function startJob( $type, &$args ) {
		$args['type'] = $type;
		wfEventPipeSend( 'StartJob', $args );
	}

	/**
	 * Send a stop job request to the local peer
	 */
	function stopJob( $id ) {
		wfEventPipeSend( 'StopJob', $id );
	}

	/**
	 * Send a pause job request to the local peer
	 */
	function pauseJob( $id ) {
		wfEventPipeSend( 'PauseJobToggle', $id );
	}

}
