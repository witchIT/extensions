<?php
ini_set("display_errors", "on");
ini_set('error_reporting', E_ALL );

########################
# GeshiCodeTag.php
# 
# By: Paul Nolasco
# Copyright 2006
# paulnolasco@gmail.com
# http://www.wikics.org/
########################

include_once('/var/www/extensions/geshi/geshi.php');

// change directory accordingly
$languagesPath = "/var/www/extensions/geshi/geshi";

// 1 - ENABLED, 0 - DISABLED
$codeTag["simple"] = 1;                       // ex. <php> echo </php> 
$codeTag["advanced"]["mode"] = 0;             // ex. <code php n> echo </php>
     
// extra options
/*      
        strict mode - http://qbnz.com/highlighter/geshi-doc.html#using-strict-mode
        ex. <img src="<?php echo rand(1, 100) ?>" /> 
*/
$codeTag["advanced"]["strict"] = 0;                   

#############################################

$wgExtensionFunctions[] = "ExtensionCodeTag";
$wgExtensionCredits['parserhook'][] = array( 
        'name' => 'GeSHiCodeTag', 
        'author' => 'Paul Nolasco', 
        'url' => 'http://www.wikics.org/', 
);
$languages = array();

function ExtensionCodeTag()
{               
        global $wgParser, $codeTag, $languages;
        
        ReadLanguages();
                
        if($codeTag["advanced"]["mode"])
                $wgParser->setHook('code', 'AdvancedCodeTag');
                
        if($codeTag["simple"])        
                foreach($languages as $lang)
                {
                     $wgParser->setHook($lang,
                                   create_function( '$source,$argv,&$parser', '
#$source = trim($parser->replaceVariables($source));
                                         $geshi = new GeSHi($source,"' . $lang . '", $GLOBALS["languagesPath"]);
$lang = "'.$lang.'";
if ($lang != "r") {
@$geshi->set_keyword_group_style(1, "font-weight:bold;color:#0022aa;", false);
@$geshi->set_keyword_group_style(2, "color:#3311cc;", false);
@$geshi->set_keyword_group_style(3, "color:#3311cc;", false);
@$geshi->set_url_for_keyword_group(1, "");
@$geshi->set_url_for_keyword_group(2, "");
@$geshi->set_url_for_keyword_group(3, "");
$comment = "font-style:italic;color:#d00000;";
@$geshi->set_comments_style(1,$comment,false);
@$geshi->set_comments_style(2,eregi("^(as|c)$",$lang)?"font-style:italic;color:#ff44aa":$comment,false);
@$geshi->set_comments_style(3,$comment,false);
@$geshi->set_comments_style("MULTI",$comment,false);
@$geshi->set_escape_characters_style("color:#ff0000;",false);
@$geshi->set_brackets_style("font-weight:bold; color:#ff0000;",false);
@$geshi->set_strings_style("color:#0080aa;",false);
@$geshi->set_numbers_style("color:#000000;",false);
@$geshi->set_methods_style("color:#000000;",false);
@$geshi->set_symbols_style("color:#008000;",false);
@$geshi->set_regexps_style("color:#00ffaa;",false);
}
$text = $geshi->parse_code();
$text = preg_replace("/&amp;([lg]t;)/","&$1",$text);
return $text;'
				
                     ));
                }       
}

function ReadLanguages()
{       
        global $languages, $languagesPath;
        
        $dirHandle = opendir($languagesPath) 
                        or die("ERROR: Invalid directory path - [$languagesPath], Modify the value of \$languagesPath'");
        
        $pattern = "^(.*)\.php$";
                        
        while ($file = readdir($dirHandle))     
        {       
                if( preg_match( "|$pattern|i", $file ) )                            
                        $languages[] = preg_replace( "|$pattern|i", "$1", $file ); 
        }
        closedir($dirHandle);
}

function AdvancedCodeTag ($source, $settings){          

        global $languages, $languagesPath, $codeTag;
                
        $language = array_shift($settings);      // [arg1]      
        $isNumbered = array_shift($settings);    // [arg2]

                
        // [arg1]
        if($language == ''){
          $language='text';  // bugfix: to work for existing <code> tags, simply use "text"
        }
        if($language == "list")                                               // list all languages supported
            return "<br>List of supported languages for <b>Geshi " . GESHI_VERSION  . "</b>:<br>"
                   . implode("<br>", $languages);
                
        if($language != "" && !in_array($language, $languages))               // list languages if invalid argument
            return "<br>Invalid language argument, \"<b>" . $language . "</b>\", select one from the list:<br>" 
                   . implode("<br>", $languages);
        
        // set geshi
        $geshi = new GeSHi(trim($source), $language, $languagesPath); 
        $geshi->enable_strict_mode($codeTag["advanced"]["strict"]);        

        // [arg2]
        if($isNumbered == "n")                                                // display line numbers
            $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
                
        /*
          Add more GeSHi features below
          http://qbnz.com/highlighter/geshi-doc.html 
        */
               
        return $geshi->parse_code(); 
}


$wgHooks['ParserBeforeStrip'][] = 'GeSHi';
function GeSHi(&$parser, &$text, &$strip_state) {
	if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'raw' || $_REQUEST['action'] == 'xml')) return true;
        if (preg_match('/\\{\\{(xml|bash|php|perl|c|r|as|js|css|sql)\\}\\}/i',$text,$m)) $text = "<$m[1]>\n$text\n</$m[1]>";
        return true;
        }

