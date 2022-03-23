#!/usr/bin/env python3

scriptHelp = '''
colorDicMojTrin.py es un script de Python3 que agrega colores en los dos archivos TAB 
del diccionario del Instituto de Lengua y Cultura Mojeño Trinitario "José Santos 
Noco Guaji" (2021) Taechirawkoriono Vechjiriiwo Trinranono: Diccionario Idioma 
Trinitario, Santa Cruz, Bolivia, 130pp. Despues de insertar los codigos de HTML 
en los archivos TAB, el script los pasa por tabfile de StarDict-Tools 
para producir los archivos de GoldenDict (y SimiDic-Builder.jar).

Uso: python3 colorDicMojTrin.py TRN-ES-DIC.tab ES-TRN-DIC.tab TRN-ES-DIR ES-TRN-DIR  

Donde:
TRN-ES-DIC.tab: Archivo TAB del diccionario trinitario-castellano.
ES-TRN-DIC.tab: Archivo TAB del diccionario castellano-trinitario.
TRN-ES-DIR:     Nombre del directorio para crear con los archivos trinitario-castellano.  
ES-TRN-DIR:     Nombre del directorio para crear con los archivos castellano-trinitario.

Autor: Amos Batto <amosbatto@yahoo.com>
Versión: 0.1 (2022-02-10)
Licencia: LGPL 3.0 (https://www.gnu.org/licenses/lgpl-3.0.en.html)  
'''
 
import re, sys, pathlib, os, os.path, shutil
 
def main():
	if len(sys.argv) != 5:
		sys.exit("Error en el número de argumentos.\n" + scriptHelp)
		
	trnTabFileName = sys.argv[1]
	esTabFileName  = sys.argv[2]
	trnDirName     = sys.argv[3]
	esDirName      = sys.argv[4]
	
	trnBookName = "bookname=Mojeño Trinitario–Español (ILC Trinitario JSNG)\n"
	esBookName  = "bookname=Español–Mojeño Trinitario (ILC Trinitario JSNG)\n"
	
	with open(trnTabFileName, "r") as trnTabFile:
		trnContents = trnTabFile.read()
		
	with open(esTabFileName, "r") as esTabFile:
		esContents = esTabFile.read()
	
	
	try:
		trnNewTabFile = open(trnDirName + ".tab", "w")
		esNewTabFile  = open(esDirName  + ".tab", "w")
	except:
		sys.exit("Error:" + sys.exc_info()[0])
		 
	
	trnContents = re.sub(r"[‘’]", r"'", trnContents)
	esContents  = re.sub(r"[‘’]", r"'", esContents )
	
	grammarAbrevs = \
		r"(\s)((adj|indef|poses|sing|adv|neg|deter|anat|aum|biol|bot|coloq|conj|"+\
		r"ger|interj|imper|mit|mod|indic|subj|loc|conj|verb|cond|n|obj|"+\
		r"direc|pl|pop|prep|prs|ind|pron|demos|excl|indef|interrog|pers|poses|"+\
		r"prnl|pres|indic|v|vtr|vintr|zool)\..*?:)"
	
	trnContents = re.sub(grammarAbrevs, r'\1<font color="#228B22"><b>\2</b></font>', trnContents, flags=re.I)
	esContents  = re.sub(grammarAbrevs, r'\1<font color="#228B22"><b>\2</b></font>', esContents , flags=re.I)
	
	trnContents = re.sub(r"\b(Ej[\d.:]{1,2})", r' <font color="purple">\1</font>', trnContents)
	esContents  = re.sub(r"\b(Ej[\d.:]{1,2})", r' <font color="purple">\1</font>', esContents )

	trnContents = re.sub(r" / ", r' <font color="purple">/</font> ', trnContents)
	esContents  = re.sub(r" / ", r' <font color="purple">/</font> ', esContents )
	
	trnContents = re.sub(r"\{(.+?)\}", r'<font color="blue">\1</font>', trnContents)
	esContents  = re.sub(r"\{(.+?)\}", r'<font color="blue">\1</font>', esContents )
	
	trnContents = re.sub(r"\b(\d\.-)\b", r"<b>\1</b>", trnContents)
	esContents = re.sub(r"\b(\d\.-)\b", r"<b>\1</b>", esContents)
	
	#strip any <font>...</font> from key words:
	trnContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", trnContents, flags=re.M)
	trnContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", trnContents, flags=re.M)
	trnContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", trnContents, flags=re.M)
	trnContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", trnContents, flags=re.M)
	
	esContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", esContents, flags=re.M)
	esContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", esContents, flags=re.M)
	esContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", esContents, flags=re.M)
	esContents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", esContents, flags=re.M)
	
	trnNewTabFile.write(trnContents)
	trnNewTabFile.close()
	
	esNewTabFile.write(esContents)
	esNewTabFile.close()
	
	if os.path.exists(trnDirName):
		shutil.rmtree(trnDirName, ignore_errors=True)
	
	if os.path.exists(esDirName):
		shutil.rmtree(esDirName, ignore_errors=True)
		
	os.mkdir(trnDirName)
	os.mkdir(esDirName)
	
	trnBaseName = trnDirName + os.sep + pathlib.Path(trnDirName).stem
	os.rename(trnDirName+".tab", trnBaseName + ".tab")
	os.system('/usr/lib/stardict-tools/tabfile "' + trnBaseName + '.tab"')
	
	if not os.path.exists(trnBaseName + '.ifo'):
		sys.exit("Error creando los archivos de GoldenDict: "+trnBaseName+".ifo")
	
	esBaseName = esDirName + os.sep + pathlib.Path(esDirName).stem
	os.rename(esDirName+".tab", esBaseName + ".tab")
	os.system('/usr/lib/stardict-tools/tabfile "' + esBaseName + '.tab"')
	
	if not os.path.exists(esBaseName + '.ifo'):
		sys.exit("Error creando los archivos de GoldenDict: "+esBaseName+".ifo")
	
	bookInfo = \
		'description=Instituto de Lengua y Cultura Mojeño Trinitario "José Santos '+\
		'Noco Guaji" (2021) Taechirawkoriono Vechjiriiwo Trinranono: Diccionario Idioma '+\
		"Trinitario, Santa Cruz, Bolivia, 130pp.<br><br>"+\
		"ABREVIATURAS USADAS EN EL DICCIONARIO <br>"+\
		"adj.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Adjetivo.<br>"+\
		"adj.indef.: &nbsp;Adjetivo indefinido.<br>"+\
		"adj.poses.: Adjetivo posesivo.<br>"+\
		"adj.sing.: &nbsp; &nbsp;Adjetivo singular.<br>"+\
		"adv.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Adverbio.<br>"+\
		"adv.a.: &nbsp; &nbsp; &nbsp; &nbsp;Adverbio de afirmación.<br>"+\
		"adv.c.: &nbsp; &nbsp; &nbsp; &nbsp;Adverbio de cantidad.<br>"+\
		"adv.l.: &nbsp; &nbsp; &nbsp; &nbsp; Adverbio de lugar.<br>"+\
		"adv.neg.: &nbsp; &nbsp;Adverbio de negación.<br>"+\
		"adv.t.: &nbsp; &nbsp; &nbsp; &nbsp; Adverbio de tiempo.<br>"+\
		"art.deter.: &nbsp; Artículo determinado.<br>"+\
		"anat.: &nbsp; &nbsp; &nbsp; &nbsp; Anatomía.<br>"+\
		"aum.: &nbsp; &nbsp; &nbsp; &nbsp; Aumentativo.<br>"+\
		"biol.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Biología.<br>"+\
		"bot.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Botánica.<br>"+\
		"coloq.: &nbsp; &nbsp; &nbsp; &nbsp;Coloquial.<br>"+\
		"conj.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Conjunción.<br>"+\
		"ger.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Gerundio.<br>"+\
		"interj.: &nbsp; &nbsp; &nbsp; &nbsp;Interjección.<br>"+\
		"imper.: &nbsp; &nbsp; &nbsp; Imperativo.<br>"+\
		"mit.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Mitología.<br>"+\
		"mod.Indic.: Modo indicativo.<br>"+\
		"mod.Subj.: &nbsp;Modo subjuntivo.<br>"+\
		"loc.adv.: &nbsp; &nbsp; &nbsp;Locución adverbial.<br>"+\
		"loc.conj.: &nbsp; &nbsp; Locución conjuntiva.<br>"+\
		"loc.verb.: &nbsp; &nbsp;Locución verbal.<br>"+\
		"mod.cond.: Modo condicional.<br>"+\
		"n.f.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Nombre femenino.<br>"+\
		"n.m.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Nombre masculino.<br>"+\
		"obj.Direc.: &nbsp; Objeto directo.<br>"+\
		"pl.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Plural.<br>"+\
		"pop.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Popular.<br>"+\
		"prep.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Preposición.<br>"+\
		"prs.ind.: &nbsp; &nbsp; &nbsp;Presente indicativo.<br>"+\
		"pron.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Pronombre.<br>"+\
		"pron.demos.: Pronombre demostrativo.<br>"+\
		"pron.excl.: &nbsp; Pronombre exclamativo.<br>"+\
		"pron.indef.: &nbsp;Pronombre indefinido.<br>"+\
		"pron.interrog.: Pronombre interrogativo.<br>"+\
		"pron.pers.: &nbsp; Pronombre personal.<br>"+\
		"pron.poses.: Pronombre posesivo.<br>"+\
		"prnl.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Pronominal.<br>"+\
		"pres.indic.: &nbsp; &nbsp;Presente indicativo.<br>"+\
		"v.imper.: &nbsp; &nbsp; &nbsp; &nbsp;Verbo impersonal.<br>"+\
		"vtr.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Verbo transitivo.<br>"+\
		"vintr.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Verbo intransitivo.<br>"+\
		"v.inf.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Verbo infinitivo.<br>"+\
		"zool.: &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Zoología.<br><br>"+\
		"Descargar otros diccionarios en https://github.com/amosbatto/native-dics\n"+\
		"sametypesequence=g"
	
	with open(trnBaseName + '.ifo', 'r+') as ifoFile:
		ifoContents = ifoFile.read()
		ifoContents = re.sub(r"bookname=.+", trnBookName + bookInfo, ifoContents, flags=re.S)
		ifoFile.seek(0)
		ifoFile.write(ifoContents)
		ifoFile.truncate()
	
	with open(esBaseName + '.ifo', 'r+') as ifoFile:
		ifoContents = ifoFile.read()
		ifoContents = re.sub(r"bookname=.+", esBookName + bookInfo, ifoContents, flags=re.S)
		ifoFile.seek(0)
		ifoFile.write(ifoContents)
		ifoFile.truncate()
	
	
	#os.rename(newBaseFileName+".tab", newDirName + os.sep + base+".tab")
	#os.rename(newBaseFileName+".ifo", newDirName + os.sep + base+".ifo")
	#os.rename(newBaseFileName+".idx", newDirName + os.sep + base+".idx")
	#os.rename(newBaseFileName+".dict.dz", newDirName + os.sep + base+".dict.dz")


if __name__ == "__main__":
    main()
