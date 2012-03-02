<?php
 
$wgHooks['ParserFirstCallInit'][] = 'wfSampleParserInit';
 
function wfSampleParserInit( Parser &$parser ) {
        // This does <setprop>Some random text</setprop>
        // And then <getprop/> to retrieve a prop
        // Or <getprop page="somepage"> to retrieve for
        // something other than the current page.
 
        $parser->setHook( 'getprop', 'wfSampleGetProp' );
        $parser->setHook( 'setprop', 'wfSampleSetProp' );
        // Always return true from this function. The return value does not denote
        // success or otherwise have meaning - it just must always be true.
        return true;
}
 
function wfSampleSetProp( $input, array $args, Parser $parser, PPFrame $frame ) {
        $parsed = $parser->recursiveTagParse( $input, $frame );
        // Since this can span different parses, we need to take account of
        // the fact recursiveTagParse only half parses the text. or strip tags
        // (UNIQ's) will be exposed. (Alternative would be to just call
        // $parser->replaceLinkHolders() and $parser->mStripState->unstripBoth()
        // right here right now.
        $serialized = serialize( $parser->serializeHalfParsedText( $parsed ) );
        $parser->getOutput()->setProperty( 'SimpleSetPropExtension', $serialized );
 
        // Note if other pages change based on a property, you should see $wgPagePropLinkInvalidations
        // to automatically invalidate dependant page. In this example that would be pages that
        // use <getprop page="something>. However that would require adding a linking table
        // (since none of the standard ones work for this example) which is a bit beyond the
        // scope of this simple example.
 
        return '';
}
function wfSampleGetProp( $input, array $args, Parser $parser, PPFrame $frame ) {
        $pageId = $parser->getTitle()->getArticleId();
        if ( isset( $args['page'] ) ) {
              $title = Title::newFromText( $args['page'] );
              if ( !$title || $title->getArticleId() === 0 ) {
                          // In a real extension, this would be i18n-ized.
                          return '<span class="error">Invalid page ' . htmlspecialchars( $args['page'] ) . ' specified.</span>';
              }
 
              // Do for some page other then current one.
              $dbr = wfGetDB( DB_SLAVE );
              $propValue = $dbr->selectField( 'page_props', // table to use
                          'pp_value', // Field to select
                          array( 'pp_page' => $title->getArticleId(), 'pp_propname' => "SimpleSetPropExtension" ), // where conditions
                          __METHOD__
              );
              if ( $propValue === false ) {
                          // No prop stored for this page
                          // In a real extension, this would be i18n-ized.
                          return '<span class="error">No prop set for page ' . htmlspecialchars( $args['page'] ) . ' specified.</span>';
              }
              // We found the prop. Unserialize (First level of serialization)
              $prop = unserialize( $propValue );
 
              if ( !$parser->isValidHalfParsedText( $prop ) ) {
                          // Probably won't ever happen.
                          return '<span class="error">Error retrieving prop</span>';
              } else {
                          // Everything should be good.
                          return $parser->unserializeHalfParsedText( $prop );
              }
        } else {
              // Second case, current page.
              // Can't query db, because could be set earlier in the page and not saved yet.
              // So have to use the parserOutput object.
 
              $prop = unserialize( $parser->getOutput()->getProperty( 'SimpleSetPropExtension' ) );
 
              if ( !$parser->isValidHalfParsedText( $prop ) ) {
                          // Probably won't ever happen.
                          return '<span class="error">Error retrieving prop</span>';
              } else {
                          // Everything should be good.
                          return $parser->unserializeHalfParsedText( $prop );
              }
        }
}
