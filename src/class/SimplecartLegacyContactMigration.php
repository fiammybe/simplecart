<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartEmailText.php';
require_once __DIR__ . '/SimplecartQuestionFormat.php';

/**
 * Turns the former fixed fields "phone" and "address" into normal questions and their stored values into answers
 *
 * Safe to run more than once: a question is found by its system key and an order that already has
 * an answer to it is skipped. The customer_phone and customer_address columns are dropped in a later release.
 */
final class SimplecartLegacyContactMigration
{
    public const SYSTEM_KEY_PHONE = 'legacy_phone';
    public const SYSTEM_KEY_ADDRESS = 'legacy_address';

    /** @var array<string, array{column: string, label: string, format: string, weight: int}> */
    private const FIELDS = [
        self::SYSTEM_KEY_PHONE => [
            'column' => 'customer_phone',
            'label' => 'LEGACY_PHONE',
            'format' => SimplecartQuestionFormat::TEXT,
            'weight' => -2,
        ],
        self::SYSTEM_KEY_ADDRESS => [
            'column' => 'customer_address',
            'label' => 'LEGACY_ADDRESS',
            'format' => SimplecartQuestionFormat::TEXTAREA,
            'weight' => -1,
        ],
    ];

    public function __construct(
        private object $questions,
        private object $answers,
        private object $orders
    ) {
    }

    /**
     * @return array{questions: int, answers: int}
     */
    public function run(): array
    {
        $created = 0;
        $converted = 0;

        foreach (self::FIELDS as $systemKey => $field) {
            $question = $this->questions->findBySystemKey($systemKey);

            if ($question === null) {
                $question = $this->createQuestion($systemKey, $field);
                $created++;
            }

            $converted += $this->convertOrders($question, $field['column']);
        }

        return [
            'questions' => $created,
            'answers' => $converted,
        ];
    }

    /**
     * @param array{column: string, label: string, format: string, weight: int} $field
     */
    private function createQuestion(string $systemKey, array $field): object
    {
        $question = $this->questions->create();
        $question->setVar('campaign_id', 0);
        $question->setVar('label', $this->legacyText($field['label']));
        $question->setVar('format', $field['format']);
        $question->setVar('options', '');
        $question->setVar('is_required', 0);
        $question->setVar('weight', $field['weight']);
        $question->setVar('active', 1);
        $question->setVar('system_key', $systemKey);

        if (! $this->questions->insert($question, true)) {
            throw new RuntimeException("Could not create the question that replaces {$field['column']}");
        }

        return $question;
    }

    private function convertOrders(object $question, string $column): int
    {
        $questionId = (int)$question->getVar('question_id', 'n');
        $format = (string)$question->getVar('format', 'n');
        $converted = 0;

        foreach ($this->orders->getObjects() as $order) {
            $value = trim((string)$order->getVar($column, 'n'));

            if ($value === '' || $this->hasAnswer($order, $questionId)) {
                continue;
            }

            $stored = $this->answers->store((int)$order->getVar('order_id', 'n'), [
                'question_id' => $questionId,
                'question_label' => (string)$question->getVar('label', 'n'),
                'question_format' => $format,
                'weight' => (int)$question->getVar('weight', 'n'),
                'answer_value' => $value,
            ]);

            if (! $stored) {
                throw new RuntimeException("Could not convert {$column} of order {$order->getVar('order_id', 'n')}");
            }

            $converted++;
        }

        return $converted;
    }

    private function hasAnswer(object $order, int $questionId): bool
    {
        foreach ($this->answers->getByOrder((int)$order->getVar('order_id', 'n')) as $answer) {
            if ((int)$answer->getVar('question_id', 'n') === $questionId) {
                return true;
            }
        }

        return false;
    }

    private function legacyText(string $key): string
    {
        return SimplecartLanguage::tagged(
            fn (string $language): string => SimplecartEmailText::forLanguage($language)->get($key)
        );
    }
}
