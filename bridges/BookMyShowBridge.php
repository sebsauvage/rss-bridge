<?php
class BookMyShowBridge extends BridgeAbstract {

	const MAINTAINER = 'captn3m0';
	const NAME = 'BookMyShow Bridge';
	const URI = 'https://in.bookmyshow.com';
	const MOVIES_IMAGE_BASE_FORMAT = 'https://in.bmscdn.com/iedb/movies/images/mobile/thumbnail/large/%s.jpg';
	const DESCRIPTION = 'Returns the latest events on BookMyShow';

	const PLAYS = 'PL';
	const EVENTS = 'CT';
	const MOVIES = 'MT';

	const CATEGORIES = array(
		self::PLAYS => 'Plays',
		self::EVENTS => 'Events',
		self::MOVIES => 'Movies',
	);

	const CITIES = array(
		// Most popular cities
		'Mumbai' => 'MUMBAI',
		'National Capital Region (NCR)' => 'NCR',
		'Bengaluru' => 'BANG',
		'Hyderabad' => 'HYD',
		'Ahmedabad' => 'AHD',
		'Chandigarh' => 'CHD',
		'Chennai' => 'CHEN',
		'Pune' => 'PUNE',
		'Kolkata' => 'KOLK',
		'Kochi' => 'KOCH',

		// Less common cities
		'Aalo' => 'AALU',
		'Abohar' => 'ABOR',
		'Abu Road' => 'ABRD',
		'Acharapakkam' => 'ACHA',
		'Adilabad' => 'ADIL',
		'Agar Malwa' => 'AGOR',
		'Agartala' => 'AGAR',
		'Agra' => 'AGRA',
		'Ahmedgarh' => 'AHMG',
		'Ahmednagar' => 'AHMED',
		'Aizawl' => 'AIZW',
		'Ajmer' => 'AJMER',
		'Akaltara' => 'AKAL',
		'Akividu' => 'AKVD',
		'Akola' => 'AKOL',
		'Alangudi' => 'ALNI',
		'Alappuzha' => 'ALPZ',
		'Alathur' => 'ALAR',
		'Alibaug' => 'ALBG',
		'Aligarh' => 'ALI',
		'Allagadda' => 'ALGD',
		'Almora' => 'ALMO',
		'Alwar' => 'ALWR',
		'Amadalavalasa' => 'ADAM',
		'Amalapuram' => 'AMAP',
		'Amaravathi' => 'AVTI',
		'Ambala' => 'AMB',
		'Ambikapur' => 'AMBI',
		'Ambur' => 'AMBR',
		'Amgaon' => 'AMGN',
		'Amravati' => 'AMRA',
		'Amritsar' => 'AMRI',
		'Anakapalle' => 'ANKP',
		'Anand' => 'AND',
		'Anantapalli' => 'ANTT',
		'Anantapur' => 'ANAN',
		'Anchal' => 'ANHL',
		'Angadipuram' => 'ANDM',
		'Angamaly' => 'ANGA',
		'Angara' => 'ANGR',
		'Angul' => 'ANGL',
		'Anjad' => 'ANJA',
		'Anjar' => 'ANJR',
		'Anklav' => 'ANKV',
		'Ankleshwar' => 'ANKL',
		'Annigeri' => 'ANGI',
		'Arakkonam' => 'ARAK',
		'Arambagh' => 'AMBH',
		'Aranthangi' => 'ARNT',
		'Ariyalur' => 'ARIY',
		'Arni' => 'ARNI',
		'Arsikere' => 'ARSI',
		'Aruppukottai' => 'ARUP',
		'Asansol' => 'ASANSOL',
		'Ashoknagar (West Bengal)' => 'ASNA',
		'Ashoknagar' => 'AKMP',
		'Aswaraopeta' => 'ASWA',
		'Atpadi' => 'ATPA',
		'Attili' => 'ATLI',
		'Aurangabad (Bihar)' => 'AUBI',
		'Aurangabad (West Bengal)' => 'AURW',
		'Aurangabad' => 'AURA',
		'Avinashi' => 'AVII',
		'Azamgarh' => 'AZMG',
		'B. Kothakota' => 'BKOT',
		'Badaun' => 'BADN',
		'Baddi' => 'BADD',
		'Badnawar' => 'BADR',
		'Bagbahara' => 'BBHA',
		'Bagha Purana' => 'BAPU',
		'Bagru' => 'BAGU',
		'Bahadurgarh' => 'BAHD',
		'Bahraich' => 'BHRH',
		'Baihar' => 'BIAH',
		'Baikunthpur' => 'BKTH',
		'Baindur' => 'BAND',
		'Bakhrahat' => 'BART',
		'Balaghat' => 'BLGT',
		'Balangir' => 'BALG',
		'Balasore' => 'BLSR',
		'Balijipeta' => 'BLIJ',
		'Balod' => 'BALD',
		'Baloda Bazar' => 'BBCH',
		'Balotra' => 'BALO',
		'Balrampur' => 'BLUR',
		'Balurghat' => 'BALU',
		'Bangarpet' => 'BAGT',
		'Banswada' => 'BNSA',
		'Banswara' => 'BANS',
		'Bantumilli' => 'BANT',
		'Barabanki' => 'BARK',
		'Baramati' => 'BARA',
		'Baraut' => 'BARL',
		'Bardoli' => 'BRDL',
		'Bareilly' => 'BARE',
		'Bargarh' => 'BARG',
		'Baripada' => 'BARI',
		'Barmer' => 'BARM',
		'Barnala' => 'BAR',
		'Barshi' => 'BRHI',
		'Barwani' => 'BRWN',
		'Basna' => 'BASN',
		'Basti' => 'BAST',
		'Bathinda' => 'BHAT',
		'Batlagundu' => 'BTGD',
		'Beawar' => 'BEAW',
		'Beed' => 'BEED',
		'Belagavi (Belgaum)' => 'BELG',
		'Bellampalli' => 'BELL',
		'Bellary' => 'BLRY',
		'Belur' => 'BELU',
		'Bemetara' => 'BMTA',
		'Berachampa' => 'BRAC',
		'Berhampore' => 'BEHA',
		'Berhampur' => 'BERP',
		'Bestavaripeta' => 'BEST',
		'Betul' => 'BETU',
		'Bhadrachalam' => 'BHDR',
		'Bhadrak' => 'BHAD',
		'Bhadravati' => 'BDVT',
		'Bhainsa' => 'BHAN',
		'Bhandara' => 'BHAA',
		'Bharamasagara' => 'BASA',
		'Bharuch' => 'BHAR',
		'Bhatapara' => 'BTAP',
		'Bhatkal' => 'BAKL',
		'Bhattiprolu' => 'BATT',
		'Bhavnagar' => 'BHNG',
		'Bhilai' => 'BHILAI',
		'Bhilwara' => 'BHIL',
		'Bhimadole' => 'BMDE',
		'Bhimavaram' => 'BHIM',
		'Bhiwadi' => 'BHWD',
		'Bhiwani' => 'BHWN',
		'Bhopal' => 'BHOP',
		'Bhubaneswar' => 'BHUB',
		'Bhuj' => 'BHUJ',
		'Bhuntar' => 'BHUN',
		'Bhupalpalle' => 'BHUP',
		'Bhusawal' => 'BHUS',
		'Biaora' => 'BIAR',
		'Bidar' => 'BIDR',
		'Bijnor' => 'BIJ',
		'Bijoynagar' => 'BIJO',
		'Bikaner' => 'BIK',
		'Bilara' => 'BILR',
		'Bilaspur (Himachal Pradesh)' => 'BIPS',
		'Bilaspur' => 'BILA',
		'Bilimora' => 'BILI',
		'Biraul' => 'BIRL',
		'Bishrampur' => 'BSRM',
		'Bodinayakanur' => 'BODI',
		'Boisar' => 'BOIS',
		'Bokaro' => 'BOKA',
		'Bolpur' => 'BLPR',
		'Bommidi' => 'BOMM',
		'Bongaigaon' => 'BONG',
		'Bongaon' => 'BONI',
		'Borsad' => 'BORM',
		'Brahmapur' => 'KHUB',
		'Brahmapuri' => 'BHMP',
		'Brajrajnagar' => 'BJNG',
		'Bulandshahr' => 'BULA',
		'Buldana' => 'BULD',
		'Bundu' => 'BUND',
		'Burdwan' => 'BURD',
		'Burhanpur' => 'BRHP',
		'Byadagi' => 'BYAD',
		'Chagallu' => 'CHAG',
		'Challakere' => 'CHLA',
		'Challapalli' => 'CHAP',
		'Champa' => 'CHAM',
		'Chanchal' => 'CCWC',
		'Chandausi' => 'CHDN',
		'Chandragiri' => 'CHAD',
		'Chandrakona' => 'CKNA',
		'Chandrapur' => 'CHAN',
		'Changanassery' => 'CNSY',
		'Channagiri' => 'CHGI',
		'Channarayapatna' => 'CHNN',
		'Chaygaon' => 'CHOG',
		'Cheepurupalli' => 'CHEE',
		'Chendrapinni' => 'CNPI',
		'Chengannur' => 'CHEG',
		'Chennur' => 'CHNU',
		'Cherial' => 'CHRY',
		'Cheyyar' => 'CHEY',
		'Chhibramau' => 'CHHI',
		'Chhindwara' => 'CHIN',
		'Chickmagaluru' => 'CHKA',
		'Chidambaram' => 'CHID',
		'Chikkaballapur' => 'CHIK',
		'Chikodi' => 'CHOK',
		'Chinturu' => 'CHTN',
		'Chirala' => 'CHIR',
		'Chitradurga' => 'CHIT',
		'Chittoor' => 'CHTT',
		'Chodavaram' => 'CDVM',
		'Chotila' => 'CHOT',
		'Coimbatore' => 'COIM',
		'Cooch Behar' => 'COBE',
		'Cuddalore' => 'CUDD',
		'Cuttack' => 'CUTT',
		'Dabra' => 'DABR',
		'Dahanu' => 'DHAU',
		'Dahegam' => 'DHGM',
		'Dahod' => 'DAHO',
		'Dakshin Barasat' => 'DAKS',
		'Dalli Rajhara' => 'DALL',
		'Daman' => 'DAMA',
		'Damoh' => 'DAMO',
		'Darjeeling' => 'DARJ',
		'Darsi' => 'DARS',
		'Dasuya' => 'DASU',
		'Dausa' => 'DAUS',
		'Davanagere' => 'DAVA',
		'Davuluru' => 'DVLR',
		'Deesa' => 'DEES',
		'Dehradun' => 'DEH',
		'Deoghar' => 'DOGH',
		'Devadurga' => 'DEVD',
		'Devarakonda' => 'DEVK',
		'Devgad' => 'DEGA',
		'Dewas' => 'DEWAS',
		'Dhampur' => 'DHPR',
		'Dhamtari' => 'DHMT',
		'Dhanbad' => 'DHAN',
		'Dhar' => 'DARH',
		'Dharamsala' => 'DMSL',
		'Dharapuram' => 'DHAR',
		'Dharmapuri' => 'DMPI',
		'Dharmavaram' => 'DDMA',
		'Dharwad' => 'DHAW',
		'Dhenkanal' => 'DNAL',
		'Dhoraji' => 'DHOR',
		'Dhule' => 'DHLE',
		'Dhuri' => 'DHRI',
		'Dibrugarh' => 'DIB',
		'Digras' => 'DIGR',
		'Dimapur' => 'DMPR',
		'Dindigul' => 'DIND',
		'Doddaballapura' => 'DDBP',
		'Domkal' => 'DMKL',
		'Dongargarh' => 'DONG',
		'Doraha' => 'DORH',
		'Durg' => 'DURG',
		'Durgapur' => 'DURGA',
		'Edappal' => 'EDPL',
		'Edlapadu' => 'EDLP',
		'Eluru' => 'ELRU',
		'Erattupetta' => 'ERAT',
		'Ernakulam' => 'ERNK',
		'Erode' => 'EROD',
		'Etawah' => 'ETWH',
		'Ettumanoor' => 'ETTU',
		'Faizabad' => 'FAZA',
		'Falna' => 'FALN',
		'Faridkot' => 'DKOT',
		'Fatehgarh Sahib' => 'FASA',
		'Fatehpur' => 'FATE',
		'Fatehpur(Rajasthan)' => 'FATR',
		'Firozpur' => 'FRZR',
		'G.Mamidada' => 'GMAD',
		'Gadag' => 'GADG',
		'Gadarwara' => 'GDWR',
		'Gadchiroli' => 'GDRO',
		'Gajendragarh' => 'GJGH',
		'Gajwel' => 'GAJW',
		'Ganapavaram' => 'GANP',
		'Gandhidham' => 'GDHAM',
		'Gandhinagar' => 'GNAGAR',
		'Gangavati' => 'GAVT',
		'Gangoh' => 'GANZ',
		'Gangtok' => 'GANG',
		'Ganjbasoda' => 'GANJ',
		'Garla' => 'GALA',
		'Gauribidanur' => 'GAUR',
		'Gaya' => 'GAYA',
		'Gingee' => 'GING',
		'Goa' => 'GOA',
		'Gobichettipalayam' => 'GOBI',
		'Godavarikhani' => 'GDVK',
		'Godhra' => 'GODH',
		'Gokak' => 'GKGK',
		'Gokavaram' => 'GOKM',
		'Golaghat' => 'GHT',
		'Gollaprolu' => 'GOLL',
		'Gonda' => 'GOND',
		'Gondia' => 'GNDA',
		'Gopalganj' => 'GOPG',
		'Gorakhpur' => 'GRKP',
		'Gorantla' => 'GORA',
		'Gotegaon' => 'GTGN',
		'Gownipalli' => 'GOWP',
		'Gudivada' => 'GUDI',
		'Gudiyatham' => 'GDTM',
		'Gudur' => 'GUDR',
		'Gulaothi' => 'GULL',
		'Guledgudda' => 'GULD',
		'Gummadidala' => 'GUMM',
		'Guna' => 'GUNA',
		'Guntakal' => 'GUNL',
		'Guntur' => 'GUNT',
		'Gurazala' => 'GURZ',
		'Guwahati' => 'GUW',
		'Gwalior' => 'GWAL',
		'Habra' => 'HARR',
		'Hagaribommanahalli' => 'HHGG',
		'Hajipur' => 'HAJI',
		'Haldia' => 'HLDI',
		'Haldwani' => 'HALD',
		'Haliya' => 'HALI',
		'Hampi' => 'HMPI',
		'Hardoi' => 'HRDI',
		'Haridwar' => 'HRDR',
		'Harihar' => 'HRRR',
		'Haripad' => 'HRPD',
		'Harugeri' => 'HARU',
		'Hasanpur' => 'HANS',
		'Hazaribagh' => 'HAZA',
		'Himmatnagar' => 'HIMM',
		'Hindaun City' => 'HIND',
		'Hisar' => 'HISR',
		'Honnali' => 'HONV',
		'Honnavara' => 'HNVR',
		'Hooghly' => 'HOOG',
		'Hoshiarpur' => 'HOSH',
		'Hoskote' => 'HOKT',
		'Hospet' => 'HOSP',
		'Hosur' => 'HSUR',
		'Howrah' => 'HWRH',
		'Hubballi (Hubli)' => 'HUBL',
		'Huvinahadagali' => 'HULI',
		'Ichalkaranji' => 'ICHL',
		'Ichchapuram' => 'ICPR',
		'Idappadi' => 'IDPI',
		'Idar' => 'IDAR',
		'Indapur' => 'INDA',
		'Indi' => 'IIND',
		'Indore' => 'IND',
		'Irinjalakuda' => 'IRNK',
		'Itanagar' => 'ITNG',
		'Itarsi' => 'ITAR',
		'Jabalpur' => 'JABL',
		'Jadcherla' => 'JADC',
		'Jagalur' => 'JAGA',
		'Jagatdal' => 'JGDL',
		'Jagdalpur' => 'JAGD',
		'Jaggampeta' => 'JAGG',
		'Jaggayyapeta' => 'JGGY',
		'Jagtial' => 'JGTL',
		'Jaipur' => 'JAIP',
		'Jaisalmer' => 'JSMR',
		'Jajpur Road' => 'JAJP',
		'Jalakandapuram' => 'JAKA',
		'Jalalabad' => 'JLAB',
		'Jalandhar' => 'JALA',
		'Jalgaon' => 'JALG',
		'Jalna' => 'JALN',
		'Jalpaiguri' => 'JPG',
		'Jami' => 'JAMI',
		'Jamkhed' => 'JAMK',
		'Jammalamadugu' => 'JAMD',
		'Jammu' => 'JAMM',
		'Jamnagar' => 'JAM',
		'Jamner' => 'JAMN',
		'Jamshedpur' => 'JMDP',
		'Jangaon' => 'JNGN',
		'Jangareddy Gudem' => 'JANG',
		'Janjgir' => 'JANR',
		'Jasdan' => 'JASD',
		'Jaunpur' => 'JANP',
		'Jehanabad' => 'JEHA',
		'Jetpur' => 'JETP',
		'Jewar' => 'JEWR',
		'Jeypore' => 'JEYP',
		'Jhabua' => 'JHAB',
		'Jhajjar' => 'JHAJ',
		'Jhansi' => 'JNSI',
		'Jharsuguda' => 'JRSG',
		'Jiaganj' => 'JAGJ',
		'Jind' => 'JIND',
		'Jodhpur' => 'JODH',
		'Jorhat' => 'JORT',
		'Junagadh' => 'JUGH',
		'Kadapa' => 'KDPA',
		'Kadi' => 'KADI',
		'Kaikaluru' => 'KAIK',
		'Kaithal' => 'KAIT',
		'Kakarapalli' => 'KAAP',
		'Kakinada' => 'KAKI',
		'Kalaburagi (Gulbarga)' => 'GULB',
		'Kalimpong' => 'KALI',
		'Kallakurichi' => 'KALL',
		'Kalol (Panchmahal)' => 'PANH',
		'Kalwakurthy' => 'KALW',
		'Kalyani' => 'KALY',
		'Kamanaickenpalayam' => 'KPLA',
		'Kamareddy' => 'KMRD',
		'Kamavarapukota' => 'KPKT',
		'Kambainallur' => 'KAMR',
		'Kamptee' => 'KAMP',
		'Kanakapura' => 'KAKP',
		'Kanchikacherla' => 'KNCH',
		'Kanchipuram' => 'KNPM',
		'Kandukur' => 'KAND',
		'Kangayam' => 'KGKM',
		'Kangra' => 'KANG',
		'Kanichar' => 'KANC',
		'Kanigiri' => 'KANI',
		'Kanipakam' => 'KAAM',
		'Kanjirappally' => 'KNNJ',
		'Kanker' => 'KANK',
		'Kannauj' => 'KANJ',
		'Kannur' => 'KANN',
		'Kanpur' => 'KANP',
		'Kanyakumari' => 'KAKM',
		'Karad' => 'KARD',
		'Karaikal' => 'KARA',
		'Karanja Lad' => 'KLAD',
		'Kareli' => 'KARE',
		'Karimangalam' => 'KARI',
		'Karimganj' => 'KRNJ',
		'Karimnagar' => 'KARIM',
		'Karjat' => 'KART',
		'Karkala' => 'KARK',
		'Karnal' => 'KARN',
		'Karunagapally' => 'KARG',
		'Karur' => 'KARU',
		'Karwar' => 'KWAR',
		'Kasdol' => 'KASD',
		'Kasgunj' => 'KASG',
		'Kashipur' => 'KASH',
		'Kasibugga' => 'KSBG',
		'Kathipudi' => 'KATP',
		'Kathua' => 'KATH',
		'Katihar' => 'KATI',
		'Kattappana' => 'AWCK',
		'Kaveripattinam' => 'KANM',
		'Kekri' => 'KEKR',
		'Keonjhar' => 'KNJH',
		'Kesinga' => 'KEGA',
		'Khachrod' => 'KHCU',
		'Khajipet' => 'KHAJ',
		'Khalilabad' => 'KHBD',
		'Khamgaon' => 'KHMG',
		'Khammam' => 'KHAM',
		'Khandwa' => 'KHDW',
		'Khanna' => 'KHAN',
		'Kharagpur' => 'KGPR',
		'Kharsia' => 'KHAS',
		'Khed' => 'KHED',
		'Khopoli' => 'KHOP',
		'Khurja' => 'KHUR',
		'Kichha' => 'KCHA',
		'Kishanganj' => 'KSGJ',
		'Kodad' => 'KODA',
		'Kodagu (Coorg)' => 'COOR',
		'Kodakara' => 'KDKR',
		'Kodungallur' => 'KODU',
		'Kokrajhar' => 'KKJR',
		'Kolar' => 'OLAR',
		'Kolhapur' => 'KOLH',
		'Kollam' => 'KOLM',
		'Kollengode' => 'KOLE',
		'Komarapalayam' => 'KOMA',
		'Kondagaon' => 'KNGN',
		'Kondlahalli' => 'KNAI',
		'Korba' => 'KRBA',
		'Kosamba' => 'KOSA',
		'Kota (AP)' => 'KOAN',
		'Kota' => 'KOTA',
		'Kothagudem' => 'KTGM',
		'Kothamangalam' => 'KTMM',
		'Kotkapura' => 'KOTK',
		'Kotpad' => 'KTPD',
		'Kotputli' => 'KPLI',
		'Kottayam' => 'KTYM',
		'Kovur (Nellore)' => 'KOVR',
		'Kovvur' => 'KOVU',
		'Koyyalagudem' => 'KOEM',
		'Kozhikode' => 'KOZH',
		'Kozhinjampara' => 'KOZA',
		'Krishnagiri' => 'KRHN',
		'Krishnanagar' => 'KNWB',
		'Krosuru' => 'KRSR',
		'Kruthivennu' => 'KRTH',
		'Kuchaman City' => 'KHCY',
		'Kukshi' => 'KUKS',
		'Kulithalai' => 'KULI',
		'Kullu' => 'KULU',
		'Kumbakonam' => 'KUMB',
		'Kunkuri' => 'KKRI',
		'Kurnool' => 'KURN',
		'Kurukshetra' => 'KURU',
		'Kutch' => 'KTCH',
		'Lakhimpur Kheri' => 'LKPK',
		'Lakhimpur' => 'LAHA',
		'Lakkavaram' => 'LRAM',
		'Lakshmeshwara' => 'LKSH',
		'Latur' => 'LAT',
		'Leh' => 'LEHL',
		'Lingasugur' => 'LING',
		'Lohardaga' => 'LOHA',
		'Lonavala' => 'LNVL',
		'Loni' => 'LONI',
		'Lucknow' => 'LUCK',
		'Ludhiana' => 'LUDH',
		'Macherla' => 'MACH',
		'Machilipatnam' => 'MAPM',
		'Madanapalle' => 'MDNP',
		'Maddur' => 'MADD',
		'Madhavaram' => 'MDHA',
		'Madhepura' => 'MHEA',
		'Madhira' => 'MADR',
		'Madurai' => 'MADU',
		'Magadi' => 'MAGA',
		'Mahabubabad' => 'MAHA',
		'Mahad' => 'MHAD',
		'Mahbubnagar' => 'MAHB',
		'Maheshwar' => 'MAHE',
		'Mahishadal' => 'MMAI',
		'Mahudha' => 'MAHU',
		'Malebennur' => 'MEBN',
		'Malegaon' => 'MALE',
		'Malerkotla' => 'MALR',
		'Mall' => 'MAAL',
		'Malout' => 'MALO',
		'Mamallapuram' => 'MMLL',
		'Manali' => 'MANA',
		'Manapparai' => 'MAPI',
		'Manawar' => 'MANW',
		'Mancherial' => 'MANC',
		'Mandapeta' => 'MAND',
		'Mandi Gobindgarh' => 'MBBH',
		'Mandla' => 'MADL',
		'Mandsaur' => 'MNDS',
		'Mandya' => 'MND',
		'Manendragarh' => 'MANE',
		'Mangalagiri' => 'MGLR',
		'Mangaldoi' => 'MANG',
		'Mangaluru (Mangalore)' => 'MLR',
		'Manikonda (AP)' => 'MNAP',
		'Manipal' => 'MANI',
		'Manjeri' => 'MAJR',
		'Mannargudi' => 'MANB',
		'Mannarkkad' => 'MKKA',
		'Mansa' => 'MNSA',
		'Manuguru' => 'MNGU',
		'Maraimalai Nagar' => 'MMNR',
		'Markapur' => 'MARK',
		'Marripeda' => 'MARR',
		'Marthandam' => 'MRDM',
		'Mathura' => 'MATH',
		'Mattannur' => 'MATT',
		'Mavellikara' => 'MVLR',
		'Medak' => 'MDAK',
		'Medarametla' => 'MDRM',
		'Meerut' => 'MERT',
		'Mehsana' => 'MEHS',
		'Memari' => 'MMRR',
		'Metpally' => 'METT',
		'Mettuppalayam' => 'MTPM',
		'Miryalaguda' => 'MRGD',
		'Mirzapur' => 'MIZP',
		'Moga' => 'MOGA',
		'Mohali' => 'MOHL',
		'Molakalmuru' => 'MOLA',
		'Moodbidri' => 'MOOD',
		'Moradabad' => 'MORA',
		'Moranhat' => 'MORH',
		'Morbi' => 'MOBI',
		'Morena' => 'MRMP',
		'Motihari' => 'MOTI',
		'Moyna' => 'MAYN',
		'Muddebihal' => 'MUDD',
		'Mudhol' => 'MUDL',
		'Mughalsarai' => 'MGSI',
		'Mukkam' => 'MUKM',
		'Muktsar' => 'MKST',
		'Mullanpur' => 'MULL',
		'Mummidivaram' => 'MUMM',
		'Mundakayam' => 'MUAM',
		'Mundra' => 'MUDA',
		'MUNNAR' => 'MUNN',
		'Muradnagar' => 'MRDG',
		'Murtizapur' => 'MUUR',
		'Musiri' => 'MUSI',
		'Mussoorie' => 'MSS',
		'Muvattupuzha' => 'MUVA',
		'Muzaffarnagar' => 'MUZ',
		'Muzaffarpur' => 'MUZA',
		'Mydukur' => 'MYDU',
		'Mysuru (Mysore)' => 'MYS',
		'Nabadwip' => 'NABB',
		'Nadiad' => 'NADI',
		'Nagaon' => 'NAAM',
		'Nagapattinam' => 'NGPT',
		'Nagari' => 'NAGI',
		'Nagarkurnool' => 'NGKL',
		'Nagda' => 'NAGD',
		'Nagercoil' => 'NAGE',
		'Nagothane' => 'NAGO',
		'Nagpur' => 'NAGP',
		'Naihati' => 'NHTA',
		'Nainital' => 'NAIN',
		'Nakhatrana' => 'NKHT',
		'Nalgonda' => 'NALK',
		'Namakkal' => 'NMKL',
		'Namchi' => 'NAMI',
		'Nanded' => 'NAND',
		'Nandigama' => 'NDGM',
		'Nandurbar' => 'NDNB',
		'Nandyal' => 'NADY',
		'Nanjanagudu' => 'NJGU',
		'Nanpara' => 'NANP',
		'Narasannapeta' => 'NRPT',
		'Narasaraopet' => 'NSPT',
		'Narayankhed' => 'NARY',
		'Narayanpur' => 'NRYA',
		'Nargund' => 'NRGD',
		'Narnaul' => 'NARN',
		'Narsampet' => 'NASP',
		'Narsapur' => 'NARP',
		'Narsipatnam' => 'NARS',
		'Nashik' => 'NASK',
		'Nathdwara' => 'NATW',
		'Navsari' => 'NVSR',
		'Nawalgarh' => 'NANA',
		'Nawanshahr' => 'NAVN',
		'Nawapara' => 'NAWA',
		'Nazira' => 'NZRA',
		'Nedumkandam' => 'NEDU',
		'Neemuch' => 'NMCH',
		'Nellimarla' => 'NLEM',
		'Ner Parsopant' => 'NERP',
		'New Tehri' => 'TEHR',
		'Neyveli' => 'NYVL',
		'Nidadavolu' => 'NDVD',
		'Nilagiri' => 'NIGA',
		'Nimbahera' => 'NIPA',
		'Nipani' => 'NIPN',
		'Nizamabad' => 'NIZA',
		'Nokha' => 'NKHA',
		'Nuzvid' => 'NZVD',
		'Nyamathi' => 'NYNT',
		'Ongole' => 'ONGL',
		'Ooty' => 'OOTY',
		'Osmanabad' => 'OSMA',
		'Ottapalam' => 'OTTP',
		'Padrauna' => 'PADR',
		'Pakala' => 'PAKA',
		'Pala' => 'PALL',
		'Palakkad' => 'PLKK',
		'Palakollu' => 'PLKL',
		'Palakonda' => 'PALK',
		'Palampur' => 'PALM',
		'Palanpur' => 'PALN',
		'Palasa' => 'PALS',
		'Palghar' => 'PALG',
		'Pali' => 'PAAL',
		'Pallipalayam' => 'PLLI',
		'Palwal' => 'PLWL',
		'Palwancha' => 'PLWA',
		'Pamarru' => 'PAMA',
		'Panchkula' => 'PNCH',
		'Pandalam' => 'PADM',
		'Pandharpur' => 'PNDH',
		'Panipat' => 'PAN',
		'Panruti' => 'PANT',
		'Papanasam' => 'PAPA',
		'Paralakhemundi' => 'PRKM',
		'Paratwada' => 'PARA',
		'Parbhani' => 'PARB',
		'Parchur' => 'PARC',
		'Parigi (Telangana)' => 'PARI',
		'Parvathipuram' => 'PRVT',
		'Patan' => 'PATA',
		'Pathalgaon' => 'PAHT',
		'Pathanamthitta' => 'PTNM',
		'Pathankot' => 'PATH',
		'Pathsala' => 'PATS',
		'Patiala' => 'PATI',
		'Patna' => 'PATN',
		'Pattambi' => 'PTMB',
		'Pattukkottai' => 'PATU',
		'Payakaraopeta' => 'PATE',
		'Payyanur' => 'PAYY',
		'Pedanandipadu' => 'PEDN',
		'Peddapalli' => 'PEDA',
		'Peddapuram' => 'PEDP',
		'Pen' => 'PEN',
		'Pendra' => 'PEND',
		'Pennagaram' => 'PENM',
		'Penuganchiprolu' => 'PENU',
		'Penugonda' => 'PDDG',
		'Perambalur' => 'PERA',
		'Peringottukurissi' => 'PERN',
		'Perinthalmanna' => 'PNTM',
		'Phagwara' => 'PHAG',
		'Phalodi' => 'PHLD',
		'Phaltan' => 'PHAL',
		'Pileru' => 'PLRU',
		'Pipariya' => 'PIPY',
		'Pithampur' => 'PITH',
		'Podili' => 'PODI',
		'Polavaram' => 'PLAB',
		'Pollachi' => 'POLL',
		'Pondicherry' => 'POND',
		'Ponduru' => 'PONU',
		'Ponnani' => 'PONN',
		'Porumamilla' => 'PORU',
		'Pratapgarh (Rajasthan)' => 'PTRT',
		'Pratapgarh (UP)' => 'PRAT',
		'Prathipadu' => 'PRTH',
		'Prayagraj (Allahabad)' => 'ALLH',
		'Proddatur' => 'PROD',
		'Pulluvila' => 'PULA',
		'Pulpally' => 'PULP',
		'Punalur' => 'PUNA',
		'Punganur' => 'PGNR',
		'Purnea' => 'PURN',
		'Purulia' => 'PURU',
		'Pusad' => 'PUSD',
		'Pusapatirega' => 'PREG',
		'Puttur' => 'PUTT',
		'Raebareli' => 'RAEB',
		'Rahimatpur' => 'RAHI',
		'Raibag' => 'RAIB',
		'Raigad' => 'RAI',
		'Raigarh' => 'RAIG',
		'Railway Koduru' => 'RLKD',
		'Raipur' => 'RAIPUR',
		'Raisinghnagar' => 'RSNG',
		'Rajamahendravaram (Rajahmundry)' => 'RJMU',
		'Rajapalayam' => 'RAYM',
		'Rajkot' => 'RAJK',
		'Rajnandgaon' => 'RAJA',
		'Rajpipla' => 'RJPA',
		'Rajpur' => 'RAJP',
		'Rajpura' => 'RARA',
		'Rajula' => 'RJLA',
		'Ramanagara' => 'RANG',
		'Ramayampet' => 'RAMP',
		'Ramgarhwa' => 'RGHA',
		'Ramnagar' => 'RAMN',
		'Rampur' => 'RAMU',
		'Ranaghat' => 'RANA',
		'Ranchi' => 'RANC',
		'Ranebennur' => 'RANE',
		'Rangia' => 'RAAA',
		'Raniganj' => 'RNGJ',
		'Ranipet' => 'RANI',
		'Ratlam' => 'RATL',
		'Ratnagiri (Odisha)' => 'RATO',
		'Ratnagiri' => 'RATN',
		'Ravulapalem' => 'RVPL',
		'Raxaul' => 'RAXA',
		'Rayachoti' => 'RYCT',
		'Rayavaram' => 'RAYA',
		'Renukoot' => 'RENU',
		'Repalle' => 'REPA',
		'Rewa' => 'RWAA',
		'Rewari' => 'REWA',
		'Rishikesh' => 'RKES',
		'Rishra' => 'RSRA',
		'Rohtak' => 'ROH',
		'Rourkela' => 'RKOR',
		'Routhulapudi' => 'ROUT',
		'Rudrapur' => 'RUDP',
		'Rupnagar' => 'RUPN',
		'Sadasivpet' => 'SADA',
		'Safidon' => 'SAFI',
		'Sagar' => 'SAMP',
		'Saharanpur' => 'SAHA',
		'Sakleshpur' => 'SASA',
		'Sakti' => 'SAKT',
		'Salem' => 'SALM',
		'Saligrama' => 'SGMA',
		'Salihundam' => 'SAHM',
		'Salur' => 'SALU',
		'Samalkota' => 'SAMA',
		'Sambalpur' => 'SAMB',
		'Sambhal' => 'SAML',
		'Samsi' => 'SAMS',
		'Sanawad' => 'SNWD',
		'Sangamner' => 'SMNE',
		'Sangareddy' => 'SARE',
		'Sangaria' => 'SAGR',
		'Sangli' => 'SANG',
		'Sangola' => 'SNGO',
		'Santhebennur' => 'STHB',
		'Saraipali' => 'SPAL',
		'Sarangarh' => 'SARH',
		'Sarangpur' => 'SARA',
		'Sardulgarh' => 'SARD',
		'Sarnath' => 'SART',
		'Sarni' => 'SARN',
		'Sasaram' => 'SARM',
		'Satara' => 'SATA',
		'Sathyamangalam' => 'STHY',
		'Satna' => 'SATN',
		'Sattenapalle' => 'SATL',
		'Secunderabad' => 'SCBD',
		'Seethanagaram' => 'SEET',
		'Sehore' => 'SEHO',
		'Semiliguda' => 'SIMI',
		'Sendhwa' => 'SEND',
		'Seoni Malwa' => 'SEMA',
		'Seoni' => 'SEON',
		'Shadnagar' => 'SHAD',
		'Shahada' => 'SHHA',
		'Shahdol' => 'SHAH',
		'Shahjahanpur' => 'SHJH',
		'Shajapur' => 'SJUR',
		'Shankarampet' => 'SHAN',
		'Shankarpally' => 'SKRP',
		'Sheorinarayan' => 'SHEO',
		'Shikaripur' => 'SHKR',
		'Shillong' => 'SHLG',
		'Shimla' => 'SMLA',
		'Shirali' => 'SHIR',
		'Shivamogga' => 'SHIA',
		'Shivpuri' => 'SHIV',
		'Shoranur' => 'SHNR',
		'Shrirampur' => 'SHUR',
		'Siddipet' => 'SDDP',
		'Sidlaghatta' => 'SIDL',
		'Sikar' => 'SIKR',
		'Silchar' => 'SIL',
		'Siliguri' => 'SILI',
		'Silvassa' => 'SILV',
		'Sindhanur' => 'SIND',
		'Sindhudurg' => 'SNDH',
		'Sinnar' => 'SINA',
		'Sircilla' => 'SIRC',
		'Sirohi' => 'SIRO',
		'Sirsi' => 'SRSI',
		'Siruguppa' => 'SPPA',
		'Sitamarhi' => 'SIMA',
		'Sitapur' => 'SITA',
		'Sivakasi' => 'SIV',
		'Sivasagar' => 'SVSG',
		'Solan' => 'SCO',
		'Solapur' => 'SOLA',
		'Sompeta' => 'SOMA',
		'Songadh' => 'SONG',
		'Sonipat' => 'RAIH',
		'Sonkatch' => 'SONH',
		'Sri Ganganagar' => 'SRIG',
		'Srikakulam' => 'SRKL',
		'Srinagar' => 'SRNG',
		'Srivaikuntam' => 'SRTA',
		'Srivilliputhur' => 'SRIV',
		'Station Ghanpur' => 'STGH',
		'Sultanpur' => 'SLUT',
		'Sulthan Bathery' => 'SULY',
		'Sundargarh' => 'SUND',
		'Surajpur' => 'SURA',
		'Surat' => 'SURT',
		'Surendranagar' => 'SRDN',
		'Suryapet' => 'SURY',
		'Tadepalligudem' => 'TADP',
		'Tallapudi' => 'TTPP',
		'Tallarevu' => 'TALL',
		'Talwandi Bhai' => 'TALW',
		'Tamluk' => 'TMLU',
		'Tanda' => 'TNDA',
		'Tandur' => 'TAND',
		'Tangutur' => 'TANG',
		'Tanuku' => 'TANK',
		'Tatipaka' => 'TATI',
		'Tenali' => 'TENA',
		'Tenkasi' => 'TENK',
		'Tezpur' => 'TEZP',
		'Thalassery' => 'THAY',
		'Thalayolaparambu' => 'THAL',
		'Thamarassery' => 'TMRY',
		'Thanipadi' => 'THPD',
		'Thanjavur' => 'TANJ',
		'Tharad' => 'THRD',
		'Theni' => 'THEN',
		'Thirubuvanai' => 'THRU',
		'Thiruthuraipoondi' => 'THND',
		'Thiruttani' => 'THTN',
		'Thiruvalla' => 'THVL',
		'Thiruvarur' => 'THVR',
		'Thodupuzha' => 'THOD',
		'Thorrur' => 'THOR',
		'Thottiyam' => 'THYM',
		'Thrissur' => 'THSR',
		'Thullur' => 'THUL',
		'Thuraiyur' => 'THYR',
		'Tilda Neora' => 'TNO',
		'Tindivanam' => 'TNVM',
		'Tinsukia' => 'TINS',
		'Tiptur' => 'TIPT',
		'Tiruchirappalli' => 'TRII',
		'Tirukoilur' => 'TRKR',
		'Tirunelveli' => 'TIRV',
		'Tirupati' => 'TIRU',
		'Tirupattur' => 'TRPR',
		'Tirupur' => 'TIRP',
		'Tirur' => 'TRUR',
		'Tiruvannamalai' => 'TVNM',
		'Titagarh' => 'TTGH',
		'Trichy' => 'TRIC',
		'Trivandrum' => 'TRIV',
		'Tumakuru (Tumkur)' => 'TUMK',
		'Tuticorin' => 'TTCN',
		'Udaipur' => 'UDAI',
		'Udaynarayanpur' => 'UDAY',
		'Udgir' => 'UDGR',
		'Udumalpet' => 'UDMP',
		'Udupi' => 'UDUP',
		'Ujjain' => 'UJJN',
		'Ulundurpet' => 'ULPT',
		'Umbergaon' => 'UMER',
		'Una' => 'BEEL',
		'Uthamapalayam' => 'UTHM',
		'Vadakara' => 'VDKR',
		'Vadakkencherry' => 'VDCY',
		'Vadalur' => 'VADA',
		'Vadanappally' => 'VADN',
		'Vadodara' => 'VAD',
		'Valigonda' => 'VALI',
		'Valluru' => 'VALL',
		'Valsad' => 'VLSD',
		'Vaniyambadi' => 'VANI',
		'Vapi' => 'VAPI',
		'Varadiyam' => 'VRYM',
		'Varanasi' => 'VAR',
		'Varkala' => 'VKAL',
		'Vatsavai' => 'VAST',
		'Vazhapadi' => 'VAZH',
		'Veeraghattam' => 'VEER',
		'Velangi' => 'VELG',
		'Velanthavalam' => 'VELM',
		'Vellakoil' => 'VELI',
		'Vellore' => 'VELL',
		'Vempalli' => 'VAIM',
		'Vemulawada' => 'VERU',
		'Venkatapuram' => 'VNKT',
		'Veraval' => 'VRAL',
		'Vetapalem' => 'VLEM',
		'Vijayapura (Bengaluru Rural)' => 'VIJP',
		'Vijayapura (Bijapur)' => 'VJPR',
		'Vijayarai' => 'VRAI',
		'Vijayawada' => 'VIJA',
		'Vikarabad' => 'VKBD',
		'Vikasnagar' => 'VKNG',
		'Vikravandi' => 'VIVI',
		'Villupuram' => 'VILL',
		'Virudhachalam' => 'VIDM',
		'Visnagar' => 'VISN',
		'Vizag (Visakhapatnam)' => 'VIZA',
		'Vizianagaram' => 'VIZI',
		'Vuyyuru' => 'VYUR',
		'Wai' => 'WAIP',
		'Wanaparthy' => 'WANA',
		'Wani' => 'WANI',
		'Warangal' => 'WAR',
		'Wardha' => 'WARD',
		'Warora' => 'WRRA',
		'Wyra' => 'WWAR',
		'Yadagirigutta' => 'YADG',
		'Yamunanagar' => 'YAMU',
		'Yavatmal' => 'YAVA',
		'Yelagiri' => 'YLGA',
		'Yelburga' => 'YELB',
		'Yellamanchili' => 'YLMN',
		'Yellandu' => 'YRLL',
		'Yemmiganur' => 'YEMM',
		'Zaheerabad' => 'ZAGE',
		'Zirakpur' => 'ZIRK',
	);

	const PARAMETERS = array(
		array(
			'city' => array(
				'name' => 'City',
				'type' => 'list',
				'defaultValue' => 'MUMBAI',
				'values' => self::CITIES,
			),

			'category' => array(
				'name' => 'Category',
				'type' => 'list',
				'defaultValue' => self::MOVIES,
				'values' => array(
					'Plays' => self::PLAYS,
					'Events' => self::EVENTS,
					'Movies' => self::MOVIES,
				),
			),
			'language' => array(
				'name' => 'Language',
				'type' => 'list',
				'defaultValue' => 'all',
				'values' => array(
					'All' => 'all',
					'Kannada' => 'Kannada',
					'English' => 'English',
					'Hindi' => 'Hindi',
					'Telugu' => 'Telugu',
					'Tamil' => 'Tamil',
					'Malayalam' => 'Malayalam',
					'Gujarati' => 'Gujarati',
					'Assamese' => 'Assamese',
				)
			)
		)
	);

	// Headers used in the generated table for Events/Plays
	// Left is the BMS API Key, and right is the rendered version
	const TABLE_HEADERS = array(
		'Genre' => 'Genre',
		'Language' => 'Language',
		'Length' => 'Length',
		'EventIsGlobal' => 'Global (Y/N)',
		'MinPrice' => 'Minimum Price',
		'IsSuperstarExclusiveEvent' => 'SuperStar Exclusive (Y/N)',
		'EventSoldOut' => 'Sold Out (Y/N)',
	);

	// Picked from EventGroup entry for movies
	// Left is BMS API Ke, and right is the rendered version
	const MOVIE_TABLE_HEADERS = array(
		'Duration' => 'Screentime',
		'EventCensor' => 'Rating',
	);

	// Picked from the ChildEvents entries inside a Event Group
	// for Movies
	// Left is BMS API Key, right is rendered version
	const INNER_MOVIE_HEADERS = array(
		'EventLanguage' => 'Language',
		'EventDimension' => 'Formats',
		'EventIsAtmosEnabled' => 'Dolby Atmos',
		'IsMovieClubEnabled' => 'Movie Club'
	);

	// Primary URL for fetching information
	// The city information is passed via a cookie
	const URL_PREFIX = 'https://in.bookmyshow.com/serv/getData?cmd=QUICKBOOK&type=';

	private function makeUrl($category){
		return self::URL_PREFIX . $category;
	}

	/**
	 * Given an event array, generates corresponding HTML entry
	 * @param  array $event
	 * @see https://gist.github.com/captn3m0/6dbd539ca67579d22d6f90fab710ccd2 Sample JSON data for various events
	 */
	private function generateEventHtml($event, $category){
		switch ($category) {
			case self::MOVIES:
				$html = $this->generateMovieHtml($event);
				break;
			default:
				$html = $this->generateStandardHtml($event);
		}

		$venues = $event['arrVenues'];
		$dates = $event['arrDates'];

		$html .= $this->generateVenueHtml($venues, $dates);

		return $html;
	}

	/**
	 * Generates a simple Venue HTML, even for multiple venues
	 * spread across multiple dates as a description list.
	 */
	private function generateVenueHtml($venues, $dates){
		$html = '<h3>Venues</h3>';

		foreach ($venues as $i => $venueData) {
			$venueName = $venueData['VenueName'];
			$address = $venueData['VenueAddress'];
			$lat = $venueData['VenueLatitude'];
			$lon = $venueData['VenueLongitude'];

			$directions = $this->generateDirectionsHtml($lat, $lon, $venueName);
			$html .= "<dt>$venueName</dt><dd><div>$address<br>$directions<br></div></dd>";
		}

		return "<div><dl>$html</dl></div>";
	}

	/**
	 * Generates a simple Table with event Data
	 * @todo Add support for jsonGenre as a tags row
	 */
	private function generateEventDetailsTable($event, $headers = self::TABLE_HEADERS){
		$table = '';
		foreach ($headers as $key => $header) {
			if ($header == 'Language') {
				$this->languages = array($event[$key]);
			}
			$table .= <<<EOT
			<tr>
				<th>$header</th>
				<td>$event[$key]</td>
			</tr>
EOT;
		}

		return "<table>$table</table>";
	}

	private function generateStandardHtml($event){
		$table = $this->generateEventDetailsTable($event);

		$imgsrc = $event['BannerURL'];

		return <<<EOT
		<img title="Event Banner URL" src="$imgsrc"></img>
		<br>
		$table
		<br>
		More Details are available on the <a href="${event['FShareURL']}">BookMyShow website</a>.
EOT;
	}

	/**
	 * Converts some movie details from child entries, such as language
	 * into a single row item, either as a list, or as a Y/N
	 */
	private function generateInnerMovieDetails($data){
		// Show list of languages and list of formats
		$headers = array('EventLanguage', 'EventDimension');
		// if any of these has a Y for any of the screenings, mark it as YES
		$booleanHeaders = array(
			'EventIsAtmosEnabled', 'IsMovieClubEnabled'
		);

		$items = array();

		// Throw values inside $items[$headerName]
		foreach ($data as $row) {
			foreach ($headers as $header) {
				$items[$header][] = $row[$header];
			}
			foreach ($booleanHeaders as $header) {
				$items[$header][] = $row[$header];
			}
		}

		// Remove duplicate values (if all screenings are 2D for eg)
		foreach ($headers as $header) {
			$items[$header] = array_unique($items[$header]);

			if ($header == 'EventLanguage') {
				$this->languages = $items[$header];
			}
		}

		$html = '';

		// Generate a list for first kind of entries
		foreach ($headers as $header) {
			$html .= self::INNER_MOVIE_HEADERS[$header] . ': ' . join(', ', $items[$header]) . '<br>';
		}

		// Put a yes for the boolean entries
		foreach ($booleanHeaders as $header) {
			if(in_array('Y', $items[$header])) {
				$html .= self::INNER_MOVIE_HEADERS[$header] . ': Yes<br>';
			}
		}

		return $html;
	}

	private function generateMovieHtml($eventGroup){
		$data = $eventGroup['ChildEvents'][0];
		$table = $this->generateEventDetailsTable($data, self::MOVIE_TABLE_HEADERS);

		$imgsrc = sprintf(self::MOVIES_IMAGE_BASE_FORMAT, $data['EventImageCode']);

		$url = $this->generateMovieUrl($eventGroup);

		$innerHtml = $this->generateInnerMovieDetails($eventGroup['ChildEvents']);

		return <<<EOT
		<img title="Movie Poster" src="$imgsrc"></img>
		<br>
		$table
		<br>
		$innerHtml
		<br>
		More Details are available on the <a href="$url">BookMyShow website</a> and a trailer is available
		<a href="${data['EventTrailerURL']}" title="Trailer URL">here</a>
EOT;

	}

	/**
	 * Generates a canonical movie URL
	 */
	private function generateMovieUrl($eventGroup){
		return self::URI . '/movies/' . $eventGroup['EventURLTitle'] . $eventGroup['EventCode'];
	}

	private function generateMoviesData($eventGroup){
		// Additional data picked up from the first Child Event
		$data = $eventGroup['ChildEvents'][0];

		return array(
			'uri' => $this->generateMovieUrl($eventGroup),
			'title' => $eventGroup['EventTitle'],
			'timestamp' => $data['EventDate'],
			'author' => 'BookMyShow',
			'content' => $this->generateMovieHtml($eventGroup),
			'enclosures' => array(
				sprintf(self::MOVIES_IMAGE_BASE_FORMAT, $data['EventImageCode']),
			),
			// Sample Input = |ADVENTURE|ANIMATION|COMEDY|
			// Sample Output = ['Adventure', 'Animation', 'Comedy']
			'categories' => array_filter(
				explode('|', ucwords(strtolower($eventGroup['EventGrpGenre']), '|'))
			),
			'uid' => $eventGroup['EventGroup']
		);
	}

	private function generateEventData($event, $category){
		if($category == self::MOVIES) {
			return $this->generateMoviesData($event);
		}

		return $this->generateGenericEventData($event, $category);
	}

	/**
	 * Takes an event data as array and returns data for RSS Post
	 */
	private function generateGenericEventData($event, $category){
		return array(
			'uri' => $event['FShareURL'],
			'title' => $event['EventTitle'],
			'timestamp' => $event['Event_dtmCreated'],
			'author' => 'BookMyShow',
			'content' => $this->generateEventHtml($event, $category),
			'enclosures' => array(
				$event['BannerURL'],
			),
			'categories' => array_merge(
				array(self::CATEGORIES[$category]),
				$event['GenreArray']
			),
			'uid' => $event['EventGroupCode']
		);
	}

	public function collectData(){
		$city = $this->getInput('city');
		$category = $this->getInput('category');

		$url = $this->makeUrl($category);
		$headers = $this->makeHeaders($city);

		$data = json_decode(getContents($url, $headers), true);

		if ($category == self::MOVIES) {
			$data = $data['moviesData']['BookMyShow']['arrEvents'];
		} else {
			$data = $data['data']['BookMyShow']['arrEvent'];
		}

		foreach ($data as $event) {
			$item = $this->generateEventData($event, $category);
			if ($this->matchesFilters($category)) {
				$this->items[] = $item;
			}
		}
	}

	/**
	 * Currently only checks if the language filter matches
	 */
	private function matchesFilters($category){
		if ($this->getInput('language') !== 'all') {
			$language = $this->getInput('language');
			return in_array($language, $this->languages);
		}
		return true;
	}

	/**
	 * Generates the RSS Feed title
	 */
	public function getName(){
		$city = $this->getInput('city');
		$category = $this->getInput('category');
		if(!is_null($city) and !is_null($category)) {
			$categoryName = self::CATEGORIES[$category];
			$cityNames = array_flip(self::CITIES);
			$cityName = $cityNames[$city];
			if ($this->getInput('language')!=='null') {
				$l = $this->getInput('language');
				// Sample: English Movies in Delhi
				return "BookMyShow: $language $categoryName in $cityName";
			}
			return "BookMyShow: $categoryName in $cityName";
		}

		return parent::getName();
	}

	/**
	 * Returns
	 * @param  string $city City Code
	 * @return array list of headers
	 */
	private function makeHeaders($city){
		$uniqid = uniqid();
		$rgn = urlencode("|Code=$city|");
		return array(
			"Cookie: bmsId=$uniqid; Rgn=$rgn;"
		);
	}

	/**
	 * Generates various URLs as per https://tools.ietf.org/html/rfc5870
	 * and other standards
	 */
	private function generateDirectionsHtml($lat, $long, $address = ''){
		$address = urlencode($address);

		$links = array(
			'Apple Maps' => 'http://maps.apple.com/maps?q=%s,%s"',
			'Google Maps' => 'http://maps.google.com/maps?ll=%s,%s',
			// 'Google Maps (Android)' => 'geo:%s,%s?q=%s',
			// 'Google Maps (iOS)' => 'comgooglemaps://?center=%s,%s&zoom=12&views=traffic',
			'OpenStreetMap' => 'https://www.openstreetmap.org/?mlat=%s&mlon=%s&zoom=12',
			'GeoURI' => 'geo:%s,%s?q=%s',
		);

		$html = '<h4>Directions</h4><ul>';

		foreach ($links as $app => $str) {
			$url = sprintf($str, $lat, $long, $address);
			$html .= "<li><a href='$url' title='$app'>$app</a></li>";
		}

		$html .= '</ul>';

		return $html;
	}
}
