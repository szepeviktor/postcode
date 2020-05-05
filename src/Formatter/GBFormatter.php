<?php

declare(strict_types=1);

namespace Brick\Postcode\Formatter;

use Brick\Postcode\CountryPostcodeFormatter;

/**
 * Validates and formats the postcodes in the United Kingdom of Great Britain and Northern Ireland.
 *
 * Postcodes can have six different formats:
 *
 * - A9 9AA
 * - A9A 9AA
 * - A99 9AA
 * - AA9 9AA
 * - AA9A 9AA
 * - AA99 9AA
 *
 * A stands for a capital letter, 9 stands for a digit.
 * Only certain letters are allowed for each position in the postcode.
 * Only certain area codes are permitted.
 *
 * @see https://en.wikipedia.org/wiki/List_of_postal_codes
 * @see https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
 * @see https://en.wikipedia.org/wiki/List_of_postcode_areas_in_the_United_Kingdom
 */
class GBFormatter implements CountryPostcodeFormatter
{
    /**
     * The list of valid area codes.
     */
    private const AREA_CODES = [
        'AB', 'AL', 'B', 'BA', 'BB', 'BD', 'BH', 'BL', 'BN', 'BR', 'BS', 'BT', 'CA', 'CB', 'CF', 'CH', 'CM', 'CO', 'CR',
        'CT', 'CV', 'CW', 'DA', 'DD', 'DE', 'DG', 'DH', 'DL', 'DN', 'DT', 'DY', 'E', 'EC', 'EH', 'EN', 'EX', 'FK', 'FY',
        'G', 'GL', 'GU', 'HA', 'HD', 'HG', 'HP', 'HR', 'HS', 'HU', 'HX', 'IG', 'IP', 'IV', 'KA', 'KT', 'KW', 'KY', 'L',
        'LA', 'LD', 'LE', 'LL', 'LN', 'LS', 'LU', 'M', 'ME', 'MK', 'ML', 'N', 'NE', 'NG', 'NN', 'NP', 'NR', 'NW', 'OL',
        'OX', 'PA', 'PE', 'PH', 'PL', 'PO', 'PR', 'RG', 'RH', 'RM', 'S', 'SA', 'SE', 'SG', 'SK', 'SL', 'SM', 'SN', 'SO',
        'SP', 'SR', 'SS', 'ST', 'SW', 'SY', 'TA', 'TD', 'TF', 'TN', 'TQ', 'TR', 'TS', 'TW', 'UB', 'W', 'WA', 'WC', 'WD',
        'WF', 'WN', 'WR', 'WS', 'WV', 'YO', 'ZE',
    ];

    /**
     * The regular expression patterns, or null if not built yet.
     *
     * @var string[]|null
     */
    private $patterns = null;

    /**
     * {@inheritdoc}
     */
    public function format(string $postcode) : ?string
    {
        // special case
        if ($postcode === 'GIR0AA') {
            return 'GIR 0AA';
        }

        // regular patterns
        $patterns = $this->getPatterns();

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $postcode, $matches) === 1) {
                [, $outwardCode, $areaCode, $inwardCode] = $matches;

                if (! in_array($areaCode, self::AREA_CODES, true)) {
                    return null;
                }

                return $outwardCode . ' ' . $inwardCode;
            }
        }

        return null;
    }

    /**
     * Builds the regular expression patterns to check the postcode.
     *
     * Each pattern contains 3 capturing groups:
     *
     * - The outward code (e.g. WC2E)
     * - The area code (ex: WC) for additional checks
     * - The inward code (e.g. 9RZ)
     *
     * @return string[]
     */
    private function getPatterns() : array
    {
        if ($this->patterns !== null) {
            return $this->patterns;
        }

        $n = '[0-9]';

        // outward code alpha chars
        $outAlpha1 = '[ABCDEFGHIJKLMNOPRSTUWYZ]';
        $outAlpha2 = '[ABCDEFGHKLMNOPQRSTUVWXY]';
        $outAlpha3 = '[ABCDEFGHJKPSTUW]';
        $outAlpha4 = '[ABEHMNPRVWXY]';

        // inward code alpha chars
        $inAlpha = '[ABDEFGHJLNPQRSTUWXYZ]';

        $outPatterns = [];

        // AN
        $outPatterns[] = '(' . $outAlpha1 . ')' . $n;

        // ANA
        $outPatterns[] = '(' . $outAlpha1 . ')' . $n . $outAlpha3;

        // ANN
        $outPatterns[] = '(' . $outAlpha1 . ')' . $n . $n;

        // AAN
        $outPatterns[] = '(' . $outAlpha1 . $outAlpha2 . ')' . $n;

        // AANA
        $outPatterns[] = '(' . $outAlpha1 . $outAlpha2 . ')' . $n . $outAlpha4;

        // AANN
        $outPatterns[] = '(' . $outAlpha1 . $outAlpha2 . ')' . $n . $n;

        $inPattern = $n . $inAlpha . $inAlpha;

        $patterns = [];

        foreach ($outPatterns as $outPattern) {
            $patterns[] = '/^(' . $outPattern . ')(' . $inPattern . ')$/';
        }

        return $this->patterns = $patterns;
    }
}
