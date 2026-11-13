<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartQuestionFormat.php';

/**
 * A single value run through the cleaning and validation of an IPF object
 */
class SimplecartAnswerCheck extends icms_ipf_Object
{
    public function clean(int $type, ?int $maxLength, string $value): ?string
    {
        $this->initVar('answer', $type, '', false, $maxLength);
        $this->setVar('answer', $value);

        if (! $this->cleanVars()) {
            return null;
        }

        return (string)$this->cleanVars['answer'];
    }
}

final class SimplecartAnswerResult
{
    /**
     * @param array<int, array{question_id: int, question_label: string, question_format: string, weight: int, answer_value: string}> $answers
     * @param array<int, string> $errors question id => message
     * @param array<int, string> $values question id => what the customer filled in
     */
    public function __construct(
        public array $answers = [],
        public array $errors = [],
        public array $values = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}

/** Thrown when what a customer filled in for the questions is missing or not valid */
final class SimplecartAnswersException extends Exception
{
    public function __construct(private SimplecartAnswerResult $result)
    {
        parent::__construct(implode(' ', $result->errors));
    }

    public function getResult(): SimplecartAnswerResult
    {
        return $this->result;
    }
}

/**
 * Checks what a customer filled in against the questions that apply to the order
 *
 * IPF cleans and checks text, e-mail addresses and URLs. It does not refuse an empty required value,
 * a non-numeric number or an impossible date, so those checks are done here.
 */
final class SimplecartAnswerValidator
{
    public function __construct(private string $language = '')
    {
    }

    public static function fieldName(int $questionId): string
    {
        return "q_{$questionId}";
    }

    /**
     * @param array<int, SimplecartQuestion> $questions
     * @param array<string, mixed> $submitted
     */
    public function validate(array $questions, array $submitted): SimplecartAnswerResult
    {
        $result = new SimplecartAnswerResult();
        $position = 0;

        foreach ($questions as $question) {
            $position++;
            $questionId = (int)$question->getVar('question_id', 'n');
            $raw = $submitted[self::fieldName($questionId)] ?? '';
            $picked = $this->pickedValues($raw);
            $value = implode(',', $picked);
            $result->values[$questionId] = $value;

            if ($question->isChoice() && count($picked) > 1 && ! $question->isMultiple()) {
                $result->errors[$questionId] = sprintf(_MD_SIMPLECART_QUESTION_ERR_INVALID, $this->label($question));

                continue;
            }

            if ($value === '') {
                if ($question->isRequired()) {
                    $result->errors[$questionId] = sprintf(_MD_SIMPLECART_QUESTION_ERR_REQUIRED, $this->label($question));
                }

                continue;
            }

            $answerValue = $question->isChoice() ?
                $this->checkChoices($question, $picked) :
                $this->checkValue($question, $value);

            if ($answerValue === null) {
                $result->errors[$questionId] ??= sprintf(_MD_SIMPLECART_QUESTION_ERR_INVALID, $this->label($question));

                continue;
            }

            $result->answers[$questionId] = [
                'question_id' => $questionId,
                'question_label' => (string)$question->getVar('label', 'n'),
                'question_format' => $question->getFormat(),
                'weight' => $position,
                'answer_value' => $answerValue,
            ];
        }

        return $result;
    }

    private function label(SimplecartQuestion $question): string
    {
        return $question->getLabelFor($this->language);
    }

    /**
     * What was posted for one question as a list of trimmed, non-empty strings: a single value or the ticked checkboxes
     *
     * @param mixed $raw
     * @return array<int, string>
     */
    private function pickedValues($raw): array
    {
        $values = is_array($raw) ? $raw : [$raw];
        $picked = [];

        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $value = trim((string)$value);

            if ($value !== '') {
                $picked[] = $value;
            }
        }

        return $picked;
    }

    /**
     * The posted values are positions of options; the answer keeps the options' own text with their language tags,
     * once each and in the order of the option list, one per line
     *
     * @param array<int, string> $picked
     */
    private function checkChoices(SimplecartQuestion $question, array $picked): ?string
    {
        $options = $question->getOptions();
        $positions = [];

        foreach ($picked as $value) {
            if (! ctype_digit($value) || ! isset($options[(int)$value - 1])) {
                return null;
            }

            $positions[(int)$value] = true;
        }

        ksort($positions);

        return implode("
", array_map(static fn (int $position): string => $options[$position - 1], array_keys($positions)));
    }

    private function checkValue(SimplecartQuestion $question, string $value): ?string
    {
        $format = $question->getFormat();

        if (mb_strlen($value) > SimplecartQuestionFormat::maxLength($format)) {
            return null;
        }

        return match ($format) {
            SimplecartQuestionFormat::INTEGER => $this->checkInteger($question, $value),
            SimplecartQuestionFormat::NUMBER => $this->checkNumber($question, $value),
            SimplecartQuestionFormat::DATE => $this->checkDate($value),
            SimplecartQuestionFormat::URL => $this->checkUrl($question, $value),
            default => $this->clean($question, $value),
        };
    }

    private function checkInteger(SimplecartQuestion $question, string $value): ?string
    {
        if (! preg_match('/^-?\d{1,18}$/', $value)) {
            return null;
        }

        return $this->clean($question, $value) === null ? null : (string)(int)$value;
    }

    private function checkNumber(SimplecartQuestion $question, string $value): ?string
    {
        $normalized = str_replace(',', '.', $value);

        if (! preg_match('/^-?\d{1,18}(\.\d{1,10})?$/', $normalized)) {
            return null;
        }

        return $this->clean($question, $normalized) === null ? null : $normalized;
    }

    private function checkDate(string $value): ?string
    {
        foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y'] as $pattern) {
            $date = DateTimeImmutable::createFromFormat($pattern, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function checkUrl(SimplecartQuestion $question, string $value): ?string
    {
        $cleaned = $this->clean($question, $value);

        if ($cleaned === null || ! filter_var($cleaned, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $cleaned;
    }

    private function clean(SimplecartQuestion $question, string $value): ?string
    {
        $format = $question->getFormat();
        $check = new SimplecartAnswerCheck($question->handler);

        return $check->clean(
            SimplecartQuestionFormat::ipfType($format),
            SimplecartQuestionFormat::maxLength($format),
            $value
        );
    }
}
