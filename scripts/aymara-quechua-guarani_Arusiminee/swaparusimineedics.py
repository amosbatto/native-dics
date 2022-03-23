#! /usr/bin/python
# -*- coding: UTF-8 -*-
'''
Author: Amos Batto <amosbatto@yahoo.com>, http://www.runasimipi.org 
Revised: 19 July 2009
License: Affero GPL, version 3.0
Usage: python swaparusimineedics.py ARUSIMIÑEE.HTML OUT-FILE

This script converts the dictionary _Arusimiñee_ (2004) published by the Bolivian
Ministry of Education to StarDict format. Before running this script, open the
arusimiñee.odt file in OpenOffice and Save As HTML (not XHTML). Before saving, 
go to Tools > Options > Load/Save > HTML Compatibility, and set OpenOffice to
save as HTML 3.2, so the i cortado (i with a strikethrough) will appear for Guaraní.

This script will automatically create the StarDict files. For more info, see:
/usr/share/doc/stardict-common/HowToCreateDictionary
'''

import re, sys, os

def prepDef(s):
	htmlTag = re.compile(r'<[A-Z/].*?>', re.DOTALL | re.MULTILINE | re.UNICODE)

	s = s.replace('<STRIKE>', '@STRIKE@')
	s = s.replace('</STRIKE>', '@/STRIKE@')
	s = htmlTag.sub('', s)
	s = s.replace('@STRIKE@','<s>')
	s = s.replace('@/STRIKE@', '</s>')
	s = re.sub('[\n\t\r\v ]+', ' ', s)

	return s.strip()

def multiKeyDef(s):
	'''multiKeyDef(s) creates another entry in the dictionary for every key which 
	is separated by a comma, which is useful for StarDict which can't search inside
	its dictionary keys, so secondary keys can't be found by searching. 
	Ex: multiKeyDef('tierra, mundo') returns the list ['tierra, mundo', 'mundo, tierra'] ''' 

	keys = re.split(',\s*', s)
	lRet = []

	for x in range(0, len(keys)):
		sDef = keys[x]

		for y in range (0, len(keys)):
			if x != y:
				sDef += ', ' + keys[y]
		
		lRet.append(sDef)

	return lRet


def formDef(lang, sDef):
	langs = {'es' : 'ESP', 'ay' : 'AYM', 'gu' : 'GUA', 'qu' : 'QUE'}

	if sDef == '':
		return ''

	return '<i>%s:</i> %s. ' % (langs[lang], sDef) 

def cleanI(s):
	s = s.replace('<s>', '')
	return s.replace('</s>', '')
	

fDicIn = open(sys.argv[1])
sDicIn = fDicIn.read()
fDicIn.close()

#print sDicIn.__len__()

dest = sys.argv[2]

fDicEs = open(dest + '-es.tab', 'w')
fDicAy = open(dest + '-ay.tab', 'w')
fDicGu = open(dest + '-gu.tab', 'w')
fDicQu = open(dest + '-qu.tab', 'w')

htmlEntities = {
	'&gt;': '>',
	'&lt;': '<',	  
	'&quot;' : '"'}

for key in htmlEntities.keys():
	sDicIn = sDicIn.replace(key, htmlEntities[key])

# each table is a letter in the alphabet	
lDicIn = re.findall(r'<TABLE.*?>(.*?)</TABLE>', sDicIn, re.DOTALL | re.MULTILINE | re.UNICODE)

#print len(lDicIn)

tableRow = re.compile(r'<TR.*?>(.*?)</TR>', re.DOTALL | re.MULTILINE | re.UNICODE)
tableCell = re.compile(r'<TD.*?>(.*?)</TD>', re.DOTALL | re.MULTILINE | re.UNICODE)
 	
for letter in lDicIn:
	if letter.find('Salustiano Ayma') != -1:
		continue

	lEntries = tableRow.findall(letter) 

	print len(lEntries)
	
	for entry in lEntries:
		lDefs = tableCell.findall(entry)
			
		if len(lDefs) != 4:
			print "Incorrect number of cells in entry:\n" + lDefs + "\n"
			continue
	
		es = prepDef(lDefs[0])
		ay = prepDef(lDefs[1])
		gu = prepDef(lDefs[2])
		qu = prepDef(lDefs[3])

		for key in multiKeyDef(es):
			if key != '':
				fDicEs.write('%s\t%s%s%s\n' % (key, formDef('qu', qu), formDef('ay', ay), formDef('gu', gu)))

		for key in multiKeyDef(ay):
			if key != '':
				fDicAy.write('%s\t%s%s%s\n' % (key, formDef('es', es), formDef('qu', qu), formDef('gu', gu)))
		
		for key in multiKeyDef(gu):
			if key != '':				
			
				fDicGu.write('%s\t%s%s%s%s\n' % (key, formDef('gu', gu), 
					formDef('es', es), formDef('qu', qu), formDef('ay', ay)))
				#uncomment the following lines if don't want keys words with <s>i</s> (ɨ,ɨ̈)
				#Note that StarDict can't handle searches for ɨ: 
				#fDicGu.write('%s\t%s%s%s%s\n' % (cleanI(key), formDef('gu', gu), 
				#	formDef('es', es), formDef('qu', qu), formDef('ay', ay)))

		for key in multiKeyDef(qu):
			if key != '':
				fDicQu.write('%s\t%s%s%s\n' % (key, formDef('es', es), formDef('ay', ay), formDef('gu', gu)))



fDicEs.close()
fDicAy.close()
fDicGu.close()
fDicQu.close()

dLangs = {'es': 'Español-Que, Aym, Gua', 'ay' : 'Aymara-Esp, Que, Gua', 
	'gu' : 'Guaraní-Esp, Que, Aym', 'qu' : 'Quechua-Esp, Aym, Gua'}

for lang in dLangs.keys():
	# Create StarDict files:
	fname = dest + '-' + lang

	try:
		os.mkdir(fname)
	except OSError:
		pass
	
	try:
		os.system('mv %s.tab %s/' % (fname, fname))
	except OSError:
		pass

	os.system('/usr/lib/stardict-tools/tabfile %s/%s.tab' % (fname, fname))


	# Write the StarDict IFO file:
	fIfo = open('%s/%s.ifo' % (fname, fname), 'r+')
	sIfo = fIfo.read()
	oWordCnt = re.search('wordcount=([0-9]+)', sIfo)
	sWordCnt = oWordCnt.group(1)
	oIdxSize = re.search('idxfilesize=([0-9]+)', sIfo)
	sIdxSize = oIdxSize.group(1)
	sIfo = 'StarDict\'s dict ifo file\n' \
		'version=3.0.0\n' \
		'wordcount=%s\n' \
		'idxfilesize=%s\n' \
		'bookname=Arusimiñee: %s\n' \
		'description=Arusimiñee: Castellano, Aymara, Guaraní, Quechua, Ministerio de Educación de Bolivia, La Paz, 2004, 121pp.' \
		'<br>Digitalizado por Runasimipi.org (2009)\n' \
		'author=Salustiano Ayma, José Barrientes, Gladys Márquez F.\n' \
		'year:2004\n' \
		'web:http://www.runasimipi.org\n' \
		'email:amosbatto@yahoo.com\n' \
		'sametypesequence=g\n' % (sWordCnt, sIdxSize, dLangs[lang])
	fIfo.seek(0)
	fIfo.truncate() # For some reason this doesn't work???
	fIfo.write(sIfo)
	fIfo.close()

sys.exit()

