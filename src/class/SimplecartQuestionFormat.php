<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

/**
 * The formats an answer can have and the IPF data type each one is validated with
 */
final class SimplecartQuestionFormat
{
    public const TEXT = 'text';
    public const TEXTAREA = 'textarea';
    public const EMAIL = 'email';
    public const URL = 'url';
    public const INTEGER = 'integer';
    public const NUMBER = 'number';
    public const DATE = 'date';
    public const RADIO = 'radio';
    public const SELECT = 'select';

    public const MAX_LENGTH_LINE = 255;
    public const MAX_LENGTH_TEXT = 5000;

    /** @return array<string, string> format => label */
    public static function labels(): array
    {
        return [
            self::TEXT => _AM_SIMPLECART_FORMAT_TEXT,
            self::TEXTAREA => _AM_SIMPLECART_FORMAT_TEXTAREA,
            self::EMAIL => _AM_SIMPLECART_FORMAT_EMAIL,
            self::URL => _AM_SIMPLECART_FORMAT_URL,
            self::INTEGER => _AM_SIMPLECART_FORMAT_INTEGER,
            self::NUMBER => _AM_SIMPLECART_FORMAT_NUMBER,
            self::DATE => _AM_SIMPLECART_FORMAT_DATE,
            self::RADIO => _AM_SIMPLECART_FORMAT_RADIO,
            self::SELECT => _AM_SIMPLECART_FORMAT_SELECT,
        ];
    }

    public static function isValid(string $format): bool
    {
        return array_key_exists($format, self::labels());
    }

    public static function isChoice(string $format): bool
    {
        return $format === self::RADIO || $format === self::SELECT;
    }

    /**
     * A text area is checked as plain text: XOBJ_DTYPE_TXTAREA purifies HTML and appends
     * "<!-- filtered with htmlpurifier -->" comments to the value, which has no place in a plain answer
     */
    public static function ipfType(string $format): int
    {
        return match ($format) {
            self::EMAIL => XOBJ_DTYPE_EMAIL,
            self::URL => XOBJ_DTYPE_URL,
            self::INTEGER => XOBJ_DTYPE_INT,
            self::NUMBER => XOBJ_DTYPE_FLOAT,
            default => XOBJ_DTYPE_TXTBOX,
        };
    }

    /** How the form shows the question: a text area, radio buttons, a selection list or a single input */
    public static function formKind(string $format): string
    {
        return match ($format) {
            self::TEXTAREA => 'textarea',
            self::RADIO => 'radio',
            self::SELECT => 'select',
            default => 'input',
        };
    }

    public static function inputType(string $format): string
    {
        return match ($format) {
            self::EMAIL => 'email',
            self::URL => 'url',
            self::INTEGER => 'number',
            self::DATE => 'date',
            default => 'text',
        };
    }

    public static function maxLength(string $format): int
    {
        return $format === self::TEXTAREA ? self::MAX_LENGTH_TEXT : self::MAX_LENGTH_LINE;
    }
}
