<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartEmailText.php';
require_once __DIR__ . '/SimplecartQuestionFormat.php';

/**
 * Turns the former fixed question "Helpende hand" into a normal question and its stored values into answers
 *
 * Safe to run more than once: the question is found by its system key and an order that already
 * has an answer to it is skipped. The helpende_hand column itself is dropped in a later release.
 */
final class SimplecartLegacyHelpMigration
{
    public const SYSTEM_KEY = 'legacy_helpende_hand';

    private const OPTION_COUNT = 4;

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
        $question = $this->questions->findBySystemKey(self::SYSTEM_KEY);

        if ($question === null) {
            $question = $this->createQuestion();
            $created = 1;
        }

        return [
            'questions' => $created,
            'answers' => $this->convertOrders($question),
        ];
    }

    private function createQuestion(): object
    {
        $options = [];

        for ($number = 1; $number <= self::OPTION_COUNT; $number++) {
            $options[] = $this->legacyText("LEGACY_HELP_{$number}");
        }

        $question = $this->questions->create();
        $question->setVar('campaign_id', 0);
        $question->setVar('label', $this->legacyText('LEGACY_HELP'));
        $question->setVar('format', SimplecartQuestionFormat::RADIO);
        $question->setVar('options', implode("\n", $options));
        $question->setVar('is_required', 1);
        $question->setVar('weight', 0);
        $question->setVar('active', 1);
        $question->setVar('system_key', self::SYSTEM_KEY);

        if (! $this->questions->insert($question, true)) {
            throw new RuntimeException('Could not create the question that replaces "Helpende hand"');
        }

        return $question;
    }

    private function convertOrders(object $question): int
    {
        $questionId = (int)$question->getVar('question_id', 'n');
        $options = $question->getOptions();
        $converted = 0;

        foreach ($this->orders->getObjects() as $order) {
            $value = trim((string)$order->getVar('helpende_hand', 'n'));

            if ($value === '' || $this->hasAnswer($order, $questionId)) {
                continue;
            }

            $stored = $this->answers->store((int)$order->getVar('order_id', 'n'), [
                'question_id' => $questionId,
                'question_label' => (string)$question->getVar('label', 'n'),
                'question_format' => SimplecartQuestionFormat::RADIO,
                'weight' => 0,
                'answer_value' => $this->optionFor($value, $options),
            ]);

            if (! $stored) {
                throw new RuntimeException("Could not convert the help preference of order {$order->getVar('order_id', 'n')}");
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

    /**
     * The stored value was help_1 to help_4; anything else is kept as it was
     *
     * @param array<int, string> $options
     */
    private function optionFor(string $value, array $options): string
    {
        if (preg_match('/[1-4]/', $value, $match) && isset($options[(int)$match[0] - 1])) {
            return $options[(int)$match[0] - 1];
        }

        return $value;
    }

    private function legacyText(string $key): string
    {
        return SimplecartLanguage::tagged(
            fn (string $language): string => SimplecartEmailText::forLanguage($language)->get($key)
        );
    }
}
