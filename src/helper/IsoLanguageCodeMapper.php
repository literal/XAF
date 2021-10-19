<?php
namespace XAF\helper;

/**
 * Provides mapping between ISO 639‑3 and ISO 639‑2 language codes
 */
class IsoLanguageCodeMapper
{
	static private $alpha3ToAlpha2Map = [
		'aar' => 'aa', 'abk' => 'ab', 'afr' => 'af', 'aka' => 'ak',
		'alb' => 'sq', 'amh' => 'am', 'ara' => 'ar', 'arg' => 'an',
		'arm' => 'hy', 'asm' => 'as', 'ava' => 'av', 'ave' => 'ae',
		'aym' => 'ay', 'aze' => 'az', 'bak' => 'ba', 'bam' => 'bm',
		'baq' => 'eu', 'bel' => 'be', 'ben' => 'bn', 'bih' => 'bh',
		'bis' => 'bi', 'bod' => 'bo', 'bos' => 'bs', 'bre' => 'br',
		'bul' => 'bg', 'bur' => 'my', 'cat' => 'ca', 'cha' => 'ch',
        'che' => 'ce', 'chi' => 'zh', 'chu' => 'cu', 'chv' => 'cv',
        'cor' => 'kw', 'cos' => 'co', 'cre' => 'cr', 'cym' => 'cy',
        'cze' => 'cs', 'dan' => 'da', 'deu' => 'de', 'div' => 'dv',
        'dut' => 'nl', 'dzo' => 'dz', 'ell' => 'el', 'eng' => 'en',
        'epo' => 'eo', 'est' => 'et', 'eus' => 'eu', 'ewe' => 'ee',
        'fao' => 'fo', 'fas' => 'fa', 'fij' => 'fj', 'fin' => 'fi',
        'fra' => 'fr', 'fre' => 'fr', 'fry' => 'fy', 'ful' => 'ff',
        'geo' => 'ka', 'ger' => 'de', 'gla' => 'gd', 'gle' => 'ga',
        'glg' => 'gl', 'glv' => 'gv', 'gre' => 'el', 'grn' => 'gn',
        'guj' => 'gu', 'hat' => 'ht', 'hau' => 'ha', 'heb' => 'he',
        'her' => 'hz', 'hin' => 'hi', 'hmo' => 'ho', 'hrv' => 'hr',
        'hun' => 'hu', 'hye' => 'hy', 'ibo' => 'ig', 'ice' => 'is',
        'ido' => 'io', 'iii' => 'ii', 'iku' => 'iu', 'ile' => 'ie',
        'ina' => 'ia', 'ind' => 'id', 'ipk' => 'ik', 'isl' => 'is',
        'ita' => 'it', 'jav' => 'jv', 'jpn' => 'ja', 'kal' => 'kl',
        'kan' => 'kn', 'kas' => 'ks', 'kat' => 'ka', 'kau' => 'kr',
        'kaz' => 'kk', 'khm' => 'km', 'kik' => 'ki', 'kin' => 'rw',
        'kir' => 'ky', 'kom' => 'kv', 'kon' => 'kg', 'kor' => 'ko',
        'kua' => 'kj', 'kur' => 'ku', 'lao' => 'lo', 'lat' => 'la',
        'lav' => 'lv', 'lim' => 'li', 'lin' => 'ln', 'lit' => 'lt',
        'ltz' => 'lb', 'lub' => 'lu', 'lug' => 'lg', 'mac' => 'mk',
        'mah' => 'mh', 'mal' => 'ml', 'mao' => 'mi', 'mar' => 'mr',
        'may' => 'ms', 'mkd' => 'mk', 'mlg' => 'mg', 'mlt' => 'mt',
        'mon' => 'mn', 'mri' => 'mi', 'msa' => 'ms', 'mya' => 'my',
        'nau' => 'na', 'nav' => 'nv', 'nbl' => 'nr', 'nde' => 'nd',
        'ndo' => 'ng', 'nep' => 'ne', 'nld' => 'nl', 'nno' => 'nn',
        'nob' => 'nb', 'nor' => 'no', 'nya' => 'ny', 'oci' => 'oc',
        'oji' => 'oj', 'ori' => 'or', 'orm' => 'om', 'oss' => 'os',
        'pan' => 'pa', 'per' => 'fa', 'pli' => 'pi', 'pol' => 'pl',
        'por' => 'pt', 'pus' => 'ps', 'que' => 'qu', 'roh' => 'rm',
        'ron' => 'ro', 'rum' => 'ro', 'run' => 'rn', 'rus' => 'ru',
        'sag' => 'sg', 'san' => 'sa', 'sin' => 'si', 'slk' => 'sk',
        'slo' => 'sk', 'slv' => 'sl', 'sme' => 'se', 'smo' => 'sm',
        'sna' => 'sn', 'snd' => 'sd', 'som' => 'so', 'sot' => 'st',
        'spa' => 'es', 'sqi' => 'sq', 'srd' => 'sc', 'srp' => 'sr',
        'ssw' => 'ss', 'sun' => 'su', 'swa' => 'sw', 'swe' => 'sv',
        'tah' => 'ty', 'tam' => 'ta', 'tat' => 'tt', 'tel' => 'te',
        'tgk' => 'tg', 'tgl' => 'tl', 'tha' => 'th', 'tib' => 'bo',
        'tir' => 'ti', 'ton' => 'to', 'tsn' => 'tn', 'tso' => 'ts',
        'tuk' => 'tk', 'tur' => 'tr', 'twi' => 'tw', 'uig' => 'ug',
        'ukr' => 'uk', 'urd' => 'ur', 'uzb' => 'uz', 'ven' => 've',
        'vie' => 'vi', 'vol' => 'vo', 'wel' => 'cy', 'wln' => 'wa',
        'wol' => 'wo', 'xho' => 'xh', 'yid' => 'yi', 'yor' => 'yo',
        'zha' => 'za', 'zho' => 'zh', 'zul' => 'zu',
    ];

	/**
	 * @param string $alpha3Code
	 * @return string|null
	 */
	static public function alpha3ToAlpha2( $alpha3Code )
	{
		$alpha3Code = \strtolower($alpha3Code);
		return self::$alpha3ToAlpha2Map[$alpha3Code] ?? null;
	}

	/**
	 * @param string $alpha2Code
	 * @return string|null
	 */
	static public function alpha2ToAlpha3( $alpha2Code )
	{
		$alpha2Code = \strtolower($alpha2Code);
		$result = \array_search($alpha2Code, self::$alpha3ToAlpha2Map);
		return $result !== false ? $result : null;
	}
}