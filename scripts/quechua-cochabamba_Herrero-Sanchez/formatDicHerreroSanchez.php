<?php
/************************************************************************
Program: CleanDicHerreroSanchez.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2014-01-27, (La Paz, Bolivia)
License: Public domain 
*****************************************************************************/

$help = <<<HELP
cleanDicHerreroSanchez.php formats the HTML version of the Herrero-Sanchez 
quechua dictionary. The script adds blue to Quechua examples and purple to 
their Spanish translations. It also ensures that all "- " are not in cursive. 

To call this program:
   php cleanDicHerreroSanchez.php DICTIONARY.htm
   
For help:	
	php convertDicHerreroSanchez.php -h

DICTIONARY.htm is the filename of the "Diccionario Quechua: Estructura 
semántica del quechua cochabambino contemporáneo" by Joaquín Herrero
S.J. and Federico Sánchez de Lozada saved in HTML (UTF-8 encoding) by
LibreOffice.

OUTPUT is the optional filename for the generated files. If not included, 
then the generated files will have the same filename as DICTIONARY.htm with
"-formatted.htm" attached to end.

Requirements:
Assuming that using a Linux machine with UTF-8 default character set and PHP5 
installed.

HELP;

//PHP configuration:
ini_set('max_execution_time', 0);  //set no max time for this program to run.
ini_set('memory_limit', '-1');  //set no memory limit
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
setlocale(LC_ALL, 'es_BO.utf8');
mb_internal_encoding("UTF-8");

//process input parameters:	
if ($argc <= 1 or $argv[1] == '-h' or stristr($argv[1], 'help'))
	exit($help);

if ($argc < 2)
	exit("Error: Too few parameters.\n\n" . $help);
elseif ($argc > 3)
	exit("Error: Too many parameters.\n\n" . $help);
	
$sFDic = $argv[1];

//get filename of HTML output file:
if ($argc == 3) {
	$sFOutput = $argv[2];
}
else {
	$sFOutput = $sFDic;
	//strip out the path and the extension:	
	$fparts = pathinfo($sFOutput);
	$sFOutput = $fparts['filename'] . '-formatted.html';
}

//if can't open file or empty file, then exit.
if (!($sIn = file_get_contents($sFDic)))
	exit("Error: Can't open file '$sFDic' or file empty.\n");
	
//remove header:
$nBodyPosition = stripos($sIn, '<body>');
$sIn = substr($sIn, $nBodyPosition + strlen('<body>'));
$nBodyPosition = stripos($sIn, '</body>');
$sIn = substr($sIn, 0, $nBodyPosition);
	
//$sIn = preg_replace('/<div[^>]*>/', '<p>', $sIn);	
//$sIn = str_replace('</div>', '</p>', $sIn);

$sIn = preg_replace(
	'@<FONT COLOR="#008000"><SPAN STYLE="font-style: normal"><SPAN STYLE="font-weight: normal">\s+(Sin\.|V\.|Véase|Vulg\.)\s+</SPAN></SPAN></FONT>@m', 
	' <FONT COLOR="#008000">\1</FONT> ', $sIn);
$sIn = str_replace('<BR></I>', '</I><BR>', $sIn);
$sIn = preg_replace('@([\.\?!,;]) </I>@', '\1</I> ', $sIn); 
$sIn = preg_replace('@(- ?)</I>@', '</I>\1', $sIn);
$sIn = preg_replace('/<P[^>]+>/m', '<P>', $sIn);


//$sIn = strip_tags($sIn, '<p><i><u><sup><br><>');

$aParasOut = array();
$aParasIn = preg_split('@<P>[ \n]*(</P>)?@mi', $sIn);
print count($aParasIn) . "\n";

foreach ($aParasIn as $sPara) {
	$aLines = explode('<BR>', $sPara);
	$nLines = count($aLines);
//	if ($nLines == 1) {
//		$aParasOut[] = $sPara;
//		continue;
//	}
	$badFormat = false;
	$hasDash = false;
	
	for ($i = 0, $ii = 0; $i < count($aLines); $i++, $ii++) {
		$sLine = trim(strip_tags($aLines[$i]));
		
		if ($i == 0) {
			
			//convert key word(s) to lowercase in bold
		   if (preg_match("/^(['A-ZÑÁÉÍÓÚ¿¡?!\n +1-5]+)\.?\s+(A [a-záéíóúñ]|[¿¡]?[&A-ZÑÁÉÍÓÚa-záéíóú][a-zñáéíóú\.]|$)/m", $sLine, $aKeyMatch)) {
		   	$sKeyStripped = $aKeyMatch[1];
		   	$sDefStart = $aKeyMatch[2];
		   	
		   	if (empty($sDefStart)) {
		   		$sKey = str_replace('<I>', '', $aLines[0]);
		   		$sKey = str_replace('</I>', '', $sKey);
		   		$sKey = mb_strtolower($sKey, 'UTF-8');
		   		$sKey = preg_replace(array('/Ñ/', '/Á/', '/É/', '/Í/', '/Ó/', '/Ú/'), array('ñ', 'á', 'é', 'í', 'ó', 'ú'), $sKey);
		   		$aLines[0] = "<B>$sKey</B>";
		   	}
		   	elseif (!preg_match("/(<[A-Z][^>]*>)*$sDefStart/m", $aLines[0], $aDefMatch, PREG_OFFSET_CAPTURE)) {	
		   		print "Key '$sKeyStripped', no def '$sDefStart':\n{$aLines[0]}\n";
		   	}
		   	else {
		   		$sDef = substr($aLines[0], $aDefMatch[0][1]);
		   		$sKey = substr($aLines[0], 0, $aDefMatch[0][1]);
		   		$sKey = str_replace('<I>', '', $sKey);
		   		$sKey = str_replace('</I>', '', $sKey);
		   		$sKey = strtolower($sKey);
		   		$sKey = preg_replace(array('/Ñ/', '/Á/', '/É/', '/Í/', '/Ó/', '/Ú/'), array('ñ', 'á', 'é', 'í', 'ó', 'ú'), $sKey);
		   		$aLines[0] = "<B>$sKey</B> $sDef";
		   	}
		   }
		   //convert key word(s) to lowercase in bold
		   elseif (preg_match("@^<I>['A-ZÑÁÉÍÓÚ¿¡\?!\n +1-5]+\.</I>\s@m", $aLines[0], $aKeyMatch)) {
		   	$sKey = $aKeyMatch[0];
		   	$sDef = substr($aLines[0], strlen($sKey));
		   	$sKey = str_replace('<I>', '', $sKey);
		   	$sKey = str_replace('</I>', '', $sKey);
		   	$sKey = strtolower($sKey);
		   	$sKey = preg_replace(array('/Ñ/', '/Á/', '/É/', '/Í/', '/Ó/', '/Ú/'), array('ñ', 'á', 'é', 'í', 'ó', 'ú'), $sKey);
		   	$aLines[0] = "<B>$sKey</B>$sDef";
		   	//print "Check: $aLines[0]\n\n";   		
			}		   
		   elseif (!preg_match("/^Algunas/", $sLine)) {
		   	print "No key found:\n$sLine\n\n";
		   }
		}		   		 
		
		if (preg_match('/^[1-9]/', $sLine)) {
			if ($ii % 2 == 0)
				$badFormat = true;
			
			$ii = 0; 
		}
		elseif (preg_match('/^-/', $sLine)) {
			$hasDash = true;
			continue;  
		}
		
		if ($ii % 2 == 1) //if Quechua example, then place in light purple
			$aLines[$i] = '<FONT COLOR="#a403fb">'. $aLines[$i] . '</FONT>';
		
		if ($ii > 0 and $ii % 2 == 0) //if Spanish translation of Quechua example, then place in blue
			$aLines[$i] = '<FONT COLOR="blue">'. $aLines[$i] . '</FONT>';
		
		//check if bad format if last line of paragraph	
		if ($ii > 0 and count($aLines) == $i + 1 and $ii % 2 == 0)
			$badFormat = true;
			
	}
	
	if ($hasDash) {
		$aQuLines = array();
		$aEsLines = array(); 
		$nDashCount = 0;
		
		for ($i = 0; $i < count($aLines); $i++) {
			$sLine = trim(strip_tags($aLines[$i]));
			
			if (preg_match('/^-/', $sLine))
				$nDashCount++;
			
			//if last line of paragraph, then add 1 to line count
			if (count($aLines) == $i + 1)
				$i++;
			
			//if Dashcount is positive number and the current line doesn't have a dash or it is the last line of paragraph.	
			if ($nDashCount > 0 and (preg_match('/^[^\-]/', $sLine) or count($aLines) == $i)) {
				
				//if only 2 lines or an odd number of lines, then probably bad format
				if ($nDashCount == 2 or $nDashCount % 2 == 1) { 
					print "Bad format $nDashCount:\n$sPara\n";
				}
				//if 4 lines, assume 2 lines of Quechua followed by 2 lines of Spanish
				elseif ($nDashCount == 4) {
					$aQuLines = array_merge($aQuLines, array($i-4, $i-3)); 
					$aEsLines = array_merge($aEsLines, array($i-2, $i-1));
				}
				//if 6 lines, then assume 3 lines of Quechua, followed by 3 lines of Spanish
				elseif ($nDashCount == 6) {
					$aQuLines = array_merge($aQuLines, array($i-6, $i-5, $i-4)); 
					$aEsLines = array_merge($aEsLines, array($i-3, $i-2, $i-1));
				}
				//if 8 lines, assume 2 lines of Quechua, 2 lines of Spanish, 2 lines of Quechua, 2 lines of Spanish 
				elseif ($nDashCount == 8) {
					$aQuLines = array_merge($aQuLines, array($i-8, $i-7, $i-4, $i-3)); 
					$aEsLines = array_merge($aEsLines, array($i-6, $i-5, $i-2, $i-1));
				}					
				//if 10 lines, then print it, but assume 3 quechua, 3 spanish, 2 quechua, 2 Spanish
				elseif ($nDashCount == 10) {
					print "Check format $nDashCount:\n$sPara\n";
					$aQuLines = array_merge($aQuLines, array($i-10, $i-9, $i-8, $i-4, $i-3)); 
					$aEsLines = array_merge($aEsLines, array($i-7, $i-6, $i-5, $i-2, $i-1));
				}
				//if 12 lines, then print it, but assume 2 lines of quechua, followed by 2 lines of Spanish
				elseif ($nDashCount == 12) {
					//print "Check format $nDashCount:\n$sPara\n";
					$aQuLines = array_merge($aQuLines, array($i-12, $i-11, $i-8, $i-7, $i-4, $i-3)); 
					$aEsLines = array_merge($aEsLines, array($i-10, $i-9, $i-6, $i-5, $i-2, $i-1));
				}
				//if 16 lines, then print it, but assume 2 lines of quechua, followed by 2 lines of Spanish
				elseif ($nDashCount == 16) {
					//print "Check format $nDashCount:\n$sPara\n";
					$aQuLines = array_merge($aQuLines, array($i-16, $i-15, $i-12, $i-11, $i-8, $i-7, $i-4, $i-3)); 
					$aEsLines = array_merge($aEsLines, array($i-14, $i-13, $i-10, $i-9, $i-6, $i-5, $i-2, $i-1));
				}
				else {
					print "Check format $nDashCount:\n$sPara\n";
				}

				$nDashCount = 0;
			}
		}
		
		foreach($aQuLines as $nQuLine)
			$aLines[$nQuLine] = '<FONT COLOR="#a403fb">'. $aLines[$nQuLine] . '</FONT>';
			
		foreach($aEsLines as $nEsLine)
			$aLines[$nEsLine] = '<FONT COLOR="blue">'. $aLines[$nEsLine] . '</FONT>';	
		
	}
	$sPara = implode('<BR>', $aLines);
	$aParasOut[] = $sPara;
	
	//if ($badFormat)
	//	print "Bad Format:\n$sPara\n";
	
}

	
$header = <<<HEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
	<TITLE></TITLE>
	<meta name="generator" content="Bluefish 2.2.3" >
	<meta name="author" content="Amos Batto" >
	<META NAME="CREATED" CONTENT="20140131;12303300">
	<META NAME="CHANGEDBY" CONTENT="Amos Batto">
	<META NAME="CHANGED" CONTENT="20140208;1223200">
	<STYLE TYPE="text/css">
	<!--
		@page { size: landscape; margin: 0.2in }
		P { margin-bottom: 0.08in }
		A:link { so-language: zxx }
	-->
	</STYLE>
</HEAD>
<BODY LANG="es-BO" DIR="LTR">

HEADER;
$fOutput = fopen($sFOutput, 'w');
fwrite($fOutput, $header);

foreach ($aParasOut as $sParaOut) {
	//throw out empty paragraphs
	//if (trim($sParaOut) == '')
	//	continue;		
	
	fwrite($fOutput, "<P>" . $sParaOut ."</P>\n");  
}

fwrite($fOutput, "\n</BODY>\n</HTML>");
fclose($fOutput);

?>