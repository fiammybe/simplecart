<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * Language of a customer and texts with language tags such as [en]Yes[/en][nl]Ja[/nl]
 *
 * A language is the name of a folder in language/ (english, nederlands). The tags come from the
 * system settings ml_tags and ml_names, which are matched by position.
 */
final class SimplecartLanguage
{
    private const FALLBACK_TAGS = [
        'english' => 'en',
        'nederlands' => 'nl',
        'dutch' => 'nl',
        'french' => 'fr',
        'francais' => 'fr',
        'german' => 'de',
        'deutsch' => 'de',
        'spanish' => 'es',
    ];

    /** @return array<int, string> */
    public static function available(): array
    {
        $languages = [];

        foreach (glob(SIMPLECART_ROOT_PATH . 'language/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $languages[] = basename($directory);
        }

        sort($languages);

        return $languages;
    }

    public static function current(): string
    {
        return (string)($GLOBALS['icmsConfig']['language'] ?? 'english');
    }

    /** First configured language when multilanguage is on, otherwise the language of the site */
    public static function siteDefault(): string
    {
        $names = self::configuredNames();

        if (self::multilanguageEnabled() && $names !== []) {
            return $names[0];
        }

        return self::current();
    }

    /**
     * A language the module has texts for: the given one, or the active one when none is given
     */
    public static function normalize(string $language = ''): string
    {
        $available = self::available();
        $candidate = trim($language) !== '' ? trim($language) : self::current();

        if (in_array($candidate, $available, true)) {
            return $candidate;
        }

        $default = self::siteDefault();

        if (in_array($default, $available, true)) {
            return $default;
        }

        return in_array('english', $available, true) ? 'english' : (string)($available[0] ?? 'english');
    }

    public static function tagFor(string $language): string
    {
        $language = strtolower(trim($language));
        $names = array_map('strtolower', self::configuredNames());
        $tags = self::configuredTags();
        $position = array_search($language, $names, true);

        if ($position !== false && isset($tags[$position])) {
            return $tags[$position];
        }

        return self::FALLBACK_TAGS[$language] ?? '';
    }

    /**
     * Keeps the block of the language and drops the others; a language without a block gets the first block
     */
    public static function resolve(string $text, string $language = ''): string
    {
        if (strpos($text, '[') === false) {
            return $text;
        }

        $tags = array_map(fn (string $tag): string => preg_quote($tag, '/'), self::knownTags());
        $pattern = '/\[(' . implode('|', $tags) . ')\](.*?)\[\/\1\]/s';

        if (! preg_match_all($pattern, $text, $blocks, PREG_SET_ORDER)) {
            return $text;
        }

        $wanted = self::tagFor($language !== '' ? $language : self::current());
        $present = array_column($blocks, 1);
        $chosen = in_array($wanted, $present, true) ? $wanted : $present[0];

        return (string)preg_replace_callback(
            $pattern,
            fn (array $block): string => $block[1] === $chosen ? $block[2] : '',
            $text
        );
    }

    /**
     * Builds a text with a language tag block per language, or the plain text of the active
     * language when multilanguage is off
     *
     * @param callable(string): string $textFor gets the language and returns the text in it
     */
    public static function tagged(callable $textFor): string
    {
        $blocks = '';

        if (self::multilanguageEnabled()) {
            foreach (self::available() as $language) {
                $tag = self::tagFor($language);

                if ($tag === '') {
                    continue;
                }

                $blocks .= "[{$tag}]" . $textFor($language) . "[/{$tag}]";
            }
        }

        return $blocks !== '' ? $blocks : $textFor(self::normalize(''));
    }

    /**
     * Escapes free text for an HTML page. The multilanguage filter of the system rewrites the whole
     * page, so a customer's [en]..[/en] has to be kept away from it.
     */
    public static function escapeHtml(string $text): string
    {
        return str_replace('[', '&#91;', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    /** @return array<int, string> */
    private static function knownTags(): array
    {
        return array_values(array_unique(array_merge(self::configuredTags(), array_values(self::FALLBACK_TAGS))));
    }

    private static function multilanguageEnabled(): bool
    {
        return ! empty($GLOBALS['icmsConfigMultilang']['ml_enable']);
    }

    /** @return array<int, string> */
    private static function configuredTags(): array
    {
        return self::splitSetting('ml_tags');
    }

    /** @return array<int, string> */
    private static function configuredNames(): array
    {
        return self::splitSetting('ml_names');
    }

    /** @return array<int, string> */
    private static function splitSetting(string $key): array
    {
        $value = (string)($GLOBALS['icmsConfigMultilang'][$key] ?? '');

        return array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $part): bool => $part !== ''));
    }
}
