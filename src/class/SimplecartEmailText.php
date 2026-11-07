<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';

/**
 * Texts of the e-mails in one language, from language/<language>/email.php
 *
 * The define() constants of the module only exist in the language of the current request, so an
 * e-mail for a customer who ordered in another language cannot be built from them.
 */
final class SimplecartEmailText
{
    /** @var array<string, self> */
    private static array $instances = [];

    /** @param array<int, array<string, string>> $catalogs the language first, then the fallbacks */
    private function __construct(private array $catalogs)
    {
    }

    public static function forLanguage(string $language): self
    {
        $language = SimplecartLanguage::normalize($language);

        if (! isset(self::$instances[$language])) {
            $chain = array_unique([$language, SimplecartLanguage::siteDefault(), 'english']);
            self::$instances[$language] = new self(array_map(fn (string $name): array => self::load($name), $chain));
        }

        return self::$instances[$language];
    }

    public static function reset(): void
    {
        self::$instances = [];
    }

    public function get(string $key): string
    {
        foreach ($this->catalogs as $catalog) {
            if (isset($catalog[$key])) {
                return $catalog[$key];
            }
        }

        return $key;
    }

    /** @return array<string, string> */
    private static function load(string $language): array
    {
        if (! preg_match('/^[a-z0-9_-]+$/i', $language)) {
            return [];
        }

        $file = SIMPLECART_ROOT_PATH . "language/{$language}/email.php";

        if (! is_file($file)) {
            return [];
        }

        $catalog = include $file;

        return is_array($catalog) ? $catalog : [];
    }
}
