#!/usr/bin/env python3

scriptHelp = '''
colorDicMojIgn.py agrega colores en el Diccionario Mojeño Ignaciano por 
el Instituto de Lengua y Cultura Mojeño Ignaciano “Salvador Chappy Muibar” (2021).
Despues de insertar los codigos de HTML en los 2 archivos TAB, el script los pasa 
por tabfile de StarDict-Tools para producir los archivos de GoldenDict 
(y SimiDic-Builder.jar). 

Uso: python3 colorDicMojIgn.py IGN-ES-DIC.tab ES-IGN-DIC.tab IGN-ES-DIR ES-IGN-DIR

Donde:
IGN-ES-DIC.tab: Archivo TAB del diccionario ignaciano-castellano.
ES-IGN-DIC.tab: Archivo TAB del diccionario castellano-ignaciano.
IGN-ES-DIR:     Nombre del directorio para crear con los archivos ignaciano-castellano.  
ES-IGN-DIR:     Nombre del directorio para crear con los archivos castellano-ignaciano.

Autor: Amos Batto <amosbatto@yahoo.com>
Versión: 0.1 (2022-02-15)
Licencia: LGPL 3.0 (https://www.gnu.org/licenses/lgpl-3.0.en.html)  
'''
 
import re, sys, pathlib, os, os.path, shutil
 
aReToReplace = {
	# #228B22 is ForestGreen
	r' Ver: '       : r' <font color="#228B22">Ver:</font> ',
	r' \(vestc\)'   : r' <font color="#228B22">(vestc)</font>',
	r' \(sc\)'      : r' <font color="#228B22">(sc)</font>',
	r' \(vis\)'     : r' <font color="#228B22">(vis)</font>',
	r' \(vic\)'     : r' <font color="#228B22">(vic)</font>',
	r' \(con\)'     : r' <font color="#228B22">(con)</font>',
	r' \(modismo\)' : r' <font color="#228B22">(modismo)</font>',
	r' \(scp\)'     : r' <font color="#228B22">(scp)</font>',
	r' \(pind\)'    : r' <font color="#228B22">(pind)</font>',
	r' \(n\)'       : r' <font color="#228B22">(n)</font>',
	r' \(adv\)'     : r' <font color="#228B22">(adv)</font>', 
	r' \(adj\)'     : r' <font color="#228B22">(adj)</font>',     
	r' \(v\)'       : r' <font color="#228B22">(v)</font>' 
}

 
def main():
	if len(sys.argv) != 5:
		sys.exit("Error en el número de argumentos.\n" + scriptHelp)
		
	ignTabFileName = sys.argv[1]
	esTabFileName  = sys.argv[2]
	ignDirName     = sys.argv[3]
	esDirName      = sys.argv[4]
	 
	
	with open(ignTabFileName, "r") as ignTabFile:
		ignContents = ignTabFile.read()
		
	with open(esTabFileName, "r") as esTabFile:
		esContents = esTabFile.read()
	
	  
	for reOriginal, reReplace in aReToReplace.items():
		ignContents = re.sub(reOriginal, reReplace, ignContents)
	
	for reOriginal, reReplace in aReToReplace.items():
		esContents  = re.sub(reOriginal, reReplace, esContents)
	
	#Replace grammar parts at beginning of definition:     
	ignContents = re.sub(r'\t(\(.*?\))', r'\t<font color="#228B22">\1</font>', ignContents)
	esContents  = re.sub(r'\t(\(.*?\))', r'\t<font color="#228B22">\1</font>', esContents)
	
	if os.path.exists(ignDirName):
		shutil.rmtree(ignDirName, ignore_errors=True)
	
	if os.path.exists(esDirName):
		shutil.rmtree(esDirName, ignore_errors=True)
		
	os.mkdir(ignDirName)
	os.mkdir(esDirName)
	
	ignBaseName = ignDirName + os.sep + pathlib.Path(ignDirName).stem
	esBaseName  = esDirName  + os.sep + pathlib.Path(esDirName).stem
	
	try:
		ignNewTabFile = open(ignBaseName + ".tab", "w")
		ignNewTabFile.write(ignContents)
		ignNewTabFile.close()
		
		esNewTabFile  = open(esBaseName  + ".tab", "w")
		esNewTabFile.write(esContents)
		esNewTabFile.close()
	except:
		sys.exit("Error:" + sys.exc_info()[0])
	
	
	os.system('/usr/lib/stardict-tools/tabfile "' + ignBaseName + '.tab"')
	
	if not os.path.exists(ignBaseName + '.ifo'):
		sys.exit("Error creando los archivos de GoldenDict: "+ignBaseName+".ifo")
	
	os.system('/usr/lib/stardict-tools/tabfile "' + esBaseName + '.tab"')
	
	if not os.path.exists(esBaseName + '.ifo'):
		sys.exit("Error creando los archivos de GoldenDict: "+esBaseName+".ifo")
	
	
	ignBookName = "bookname=Mojeño Ignaciano–Español (ILC Ignaciano)\n"
	esBookName  = "bookname=Español–Mojeño Ignaciano (ILC Ignaciano)\n"
	
	bookInfo = \
		"description=Instituto de Lengua y Cultura Mojeño Ignaciano “Salvador Chappy Muibar” (2021) "+\
		"Diccionario Mojeño Ignaciano, San Ignacio de Moxos, Bolivia.<br><br>"+\
		"ABREVIATURAS:<br>"+\
		"Sp. &nbsp; &nbsp;especie<br>"+\
		"- &nbsp; &nbsp; &nbsp; &nbsp;morfema ligado<br>"+\
		"“” &nbsp; &nbsp; &nbsp;correspondencia en castellano<br>"+\
		"() &nbsp; &nbsp; &nbsp; referencia, clases de palabras<br>"+\
		"Adj. &nbsp; adjetivo<br>"+\
		"Adv. &nbsp; adverbio<br>"+\
		"Con. &nbsp; conjunción<br>"+\
		"Int. &nbsp; &nbsp; interrogativo<br>"+\
		"N. &nbsp; &nbsp; &nbsp; nombre<br>"+\
		"Inj. &nbsp; &nbsp; interjección<br>"+\
		"V. &nbsp; &nbsp; &nbsp; verbo<br>"+\
		"Pron. &nbsp;pronombre<br>"+\
		"Loc. &nbsp; locativo<br>"+\
		"Art. &nbsp; &nbsp;artículo<br><br>"+\
		"Descargar otros diccionarios en https://github.com/amosbatto/native-dics\n"+\
		"sametypesequence=g"
	
	with open(ignBaseName + '.ifo', 'r+') as ifoFile:
		ifoContents = ifoFile.read()
		ifoContents = re.sub(r"bookname=.+", ignBookName + bookInfo, ifoContents, flags=re.S)
		ifoFile.seek(0)
		ifoFile.write(ifoContents)
		ifoFile.truncate()
	
	with open(esBaseName + '.ifo', 'r+') as ifoFile:
		ifoContents = ifoFile.read()
		ifoContents = re.sub(r"bookname=.+", esBookName + bookInfo, ifoContents, flags=re.S)
		ifoFile.seek(0)
		ifoFile.write(ifoContents)
		ifoFile.truncate()

  
  
if __name__ == "__main__":
    main()
