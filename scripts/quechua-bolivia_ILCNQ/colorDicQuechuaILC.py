#!/usr/bin/env python3

scriptHelp = '''
colorDicQuechuaILC.py es un script de Python3 que agrega colores en el diccionario
Instituto de Lengua y Cultura de la Nación Quechua y CENAQ (2018) 
Qhichwa simipirwa: Diccionario de la Nación Quechua, Cochabamba, Bolivia, 558pp. 

Uso: python3 colorDicQuechuaILC.py ARCHIVO.tab ARCHIVO-NUEVO.tab [LENGUA]

LENGUA puede ser "qu" o "es". 

Autor: Amos Batto <amosbatto@yahoo.com>
Versión: 0.1 (2022-01-23)
Licencia: LGPL 3.0 (https://www.gnu.org/licenses/lgpl-3.0.en.html)  
'''
 
import re, sys, pathlib, os, os.path, shutil
 
def main():
	if len(sys.argv) < 3 or len(sys.argv) > 4:
		sys.exit("Error en el número de argumentos.\n" + scriptHelp)
		
	tabFileName    = sys.argv[1]
	newTabFileName = sys.argv[2]
	
	#default values:
	language = "qu"
	bookname = "bookname=Quechua Boliviano–Castellano (ILCNQ)\n"
	newDirName = "qu_es-ilcn-quechua"
	
	if len(sys.argv) == 4:
		if sys.argv[3] != "es" and sys.argv[3] != "qu":
			sys.exit('Error: La lengua debe ser "qu" o "es".\n' + scriptHelp)
		
		if sys.argv[3] == "es":
			language = "es"
			bookname = "bookname=Castellano–Quechua Boliviano (ILCNQ)\n"
			newDirName = "es_qu-ilcn-quechua"
	
	with open(tabFileName, "r") as tabFile:
		contents = tabFile.read()
	
	contents = re.sub(r"’", r"'", contents)
	contents = re.sub(r"<", r"&lt;", contents)
	
	if language == "qu":
		contents = re.sub( 
			r"(\t|\t.{0,15} |\\n)((allqu|aqha|asiku|awa|awqan|challwa|chiru|iñiy|k'uski|k'utu|abrev|khuru|Kiti|loc|kurku|llaqta|" + \
			r"soc|llimp'i|col|mallki|bot|masi|mikhu|musuq|neol|ñancha|ñawpa|ñin|onomat|p'isqu|orn|pacha|geog|" + \
			r"fis|silv|qillqa|graf|rumi|runa|pers|sañu|simi|ling|sunqu|emo|suti|onom|taki|" + \
			r"Mús|tarpu|agr|Tikra|met|tupu|ukhu|anat|uywa|wakin|p\.u|wanlla|juri|wasi|dom|wawa|infa|wallpa|"+ \
			r"khipu|yacha|yupa|yaw|lit)\.) ", 
			r'\1<font color="#0000FF">\2</font> ', contents, flags=re.M|re.I)
			
		contents = re.sub(r"(\s|\\n)(Pachakallpa|purum uywa)\b", r'\1<font color="#0000FF">\2</font> ', contents, flags=re.M|re.I)
		
	else: #if "es"
		contents = re.sub( 
			r"(\s|\\n)((allqu|aqha|asiku|awa|awqan|challwa|chiru|iñiy|k'uski|k'utu|abrev|khuru|Kiti|loc|kurku|llaqta|" + \
			r"soc|llimp'i|col|mallki|bot|masi|mikhu|musuq|neol|ñancha|ñawpa|ñin|onomat|p'isqu|orn|pacha|geog|" + \
			r"fis|silv|qillqa|graf|rumi|runa|pers|sañu|simi|ling|sunqu|emo|suti|onom|taki|" + \
			r"Mús|tarpu|agr|Tikra|met|tupu|ukhu|anat|uywa|wakin|p\.u|wanlla|juri|wasi|dom|wawa|infa|wallpa|"+ \
			r"khipu|yacha|yupa|yaw|lit)\.)", 
			r'\1<font color="#0000FF">\2</font>', contents, flags=re.M|re.I)
		
		contents = re.sub(r"(\s|\\n)(Pachakallpa|purum uywa)\b", r'\1<font color="#0000FF">\2</font> ', contents, flags=re.M|re.I)
	
	contents = re.sub( 
		r"(\s|\\n)((&lt;kas|&lt;aym|&lt;cuna|&lt;guar|&lt;nahua|&lt;latin|&lt;inglés|&lt;kallawaya|aq|excl|cp|ja|interj|k'|suf|ka|imp|kh|conj|musuq|neol|ñin|onomat|"+ \
		r"ph|part|r\.kh|v\.cop|r\.ku|v\.refl|r\.mp|v\.intr|r\.m\.r|v\.imp|r\.p|v\.tr|r\.t|adv|r\.wa-n|"+ \
		r"v\.pron|ranti|pron|rikuchiq|dem|pron\.dem|s\.ranti|pro\.s\.t|adj|adv|t\.ranti|y\.r|f\.v|"+ \
		r"y\.s|f\.n|s\.t|pron\.indet|pron\.int|pron\.dem\.pl|s\. y adj|adj\. y s|voc\.exclam|voc|exclam|pt)\.?)(,| |$)", r'\1<font color="#228B22">\2</font>\4', contents, flags=re.M|re.I)
	
	contents = re.sub( 
		r"(\s|\\n)(&lt;(kas|aym|cuna|guar|nahua|latin|inglés|kallawaya)\.?:)", r'\1<font color="#228B22">\2</font>', contents, flags=re.M|re.I)

		
	#deal with grammar that needs a period after it:
	contents = re.sub(r"(\s)(v|f|n|y|s|t|r)\.", r'\1<font color="#228B22">\2.</font>', contents, flags=re.M)
	
	contents = re.sub(r"(\s|\\n)(k'|ch\.?)(\s)", r'\1<font color="#228B22">\2</font>\3', contents)
	
	contents = re.sub(r"\b(CBB|CHU|PTS|LPZ|CUZ|qq|[sS]xx)\b", r'<font color="#800080">\1</font>', contents, flags=re.M) # purple = #800080
	
	contents = re.sub(r"\b((kikin|qhaway|awqan|ñawpan?|sinón): .+?)(\.|;|$|\()", r'<font color="#FF0000">\1</font>\3', contents, flags=re.M|re.I) # red = #FF0000
	
	# #8B4513 is the HTML color "SaddleBrown"
	contents = re.sub( 
		r"(\((smtq|Laymi salta|xa|arusimiñee|ab|jdb|bert|cer|ceq|pol|dgh|gro|Poma|Guz|Herbas|h&s|h&h|aul|" + \
		r"jl|lay|jal|lot|rk|rpaa|str|lrs|atd|pat|trbk|ilcq|alquivi|rh|h\. R\. R\.|ñancha|drae|montalvo|márquez|jayma|tiyay|aae|simi pirwa cenaq|ñawpa lay).*?\))", 
		r'<font color="#8B4513">\1</font>', contents, flags=re.M|re.I)
	
	contents = re.sub(r"\*", r'<font color="#0000FF">*</font>', contents) # #0000FF = blue
	
	#strip any <font>...</font> from key words:
	contents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", contents, flags=re.M)
	contents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", contents, flags=re.M)
	contents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", contents, flags=re.M)
	contents = re.sub(r"</?font.*?>(.*?)\t", r"\1\t", contents, flags=re.M)
	
	try:
		newTabFile = open(newTabFileName, "w")
	except:
		sys.exit("Error:" + sys.exc_info()[0])
		
	newTabFile.write(contents)
	newTabFile.close()
	
	os.system('/usr/lib/stardict-tools/tabfile "'+ newTabFileName +'"')
	
	newBaseFileName = os.path.splitext(newTabFileName)[0]  #get path without file extension.
	
	
	if not os.path.exists(newBaseFileName + '.ifo'):
		sys.exit("Error creando los archivos GoldenDict/StarDict.")
	
	bookname += \
		"description=Instituto de Lengua y Cultura de la Nación Quechua y CENAQ (2018) "+\
		"Qhichwa simipirwa: Diccionario de la Nación Quechua, Cochabamba, Bolivia, 558pp.<br><br>"+\
		"K'UTU SIMIKUNA / ABREVIATURAS:</b><br>"+\
		"&lt; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Jamun / viene de <br>"+\
		"&lt;arawak &nbsp; &nbsp; &nbsp; &nbsp;Arawak simimanta / viene de arawak <br>"+\
		"&lt;aym. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Aymara simimanta / viene de aymara <br>"+\
		"&lt;cuna &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Kuna simimanta / viene de la lengua cuna <br>"+\
		"&lt;guar. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Guaraní simimanta / viene de guaraní <br>"+\
		"&lt;kas. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Kastilla simimanta / viene del castellano <br>"+\
		"&lt;nahua &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Nahua simimanta / viene de la lengua nahua <br>"+\
		"allqu. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Allquchakuq simi / insulto <br>"+\
		"aq &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Aq ñin / exclamativo <br>"+\
		"aqha. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Aqhamanta / chicha <br>"+\
		"arusimiñee &nbsp; &nbsp; Aymara,qhichwa,guaraní / aimara, quechua, guaraní <br>"+\
		"asiku. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Asikunapaq jina / chiste <br>"+\
		"awa. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Awaykunamanta / tejidos <br>"+\
		"awqan &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Awqa simi, awqanakuq simi / antónimo <br>"+\
		"CBB &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Quchapampa / Cochabamba <br>"+\
		"challwa. &nbsp; &nbsp; &nbsp; &nbsp; Yakupi kawsaqmanta / peces <br>"+\
		"chiru. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Imayna kurkun, jawan / geometría <br>"+\
		"CHU &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Chukichaka / Chuquisaca <br>"+\
		"cp &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Chayjinalla parlanku / castellano popular <br>"+\
		"iñiy. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Kasqanta yuyanchik / creencia, religión <br>"+\
		"ja. / interj. &nbsp; &nbsp; &nbsp; Jawancharquy / interjección <br>"+\
		"k' / suf. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; K'askaq / sufijo <br>"+\
		"k'uski &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Yachay taripaymanta / investigación <br>"+\
		"k'utu / abrev. &nbsp; K'utusqa, juch’uyyachisqa simi / abreviatura <br>"+\
		"ka. / imp. &nbsp; &nbsp; &nbsp; &nbsp; Kamachinapaq / imperativo <br>"+\
		"kh. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Khuskachaq / conjunción <br>"+\
		"khuru. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Juch’uy kawsaq / gusanos, insectos <br>"+\
		"kikin &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Kikin yuyayniyuq simi / sinónimo <br>"+\
		"Kiti &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Maypi kasqan / lugar <br>"+\
		"kurku &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Kurkunamanta / de los cuerpos <br>"+\
		"llaqta / soc. &nbsp; &nbsp; &nbsp;Llaqtakunamanta / sociedad <br>"+\
		"llimp'i / col. &nbsp; &nbsp; &nbsp;Color <br>"+\
		"LPZ &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Chukiyapu / La Paz <br>"+\
		"mallki. / bot. &nbsp; &nbsp; &nbsp;Q’umir wiñaqkuna / plantas, botánica <br>"+\
		"masi. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Kikin runa kaq / relación entre pares <br>"+\
		"mikhu. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Mikhunamanta / alimentación <br>"+\
		"musuq.* / neol. Musuq simi / neologismo <br>"+\
		"ñancha &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Ñancharisqa simi / palabra normalizada <br>"+\
		"ñawpa &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Ñawpa simi / antiguo <br>"+\
		"ñin. / onomat. &nbsp; Uyarisqata yachapayan / onomatopeya <br>"+\
		"p'isqu / orn. &nbsp; &nbsp; &nbsp;Phawaqkunamanta / ornitología <br>"+\
		"pacha. / geog. &nbsp; Pachapi kaqkunamanta / geografía <br>"+\
		"Pachakallpa / fis. Pachakallpakamay / fisica <br>"+\
		"ph. / part. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Phatmasqa / participio <br>"+\
		"PTS &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;P’utuqsi / Potosí <br>"+\
		"purum uywa / silv. Sallqa uywa / silvestre <br>"+\
		"qillqa. / graf. &nbsp; &nbsp; Escritura, grafia <br>"+\
		"qq &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Qala Qala, P’utuqsi / Cala Cala, Norte de Potosí <br>"+\
		"r. / v. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Rimay, rimachiq / verbo <br>"+\
		"r.kh. / v.cop. &nbsp; &nbsp; &nbsp;Khuskachaq rimay; khuskachaq / verbo copulativo <br>"+\
		"r.ku. / v.refl. &nbsp; &nbsp; &nbsp;Kikinman urmaq rimay / verbo reflexivo <br>"+\
		"r.mp. / v.intr. &nbsp; &nbsp; Yuyaynin pachallanpi / verbo intransitivo <br>"+\
		"r.m.r. / v.imp. &nbsp; &nbsp;Mana runachu, wak ruwasqan rimay / verbo impersonal <br>"+\
		"r.p. / v.tr. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Yuyaynin sutiman urman / verbo transitivo <br>"+\
		"r.t. / adv. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Rimay tikran / adverbio <br>"+\
		"r.wa-n &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Yuyaynin runaman urman / verbo pronominal <br>"+\
		"ranti / pron. &nbsp; &nbsp; &nbsp; Juk simita wakmanta yuyarinapaq / pronombre <br>"+\
		"rikuchiq / dem. Rikhuchinapaq / demostrativo <br>"+\
		"rikhuchiq ranti / pro.dem. Pronombre demostrativo <br>"+\
		"rumi. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Rumikunamanta / mineral <br>"+\
		"runa. / pers. &nbsp; &nbsp; Runamanta / persona <br>"+\
		"s. / n. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Suti / nombre <br>"+\
		"s.ranti / pron. &nbsp; &nbsp; Suti ranti / pronombre <br>"+\
		"pro.s.t. / adj. &nbsp; &nbsp;Suti tikran; sutilli / adjetivo <br>"+\
		"sañu &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; T'urumanta ruwasqa cerámica <br>"+\
		"simi / ling. &nbsp; &nbsp; &nbsp; &nbsp;Lingüística <br>"+\
		"sunqu / emo. &nbsp; &nbsp;Sunquchasqa ruwaykunamanta / emoción <br>"+\
		"suti. / onom. &nbsp; &nbsp; Sutikunamanta / onomástica <br>"+\
		"sxx &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Siglo XX, P'utuqsi / Siglo XX, Prov. Bustillos, Norte Potosí<br>"+\
		"taki. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Takiykunamanta / música <br>"+\
		"tarpu. / agr. &nbsp; &nbsp; &nbsp;Tarpuykunamanta / agricultura <br>"+\
		"Tikra. / met. &nbsp; &nbsp; Tikrachiq / metafórico <br>"+\
		"t. / adv. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Rimay tikrachiy / adverbio <br>"+\
		"t.ranti / pro.int. Tapuq ranti / pronombre interrogativo <br>"+\
		"Trbk &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Tarabuco <br>"+\
		"tupu. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Tupunamanta / medidas <br>"+\
		"ukhu. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Ukhunchikmanta / anatomía <br>"+\
		"uywa. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Uywa, kawsaqkunamanta / animal doméstico <br>"+\
		"wakin. / p.u. &nbsp; &nbsp; Wakillan jina parlanku / poco usado <br>"+\
		"wanlla. / juri. &nbsp; &nbsp;Llaqtapi ruwaykunamanta / jurídico <br>"+\
		"wasi. / dom. &nbsp; &nbsp; Wasipi kaqkunamanta / de lo doméstico <br>"+\
		"wawa. / infa. &nbsp; &nbsp;Wawakunamanta / infantil <br>"+\
		"wallpa &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Wallpakamay / artes plasticas <br>"+\
		"y. / f. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Yuyaychaq; rimaycha / frase <br>"+\
		"y.r. / f.v. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Yuyay, rimaywan / frase verbal <br>"+\
		"y.s. / f.n. &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Yuyay, sutiwan / frase nominal <br><br>"+\
		"ÑAWIRISQA P'ANQA / FUENTES: <br>"+\
		"Nota: Al final de cada referencia bibliográfica se repite entre paréntesis la abreviación del autor que aparece al final de cada entrada en el diccionario.<br>"+\
		"(ab) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Balderrama Rocha, Ariel (2010) Qhichwa simipi yachachiymanta<br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; runap yuyaychaynin. (Tesis de licenciatura en EIB, UMSS)<br>"+\
		"(arusimiñee) Ayma, Salustiano, José Barrientos, Gladys Marquez (2004)<br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Arusimiñee: Castellano, Aymara, Guaraní, Qhichwa. La Paz: <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Ministerio de Educación.<br>"+\
		"(atd) &nbsp; &nbsp; &nbsp; &nbsp; Terán de Dick, Alicia. (Varios Trabajos)<br>"+\
		"(aul) &nbsp; &nbsp; &nbsp; &nbsp; Jacobs, Philip (2005) Aulex. Diccionario Quechua- español. <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; En línea. <br>"+\
		"(bert) &nbsp; &nbsp; &nbsp; &nbsp;Bertonio, Ludovico (1612) Vocabulario de la Lengua Aymara. <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Juli, Chucuito: Francisco del Canto.<br>"+\
		"(ceq) &nbsp; &nbsp; &nbsp; &nbsp; Club de Escritores Quechuas (1972) Diccionario trilingüe: <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; quechua-castellano-inglés, Tomo I. Ilustraciones por Saturnino <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Urquidi. Cochabamba: Club de escritores en Quechua.<br>"+\
		"(cer) &nbsp; &nbsp; &nbsp; &nbsp; Cerrón Palomino, Rodolfo (1994 Quechua Sureño: Diccionario <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; unificado: Quechua–castellano, castellano–Quechua. Lima: <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Biblioteca Nacional del Perú.<br>"+\
		"(dgh) &nbsp; &nbsp; &nbsp; &nbsp;Gonzales Holguín, Diego (1608) Vocabulario de la Lengua <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; General de todo el Perú llamada lengua Qquichua, o del Inca. <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Lima: imprenta de Francisco del Canto.<br>"+\
		"(gro) &nbsp; &nbsp; &nbsp; &nbsp; Grondín, Marcelo (1971/1980) Metodo de Quechua. Runa Simi. <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; La Paz/Cochabamba: Los Amigos del Libro.<br>"+\
		"(Guz) &nbsp; &nbsp; &nbsp; &nbsp;Guzmán Palomino, Luis (1992) Diccionario Quechua. En Linea:<br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Runapacha.<br>"+\
		"(Herbas) &nbsp; Herbas, Ángel (1992) Diccionario quechua-castellano<br>"+\
		"(h&h) &nbsp; &nbsp; &nbsp; &nbsp;Hornberger Nancy y Esteban Hornberger (1983) Diccionario <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; tri-lingüe, quechua de Cusco. Quechua–English–Castellano. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; La Paz: Qoya raymi.<br>"+\
		"(h&s) &nbsp; &nbsp; &nbsp; &nbsp;Herrero, Joaquin y Federico Sanchez (1974) Diccionario <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; quechua-castellano, castellano-quechua: para hispanohablantes <br>"+\
 		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; que estudian quechua: Instituto de Idiomas Maryknoll.<br>"+\
		"(jal) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Lira, Jorge A. (1944) Diccionario kkechuwa–español. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Universidad Nacional de Tucumán.<br>"+\
		"(jdb) &nbsp; &nbsp; &nbsp; &nbsp; Berríos, José David (1904) Elementos de grámatica de la <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; lengua keshua. Paris: garnier hermanos, Libreros editores.<br>"+\
		"(jl) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Lara, Jesús (1978) Diccionario qhëshua–castellano, <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; castellano–qhëshua. La Paz: Los Amigos del Libro.<br>"+\
		"(lay) &nbsp; &nbsp; &nbsp; &nbsp; Layme Ajacopa, Teófilo, Efraín Cazazola y Félix Layme Pairumani<br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (2007) Diccionario Bilingüe. Iskay simipi yuyayk’ancha. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Quechua–castellano, Castellano–quechua. Juk ñiqi p’anqata <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ñawirispa allichaq: Pedro Plaza Martínez. La Paz.<br>"+\
		"(Laymi salta) Ajacopa Pairumani, Sotero (2010) Léxico textil aymara y <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; quechua desde los saberes locales. RAE Lingüística, oralidad y <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; educación intercultural bilingüe. La Paz: Museo Nacional de <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Etnografía y Folklore.<br>"+\
		"(lot) &nbsp; &nbsp; &nbsp; &nbsp; Lott, Philip S. (2000) Bolivian Quechua–English Dictionary. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;En línea. <br>"+\
		"(pol) &nbsp; &nbsp; &nbsp; &nbsp; Franciscanos del Colegio de Propaganda Fide del Perú (1905) <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Vocabulario políglota incaico: comprende más de 12,000 voces <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; castellanas, keshua del Cuzco, Ayacucho, Jinín, Ancash y aimará. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Lima: Tipología del colegio de propaganda fide del Perú.<br>"+\
		"(Poma) &nbsp; &nbsp; Guamán Poma de Ayala, Felipe (1615/1980) Nueva Crónica y <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Buen Gobierno. John V. Murra y Rolena Adorno, eds.; traducciones <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; del quechua por Jorge L. Urioste. 3 Tomos. México D.F.: <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Siglo Veintiuno.<br>"+\
		"(rk) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Plaza Martínez, Pedro (1983) Glosario Quechua–castellano. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; La Paz: INEL (mimeo) (rk=rumi kancha)<br>"+\
		"(rk) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Plaza Martínez, Pedro (2010) Qallarinapaq. Curso básico de <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; quechua boliviano. Cochabamba: Fundación PROEIB Andes/SAIH.<br>"+\
		"(rpaa) &nbsp; &nbsp; &nbsp; Rosat Pontalti, Mons. Adalberto A. (2009) Diccionario <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Enciclopédico Quechua–castellano del Mundo Andino.<br>"+\
		"(smtq) &nbsp; &nbsp; &nbsp;Academia Mayor de la Lengua Quechua (2005) Diccionario <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Quechua-español-quechua: Qheswa-español-qheswa Simi Taqe. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Cusco: Gobierno Regional Cusco.<br>"+\
		"(str, lrs) &nbsp; &nbsp;Stark, Louse R. (1971) Sucre Quechua: A pedagogical grammar. <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Madison, Wisconsin: Department of Anthropology, University of<br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; Wisconsin.<br>"+\
		"(xa) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Albó, Xavier (1964) El quechua a su alcance I-II. La Paz: Alianza <br>"+\
		" &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; para el Progreso.<br><br>"+\
		"Descargar otros diccionarios en https://github.com/amosbatto/native-dics\n"+\
		"sametypesequence=g"
	
	with open(newBaseFileName + '.ifo', 'r+') as ifoFile:
		ifoContents = ifoFile.read()
		ifoContents = re.sub(r"bookname=.+", bookname, ifoContents, flags=re.S)
		ifoFile.seek(0)
		ifoFile.write(ifoContents)
		ifoFile.truncate()
	
	if os.path.exists(newDirName):
		shutil.rmtree(newDirName, ignore_errors=True)
		
	base = pathlib.Path(newTabFileName).stem
	os.mkdir(newDirName)
	os.rename(newBaseFileName+".tab", newDirName + os.sep + base+".tab")
	os.rename(newBaseFileName+".ifo", newDirName + os.sep + base+".ifo")
	os.rename(newBaseFileName+".idx", newDirName + os.sep + base+".idx")
	os.rename(newBaseFileName+".dict.dz", newDirName + os.sep + base+".dict.dz")


if __name__ == "__main__":
    main()
