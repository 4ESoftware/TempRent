from copy import deepcopyreferinta = 'referinta'articulat = 'articulat'simplu = 'simplu'gerunziu = 'gerunziu'tip_imobil = '<TIP_IMOBIL>'sumarizare = '<SUMARIZARE>'namebot = '<NAMEBOT>'dct_tip_imobil = {  'garsoniera' : {articulat: 'garsoniera',                  referinta : 'aceasta garsoniera',                  simplu : 'garsoniera',                  gerunziu : 'garsonierei'},    'apartament' : {articulat: 'apartamentul',                  referinta: 'acest apartament',                  simplu : 'apartament',                  gerunziu : 'apartamentului'},    'spatiubirou' : {articulat: 'spatiul de birouri',                   referinta: 'acest spatiu de birouri',                   simplu : 'spatiu de birouri',                   gerunziu : 'spatiului de birouri'}}HELLO = {  'expected_label_after' : ['spatiugeneric', 'garsoniera', 'apartament', 'spatiubirou']}SPATIU = {  'constraint_label_before': 'spatiugeneric',  'expected_label_after'   : ['garsoniera', 'apartament', 'spatiubirou']}PERIOADA = {  'expected_label_after' : ['1_3luni', '3_6luni', '6_12luni']}ORAS = {  'expected_label_after' : ['bucuresti', 'cluj', 'constanta', 'brasov', 'sibiu',                            'iasi', 'timisoara']}BUCURESTI = {  'constraint_label_before' : 'bucuresti',    'expected_label_after' : ['sec1', 'sec2', 'sec3', 'sec4', 'sec5', 'sec6',                            'oricare_sector']}BUGET = {  'expected_label_after' : ['300_1000euro', '1000_2500euro', '2500_5000euro',                            '5000_10000euro']}SUPRAFATA = {  'expected_label_after' : ['20_50mp', '55_150mp', '155_300mp', 'peste_300mp']}NR_CAMERE = {   'expected_label_after' : ['1camera', '2camere', '3camere', '4+camere']}NR_PERSOANE = {  'expected_label_after' : ['1_10persoane', '11_20persoane', '21_30persoane']}BLOC = {  'expected_label_after' : ['blocnou', 'blocvechi']}SERVICII = {  'expected_label_after' : ['nu',                            'cateringlacerere', 'serviciiIT', 'curatenie',                             'secretara', 'contabil', 'servicii_depozitare',                            'alarmasecuritate', 'securitate_noaptea',                            'inchiriere_copycenter', 'inchiriere_calculatoare',                            'servicii_internet', 'inchiriere_espressor',                            ]}MORE_SERVICII = {  'expected_label_after' : ['nu',                            'cateringlacerere', 'serviciiIT', 'curatenie',                             'secretara', 'contabil', 'servicii_depozitare',                            'alarmasecuritate', 'securitate_noaptea',                            'inchiriere_copycenter', 'inchiriere_calculatoare',                            'servicii_internet', 'inchiriere_espressor',                            ]}SPECIFICATII = {  'expected_label_after' : ['nu',                             'accestransportincomun', 'accesparc', 'parcare',                            'lift_sau_parter']}MORE_SPECIFICATII = {  'expected_label_after' : ['nu',                             'accestransportincomun', 'accesparc', 'parcare',                            'lift_sau_parter']}SECRETARIAT = {  'constraint_label_before' : 'secretara',  'expected_label_after' : ['secretarafemeie', 'secretarabarbat']}PERIOADA_CURATENIE = {  'constraint_label_before' : 'curatenie',  'expected_label_after' : ['curateniezilnica', 'curateniesaptamanala']  }SUMARIZARE = {}buckets = {  'hello'     : HELLO,  'spatiu'    : SPATIU,  'perioada'  : PERIOADA,  'oras'      : ORAS,  'bucuresti' : BUCURESTI,  'buget'     : BUGET,  'suprafata' : SUPRAFATA,  'nr_camere' : NR_CAMERE,  'nr_persoane'   : NR_PERSOANE,  'bloc'          : BLOC,  'servicii'      : SERVICII,  'more_servicii' : MORE_SERVICII,  'specificatii'  : SPECIFICATII,  'more_specificatii'  : MORE_SPECIFICATII,  'secretariat'        : SECRETARIAT,  'perioada_curatenie' : PERIOADA_CURATENIE,  'sumarizare' : SUMARIZARE}loop_buckets = {'servicii'     : 'more_servicii',                'more_servicii': 'more_servicii',                'secretariat'  : 'more_servicii',                'perioada_curatenie' : 'more_servicii',                                'specificatii'     : 'more_specificatii',                'more_specificatii': 'more_specificatii'}locuinte = []spatii = ['spatiubirou', 'garsoniera', 'apartament']dct_conversation_layers_spatii = {  'hello' : 0,    'oras' : 1,  'perioada': 2, 'buget': 2, 'suprafata': 2, 'nr_camere': 2, 'nr_persoane' : 2,  'bloc': 3,  'servicii': 4,    'specificatii': 6 }insert_summarization_before = ['specificatii']conversation_layers_spatii = [[] for _ in range(1+max(dct_conversation_layers_spatii.values()))]for k,v in dct_conversation_layers_spatii.items():  conversation_layers_spatii[v].append(k)def create_buckets(log, fn_buckets):  r_buckets = log.load_data_json(fn_buckets)  _buckets = deepcopy(buckets)  for k,v in buckets.items():    _buckets[k]['replica'] = r_buckets[k]    return _buckets