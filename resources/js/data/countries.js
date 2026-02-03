/**
 * Lista paesi per CardSwap Shipping V1 – selezione paesi nelle tabelle prezzi.
 * Stessa struttura usata in ShippingZoneSelectorAdvanced (EU, AS, AM, AF, OC).
 */
const countriesByContinent = {
  EU: [
    { code: 'IT', name: 'Italia' }, { code: 'FR', name: 'Francia' }, { code: 'DE', name: 'Germania' },
    { code: 'ES', name: 'Spagna' }, { code: 'GB', name: 'Regno Unito' }, { code: 'NL', name: 'Paesi Bassi' },
    { code: 'BE', name: 'Belgio' }, { code: 'AT', name: 'Austria' }, { code: 'CH', name: 'Svizzera' },
    { code: 'SE', name: 'Svezia' }, { code: 'NO', name: 'Norvegia' }, { code: 'DK', name: 'Danimarca' },
    { code: 'FI', name: 'Finlandia' }, { code: 'PL', name: 'Polonia' }, { code: 'CZ', name: 'Repubblica Ceca' },
    { code: 'HU', name: 'Ungheria' }, { code: 'PT', name: 'Portogallo' }, { code: 'GR', name: 'Grecia' },
    { code: 'RO', name: 'Romania' }, { code: 'BG', name: 'Bulgaria' }, { code: 'HR', name: 'Croazia' },
    { code: 'SI', name: 'Slovenia' }, { code: 'SK', name: 'Slovacchia' }, { code: 'LT', name: 'Lituania' },
    { code: 'LV', name: 'Lettonia' }, { code: 'EE', name: 'Estonia' }, { code: 'IE', name: 'Irlanda' },
    { code: 'LU', name: 'Lussemburgo' }, { code: 'MT', name: 'Malta' }, { code: 'CY', name: 'Cipro' }
  ],
  AS: [
    { code: 'CN', name: 'Cina' }, { code: 'JP', name: 'Giappone' }, { code: 'KR', name: 'Corea del Sud' },
    { code: 'IN', name: 'India' }, { code: 'ID', name: 'Indonesia' }, { code: 'TH', name: 'Thailandia' },
    { code: 'VN', name: 'Vietnam' }, { code: 'MY', name: 'Malesia' }, { code: 'SG', name: 'Singapore' },
    { code: 'PH', name: 'Filippine' }, { code: 'TW', name: 'Taiwan' }, { code: 'HK', name: 'Hong Kong' },
    { code: 'MN', name: 'Mongolia' }, { code: 'KZ', name: 'Kazakistan' }, { code: 'UZ', name: 'Uzbekistan' },
    { code: 'KG', name: 'Kirghizistan' }, { code: 'TJ', name: 'Tagikistan' }, { code: 'TM', name: 'Turkmenistan' },
    { code: 'AF', name: 'Afghanistan' }, { code: 'PK', name: 'Pakistan' }, { code: 'BD', name: 'Bangladesh' },
    { code: 'LK', name: 'Sri Lanka' }, { code: 'NP', name: 'Nepal' }, { code: 'BT', name: 'Bhutan' },
    { code: 'MV', name: 'Maldive' }, { code: 'MM', name: 'Myanmar' }, { code: 'LA', name: 'Laos' },
    { code: 'KH', name: 'Cambogia' }, { code: 'BN', name: 'Brunei' }, { code: 'TL', name: 'Timor Est' }
  ],
  AM: [
    { code: 'US', name: 'Stati Uniti' }, { code: 'CA', name: 'Canada' }, { code: 'MX', name: 'Messico' },
    { code: 'BR', name: 'Brasile' }, { code: 'AR', name: 'Argentina' }, { code: 'CL', name: 'Cile' },
    { code: 'CO', name: 'Colombia' }, { code: 'PE', name: 'Perù' }, { code: 'VE', name: 'Venezuela' },
    { code: 'EC', name: 'Ecuador' }, { code: 'BO', name: 'Bolivia' }, { code: 'PY', name: 'Paraguay' },
    { code: 'UY', name: 'Uruguay' }, { code: 'GY', name: 'Guyana' }, { code: 'SR', name: 'Suriname' },
    { code: 'GF', name: 'Guyana Francese' }, { code: 'CR', name: 'Costa Rica' }, { code: 'PA', name: 'Panama' },
    { code: 'GT', name: 'Guatemala' }, { code: 'HN', name: 'Honduras' }, { code: 'SV', name: 'El Salvador' },
    { code: 'NI', name: 'Nicaragua' }, { code: 'CU', name: 'Cuba' }, { code: 'DO', name: 'Repubblica Dominicana' },
    { code: 'HT', name: 'Haiti' }, { code: 'JM', name: 'Giamaica' }, { code: 'TT', name: 'Trinidad e Tobago' },
    { code: 'BB', name: 'Barbados' }, { code: 'BS', name: 'Bahamas' }, { code: 'BZ', name: 'Belize' }
  ],
  AF: [
    { code: 'ZA', name: 'Sudafrica' }, { code: 'EG', name: 'Egitto' }, { code: 'NG', name: 'Nigeria' },
    { code: 'KE', name: 'Kenya' }, { code: 'MA', name: 'Marocco' }, { code: 'TN', name: 'Tunisia' },
    { code: 'DZ', name: 'Algeria' }, { code: 'LY', name: 'Libia' }, { code: 'ET', name: 'Etiopia' },
    { code: 'GH', name: 'Ghana' }, { code: 'CI', name: "Costa d'Avorio" }, { code: 'SN', name: 'Senegal' },
    { code: 'ML', name: 'Mali' }, { code: 'BF', name: 'Burkina Faso' }, { code: 'NE', name: 'Niger' },
    { code: 'TD', name: 'Ciad' }, { code: 'SD', name: 'Sudan' }, { code: 'UG', name: 'Uganda' },
    { code: 'TZ', name: 'Tanzania' }, { code: 'ZW', name: 'Zimbabwe' }, { code: 'ZM', name: 'Zambia' },
    { code: 'BW', name: 'Botswana' }, { code: 'NA', name: 'Namibia' }, { code: 'AO', name: 'Angola' },
    { code: 'MZ', name: 'Mozambico' }, { code: 'MG', name: 'Madagascar' }, { code: 'MU', name: 'Mauritius' },
    { code: 'SC', name: 'Seychelles' }, { code: 'RW', name: 'Ruanda' }, { code: 'BI', name: 'Burundi' }
  ],
  OC: [
    { code: 'AU', name: 'Australia' }, { code: 'NZ', name: 'Nuova Zelanda' }, { code: 'FJ', name: 'Fiji' },
    { code: 'PG', name: 'Papua Nuova Guinea' }, { code: 'NC', name: 'Nuova Caledonia' }, { code: 'VU', name: 'Vanuatu' },
    { code: 'SB', name: 'Isole Salomone' }, { code: 'TO', name: 'Tonga' }, { code: 'WS', name: 'Samoa' },
    { code: 'KI', name: 'Kiribati' }, { code: 'TV', name: 'Tuvalu' }, { code: 'NR', name: 'Nauru' },
    { code: 'PW', name: 'Palau' }, { code: 'FM', name: 'Micronesia' }, { code: 'MH', name: 'Isole Marshall' }
  ]
}

const continentLabels = { EU: 'Europa', AS: 'Asia', AM: 'America', AF: 'Africa', OC: 'Oceania' }

/** Lista piatta di tutti i paesi { code, name } */
export function allCountriesFlat () {
  return Object.values(countriesByContinent).flat()
}

export { countriesByContinent, continentLabels }
