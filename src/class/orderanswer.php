<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartQuestionFormat.php';

/**
 * An answer to a question, with the label and format of the question as they were when the order was placed
 */
class SimplecartOrderanswer extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);

        $this->initVar('answer_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('order_id', XOBJ_DTYPE_INT, 0, true);
        $this->initVar('question_id', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('question_label', XOBJ_DTYPE_SOURCE, '', false);
        $this->initVar('question_format', XOBJ_DTYPE_TXTBOX, SimplecartQuestionFormat::TEXT, false, 32);
        $this->initVar('weight', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('answer_value', XOBJ_DTYPE_SOURCE, '', false);

        $this->hideFieldFromForm('answer_id');
    }

    public function isChoice(): bool
    {
        return SimplecartQuestionFormat::isChoice((string)$this->getVar('question_format', 'n'));
    }

    public function getLabelFor(string $language = ''): string
    {
        return SimplecartLanguage::resolve((string)$this->getVar('question_label', 'n'), $language);
    }

    /** The label of a chosen option is written by an administrator and has language tags; free text never does */
    public function getAnswerFor(string $language = ''): string
    {
        $value = (string)$this->getVar('answer_value', 'n');

        if (! $this->isChoice()) {
            return $value;
        }

        // A multiple-choice answer holds one option per line
        $lines = array_map(
            static fn (string $line): string => SimplecartLanguage::resolve($line, $language),
            preg_split('/\R/', $value) ?: []
        );

        return implode("
", $lines);
    }

    /**
     * @return array{label: string, value: string}
     */
    public function toRow(string $language = ''): array
    {
        return [
            'label' => $this->getLabelFor($language),
            'value' => $this->getAnswerFor($language),
        ];
    }

    /**
     * @return array{label: string, value: string}
     */
    public function toHtmlRow(string $language = ''): array
    {
        $row = $this->toRow($language);

        return [
            'label' => SimplecartLanguage::escapeHtml($row['label']),
            'value' => nl2br(SimplecartLanguage::escapeHtml($row['value'])),
        ];
    }
}

class SimplecartOrderanswerHandler extends icms_ipf_Handler {
    public function __construct(&$db) {
        parent::__construct($db, 'orderanswer', 'answer_id', 'question_label', 'answer_value', 'simplecart');
    }

    /** @return array<int, SimplecartOrderanswer> */
    public function getByOrder(int $orderId): array
    {
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('order_id', $orderId));
        $criteria->setSort('weight');
        $criteria->setOrder('ASC');

        $answers = array_values($this->getObjects($criteria, false, true));
        usort($answers, fn (SimplecartOrderanswer $a, SimplecartOrderanswer $b): int => [(int)$a->getVar('weight', 'n'), (int)$a->getVar('answer_id', 'n')] <=> [(int)$b->getVar('weight', 'n'), (int)$b->getVar('answer_id', 'n')]);

        return $answers;
    }

    public function deleteByOrder(int $orderId): void
    {
        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('order_id', $orderId));
        $this->deleteAll($criteria);
    }

    /**
     * @param array{question_id: int, question_label: string, question_format: string, weight: int, answer_value: string} $answer
     */
    public function store(int $orderId, array $answer): bool
    {
        $object = $this->create();
        $object->setVar('order_id', $orderId);

        foreach (['question_id', 'question_label', 'question_format', 'weight', 'answer_value'] as $field) {
            $object->setVar($field, $answer[$field]);
        }

        return (bool)$this->insert($object, true);
    }
}
?>
