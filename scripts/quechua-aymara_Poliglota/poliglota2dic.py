#! /usr/bin/python
# -*- coding: UTF-8 -*-
'''
Author: Amos Batto <amosbatto@yahoo.com>, http://www.runasimipi.org 
Revised: 18 Oct 2013
License: GPL, version 3.0
Usage: python poliglota2dic.py POLIGLOTA.TXT OUT-FILE
Note, needs Unicode support in Python 3, so may need to specify Python version 
(depending on distro):
	python3.2 poliglota2dic.py POLIGLOTA.TXT OUT-FILE
	
This script converts the dictionary _Vocabulario_Poliglota_Incaico_ (1998) [1905] 
published by the Peruvian Ministry of Education to StarDict and HTML format so it
can be used by StarDict, GoldenDict and SimiDic-Builder. Before running this script, 
save the dictionary spreadsheet as plain text, with tabs separating each cell
CASTELLANO CUZCO AYACUCHO JUNIN ANCASH AYMARA

This script will automatically creates a StarDict tab and inf files, plus a tab file with HTML
for the Castellano, Cuzco, Ayacucho, Junín and Aymara dictionaries. For more info, see:
/usr/share/doc/stardict-common/HowToCreateDictionary
GoldenDict uses the same StarDict files, but it needs HTML formatting instead of Pango Markup

To convert these dictionaries with SimiDic-Builder, use the pango files, then afterwards use
SQL to replace \n with <br>:
$ sqlite3 qj_po_fr.db
sqlite> update words set summary=replace(summary, '\n', ' ');
sqlite> update words set summary=replace(summary, '  ', ' ');
sqlite> update words set summary=replace(summary, '\...', '...');
sqlite> update words set meaning=replace(meaning, '\n', '<br>');

For some reason this line in the Quechua Junín dictionary causes problems with SimiDic-Builder, 
so it has to be removed:
  hapakama	<b>JUNÍN:</b> hapakama <span fgcolor="#696969">&lt;Japa cama&gt;</span> ...
After building the Sqlite3 database, it can be re-added with SQL:
$ sqlite3 qj_po_fr.db
sqlite> insert into words (word, meaning, summary) values ('hapakama', '<b>JUNÍN:</b> hapakama <span fgcolor="#696969">&lt;Japa cama&gt;</span>\n<b>CASTELLANO:</b> cada <span fgcolor="purple"><b>uno</b></span>\n<b>CUZCO:</b> sapa-sapanka <span fgcolor="#696969">&lt;Sapa-sapanca&gt;</span>\n<b>AYACUCHO:</b> sapakama <span fgcolor="#696969">&lt;Sapacama&gt;</span>\n<b>ANCASH:</b> hapan hapanka <span fgcolor="#696969">&lt;Japan japanca&gt;</span>, <span fgcolor="purple">[huknin hukninlla]</span>\n<b>AYMARA:</b> sapa maya', 'JUNÍN: hapakama <Japa cama>...');
'''
 
import re, sys, os, pprint, codecs, time

#getch() to get single keypress input 
#from http://code.activestate.com/recipes/577977-get-single-keypress/
try:
    import tty, termios
except ImportError:
    # Probably Windows.
    try:
        import msvcrt
    except ImportError:
        # FIXME what to do on other platforms?
        # Just give up here.
        raise ImportError('getch not available')
    else:
        getch = msvcrt.getch
else:
    def getch():
        """getch() -> key character

        Read a single keypress from stdin and return the resulting character. 
        Nothing is echoed to the console. This call will block if a keypress 
        is not already available, but will not wait for Enter to be pressed. 

        If the pressed key was a modifier key, nothing will be detected; if
        it were a special function key, it may return the first character of
        of an escape sequence, leaving additional characters in the buffer.
        """
        fd = sys.stdin.fileno()
        old_settings = termios.tcgetattr(fd)
        try:
            tty.setraw(fd)
            ch = sys.stdin.read(1)
        finally:
            termios.tcsetattr(fd, termios.TCSADRAIN, old_settings)
        return ch
#end of getch() code


def formatDef(s, lineNo):
	'''formatDef() formats an entry (line) in the Poliglota dictionary.
	s: line of text from dictionary
	lineNo: line number from the input file, used to help with debugging 
	Return value: a tuple with a list of keys and a list of definitions in the order of the languages.
	formatDef puts the original text <...> in grey, all grammatical abbreviations 
	(n., adj., etc.) in green and all inserted text [...] in purple. 
	It splits the line into the definitions for each language (castellano, cuzco, ayacucho, etc.) 
	For Spanish, it spits it into key word, more info and grammar abbreviations. 
	For the other languages, it strips strips <...> and adds to keys then constructs the definitions.'''
	
	lSplit = s.split("\t")
	if len(lSplit) != 6:
		exit("Error in line %d. Should be 5 tabs:\n%s" % (lineNo, s))
	
	lLangs = ["CASTELLANO", "CUZCO", "AYACUCHO", "JUNÍN", "ANCASH", "AYMARA"]
	lKeys  = ['', '', '', '', '', '']   #list to hold key words for each language
	lDefs  = ['', '', '', '', '', '']   #list to hold definitions for each language
	
	#Split Spanish entry between 1. key word, 2. more info and 3. grammar abbreviations
	#Ex: aparejado, -a. | (prevendio, preparado) | part.
	sEs = lSplit[0]
	sEsGrammar = ''
	sEsMore = ''
	mGrammar = re.search(r' (n|v|adj|adv|pron|pers|dem|part|prep|conj|interj|pref|fr|pref|excl|art|loc|pos|ger)\.', sEs)
	
	if mGrammar:
		sEsGrammar = sEs[mGrammar.start(0): ].strip()
		sEs = sEs[: mGrammar.start(0)].strip()
	
	mMore = re.search(r' [\(\[]', sEs) 
	
	if mMore:
		sEsMore = sEs[mMore.start(0): ].strip()
		sEs = sEs[: mMore.start(0)].strip()
	
	if sEsGrammar != '':
		sEsGrammar = '<i><span fgcolor="#228B22">' + sEsGrammar + '</span></i>'  #place grammar in green
	
	#Place alternative Spanish words added by editors in blue
	sEsMore = re.sub(r'(\[.+?\])', r'<span fgcolor="purple">\1</span>', sEsMore)
	lKeys[0] = sEs
	
	#Remove final "." if exists in key
	if sEs != '' and sEs[-1] == '.' and sEs.find('~') == -1:
		lKeys[0] = sEs[:-1]
		
	if sEsMore:
		sEs += ' ' + sEsMore
		lDefs[0] = sEsMore + ' '
	
	if sEsGrammar:
		sEs += ' ' + sEsGrammar
		lDefs[0] += sEsGrammar + ' '
	
	sDefs = ''
	#Loop to make Spanish entry with language labels:
	for i in range(1, 6):
		if lSplit[i].strip() == '':
			continue
		elif sDefs != '':
			sDefs += "\\n"
		 
		sDefs += "<b>%s:</b> %s" % (lLangs[i], lSplit[i].strip())
	
	#place <...> in grey and [...] in blue:
	sDefs = re.sub(r'(&lt;.+?&gt;)', r'<span fgcolor="#696969">\1</span>', sDefs)
	sDefs = re.sub(r'(\[.+?\])', r'<span fgcolor="purple">\1</span>', sDefs)
	sDefs = sDefs.replace('<span fgcolor="purple">[sic]</span>', '[sic]') 	#don't place "[sic]" inside <...> in purple
	sDefs = re.sub(r' (af|suf)\.', r' <i><span fgcolor="#228B22">\1.</span></i>', sDefs)
	
	if lDefs[0]:	#add new line if the Spanish definition has more info or grammar
		lDefs[0] += "\\n" 
	
	lDefs[0] += sDefs
	
	#Loop to make entry for each indigenous language:
	for i in range(1, 6):
		sKey = lSplit[i].strip()
		if sKey == '':
			continue
		#strip key of <...>, [sic], suf., af., commas at end of string
		sKey = re.sub(r'&lt;.+?&gt;', '', sKey)
		sKey = sKey.replace('[sic]', '')
		sKey = re.sub(r'[\.,] (suf|af|sufs|afs)\.', '', sKey)
		sKey = sKey.replace('[', '')
		sKey = sKey.replace(']', '')
		sKey = re.sub(r'(, *){2,}', ', ',sKey) # remove two or more commas in a row
		sKey = re.sub(r' {2,}', ' ', sKey).strip()
		sKey = re.sub(r',$', '', sKey)
		lKeys[i] = sKey.replace(' ,', ',')
		
		
		sDefs = "<b>%s:</b> %s" % (lLangs[i], lSplit[i].strip()) #add indigenous language of the dictionary
		sDefs += "\\n<b>CASTELLANO:</b> %s" % sEs                #add Spanish
		
		for ii in range(1, 6):	#add the rest of the indigenous languages
			if i == ii:
				continue
			sDefs += "\\n<b>%s:</b> %s" % (lLangs[ii], lSplit[ii].strip())
			
		#place <...> in grey and [...] in purple:
		sDefs = re.sub(r'(&lt;.+?&gt;)', r'<span fgcolor="#696969">\1</span>', sDefs)
		sDefs = re.sub(r'(\[.+?])', r'<span fgcolor="purple">\1</span>', sDefs)
		sDefs = sDefs.replace('<span fgcolor="purple">[sic]</span>', '[sic]') 	#don't place "[sic]" inside <...> in purple
		sDefs = re.sub(r' (af|suf|pref|afs|sufs)\.', r' <i><span fgcolor="#228B22">\1.</span></i>', sDefs)
		lDefs[i] = sDefs
	
	return (lKeys, lDefs) 


def multiKeyDef(s):
	'''multiKeyDef(s) creates another entry in the dictionary for every key which 
	is separated by a comma, which is useful for StarDict and SimiDic which can't search inside
	its dictionary keys, so secondary keys can't be found by searching. 
	Ex: multiKeyDef('tierra, mundo') returns the list ['tierra, mundo', 'mundo, tierra'] ''' 
	
	lKeys = re.split(',\s*', s)
	
	lRet = []
	
	for x in range(0, len(lKeys)):
		sDef = lKeys[x]
		
		for y in range (0, len(lKeys)):
			if x != y:
				sDef += ', ' + lKeys[y]
		
		lRet.append(sDef)

	return lRet

	

fDicIn = codecs.open(sys.argv[1], "r", encoding="utf-8")
sDicIn = fDicIn.read()
fDicIn.close()
lFilesPango = []
lFilesHtml = []
dest = sys.argv[2]
tFilenames = ("es", "qu-cuzco", "qu-ayacucho", "qu-junin", "qu-ancash", "ay")
tLangCodes = ('es', 'cuz', 'aya', 'jun', 'anc', 'aym')
tLangs =     ('Castellano', 'Cuzco', 'Ayacucho', 'Junín', 'Ancash', 'Aymara')

for filename in tFilenames:
#	lFilesPango.append(open(filename + '-' + dest + '-pango.tab',  'w'))
#	lFilesHtml.append( open(filename + '-' + dest + '-html.tab',   'w'))
	lFilesPango.append(codecs.open(filename + '-' + dest + '-pango.tab',  'w', encoding="utf-8"))
	lFilesHtml.append( codecs.open(filename + '-' + dest + '-html.tab',   'w', encoding="utf-8"))


htmlEntities = {
	'>' : '&gt;',
	'<' : '&lt;',
	'"' : '&quot;'
}

for key in htmlEntities.keys():
	sDicIn = sDicIn.replace(key, htmlEntities[key])

lLines = sDicIn.split("\n")
iLine = 0       #count lines processed from the input file
iEntries = 0    #count the entries written to output file
sLastKey = ''   #key word from the previous entry, saved if the current key has '~'
alwaysAnswer3 = False;

for sLine in lLines:
	iLine += 1
	if sLine.strip() == '' or sLine.find("CASTELLANO") != -1:
		continue
	
	lKeys, lDefs = formatDef(sLine, iLine)
	sKey = lKeys[0]
	
	if sKey.find('~') != -1: 
		sKeyToInsert = sLastKey
		#Check if both a masculine and feminine form of word and ask which should be inserted
		mGendered = re.match(r"([ a-zñáéíóúü'¡¿\-]+?)(os?), -(as?)", sLastKey, re.I)
		if alwaysAnswer3 == False and mGendered:
			sMasc = mGendered.group(1) + mGendered.group(2)
			sFem  = mGendered.group(1) + mGendered.group(3)
			sDefStripped = re.sub(r'(<.+?>)', '', lDefs[0])
			sDefStripped = sDefStripped.replace('&lt;', '<')
			sDefStripped = sDefStripped.replace('&gt;', '>')
			print('\nIn: %s\n%s\n\t[1]"%s", [2]"%s", [3]"%s" or [4]Always 3?' % (lKeys[0], sDefStripped, sMasc, sFem, sLastKey), end = "")
			iAnswer = getch()
			print(iAnswer)
			
			for i in range(0, 5):
				if iAnswer == "1":
					sKeyToInsert = sMasc
					break
				elif iAnswer == "2":
					sKeyToInsert = sFem
					break
				elif iAnswer == "3":
					sKeyToInsert = sLastKey
					break
				elif iAnswer == "4":
					sKeyToInsert = sLastKey
					alwaysAnswer3 = True;
					break
				elif i >= 3:
					print('Too many wrong answers! Selecting "%s"' % sMasc)
					sKeyToInsert = sMasc
					break
				else:
					print("Please enter 1 or 2:", end = "")
					iAnswer = getch()
					print(iAnswer)
					continue
	
	for i in range(0, len(tLangs)):
		sDef = lDefs[i]
		if lKeys[i] == '':
			continue 
		#if first entry don't add newline before line
		elif iEntries == 0:
			sEntryPango = "%s\t%s" % (lKeys[i], lDefs[i])
		#if in the Spanish dictionary and Spanish key contains '~', then add current entry to previous entry
		elif i == 0 and sKey.find('~') != -1:
			sDef = sDef.replace("~", "⁓")
			sEntryPango = '\\n<span fgcolor="grey"><s>     </s></span>\\n<b>%s</b>' % lKeys[i]
			if lDefs[i].find("<b>CUZCO:</b>") == 0:
				sEntryPango += "\\n" + sDef
			else:
				sEntryPango += ' ' + sDef
		elif sKey.find('~') != -1:  
			sDef = lDefs[i].replace('~', '<span fgcolor="purple"><b>%s</b></span>' % sKeyToInsert)
			sEntryPango = "\n%s\t%s" % (lKeys[i], sDef) 
		else:
			sEntryPango = "\n%s\t%s" % (lKeys[i], lDefs[i])
		
		#create HTML version for GoldenDict
		sEntryHtml = sEntryPango.replace('<span fgcolor=', '<font color=')
		sEntryHtml = sEntryHtml.replace('</span>', '</font>')
		sEntryHtml = sEntryHtml.replace(r'\n', '<br>')
		#goldendict has a darker background so change to a lighter green
		sEntryHtml = sEntryHtml.replace(r'="#228B22"', '="green"') 
		#goldendict and SimiDic has a darker background so change to a lighter blue-grey
		sEntryHtml = sEntryHtml.replace(r'="#696969"', '="#a4a4b7"') 
		
		lFilesHtml[i].write(sEntryHtml)
		
		#Write Pango (StarDict and SimiDic dictionaries)
		#Need to strip all "[]¿¡" from the key, because StarDict and SimiDic doesn't ignore 
		#these characters in searches like GoldenDict does
		if i == 0:   #if Spanish dictionary
			sEntryPango = re.sub(r'^[¡¿]', '', sEntryPango)
			sEntryPango = re.sub(r'^([ a-zñáéíóúü\-]*)\[([ a-zñáéíóúü\-]*)\]', r'\1\2', sEntryPango)
			lFilesPango[i].write(sEntryPango)
		else:        #all other dictionaries
			sKeyStripped = re.sub(r'[¡¿\[\]]', '', lKeys[i])
			#if not in Spanish and key phrase contains multiple keys separated by commas, then insert
			#a separate entry for each key.
			lMultiKeys = multiKeyDef(sKeyStripped)
			for iKey in range(0, len(lMultiKeys)):
				if iEntries == 0:
					sAltEntry = "%s\t%s" % (lMultiKeys[iKey], sDef)
				else:
					sAltEntry = "\n%s\t%s" % (lMultiKeys[iKey], sDef)
				lFilesPango[i].write(sAltEntry)
	
	
	#if not a continued entry with "~"
	if sKey.find('~') == -1: 
		sLastKey = sKey
		iEntries += 1

print("Lines Processed: %d\nSpanish Entries: %d\n" % (iLine, iEntries))

for i in range(0, len(tLangs)):	
	#write new line at end to avoid errors with tabfile:
	lFilesPango[i].write("\n")
	lFilesHtml[i].write("\n")
	lFilesPango[i].close()
	lFilesHtml[i].close()

for i in range(0, len(tLangs)):
	fname = tFilenames[i] + '-' + dest
	sFilePango = fname + '-pango.tab'
	sFileHtml  = fname + '-html.tab'
	
	#replace tildes with swung dash '⁓' (U+2053), but Python 2 is so strange about 
	#unicode support that it is far easier to use sed
#	os.system(r"sed s/~/⁓/g %s > %s" % (sFilePango, sFilePango))
#	time.sleep(5)
#	os.system(r"sed s/~/⁓/g %s > %s" % (sFileHtml, sFileHtml))
#	time.sleep(5)
	
	# Run StarDict's tabfile to create the StarDict (Pango) y GoldenDict (Html) files:
	try:
		os.mkdir(fname + '-pango')
		os.mkdir(fname + '-html')
	except OSError:
		pass
	
	os.system('tabfile %s-pango.tab' % fname)
	os.system('tabfile %s-html.tab' % fname)
	
	# Write the StarDict IFO file:
	fIfoPango = open('%s-pango.ifo' % fname, 'r+')
	fIfoHtml  = open('%s-html.ifo' % fname,  'r+')
	sIfoPango = fIfoPango.read()
	sIfoHtml  = fIfoHtml.read()
	
	mWordCnt = re.search('wordcount=([0-9]+)', sIfoPango)
	sWordCntPango = mWordCnt.group(1)
	mWordCnt = re.search('wordcount=([0-9]+)', sIfoHtml)
	sWordCntHtml = mWordCnt.group(1)
	
	mIdxSize = re.search('idxfilesize=([0-9]+)', sIfoPango)
	sIdxSizePango = mIdxSize.group(1)
	oIdxSize = re.search('idxfilesize=([0-9]+)', sIfoHtml)
	sIdxSizeHtml = oIdxSize.group(1)	
	
	if tLangs[i] == 'Castellano' or tLangs[i] == 'Aymara':
		lang = tLangs[i]
	else:
		lang = 'Quechua-' + tLangs[i]
		
	sIfoPango = "StarDict's dict ifo file\n" \
		'version=3.0.0\n' \
		'wordcount=%s\n' \
		'idxfilesize=%s\n' \
		'bookname=%s (Políglota)\n' \
		'description=Vocabulario Políglota Incaico: Comprende más de 12,000 voces castellanas y 100,000 de keshua del Cuzco, Ayacucho, Junín, Ancash y Aymará, Lima, Perú, 1905 [1998].\n' \
		'author=Franciscanos de Propaganda Fide del Perú\n' \
		'year=1905 [1998]\n' \
		'web=http://www.illa-a.org\n' \
		'email=amosbatto@yahoo.com\n' \
		'sametypesequence=g\n' % (sWordCntPango, sIdxSizePango, tLangs[i])
		
	sIfoHtml = "StarDict's dict ifo file\n" \
		'version=3.0.0\n' \
		'wordcount=%s\n' \
		'idxfilesize=%s\n' \
		'bookname=%s (Políglota) \n' \
		'description=Vocabulario Políglota Incaico: Comprende más de 12,000 voces castellanas y 100,000 de keshua del Cuzco, Ayacucho, Junín, Ancash y Aymará, Lima, Perú, 1905 [1998].\n' \
		'author=Franciscanos de Propaganda Fide del Perú\n' \
		'year=1905 [1998]\n' \
		'web=http://www.illa-a.org\n' \
		'email=amosbatto@yahoo.com\n' \
		'sametypesequence=g\n' % (sWordCntHtml, sIdxSizeHtml, tLangs[i])
		
	fIfoPango.seek(0)
	fIfoHtml.seek(0)
	fIfoPango.write(sIfoPango)
	fIfoHtml.write(sIfoHtml)
	fIfoPango.close()
	fIfoHtml.close()
	
	os.system('mv %s-pango.* %s-pango' % (fname, fname))
	os.system('mv %s-html.* %s-html' % (fname, fname))
	

sys.exit()

