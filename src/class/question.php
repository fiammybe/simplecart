<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartQuestionFormat.php';
require_once __DIR__ . '/SimplecartAnswerValidator.php';

class SimplecartQuestion extends icms_ipf_Object {
    public function __construct(&$handler) {
        parent::__construct($handler);

        $this->initVar('question_id', XOBJ_DTYPE_INT, null, false);
        $this->initVar('campaign_id', XOBJ_DTYPE_INT, 0, false, null, '', false, _MI_SIMPLECART_QUESTION_CAMPAIGN);
        $this->initVar('label', XOBJ_DTYPE_SOURCE, '', true, null, '', false, _MI_SIMPLECART_QUESTION_LABEL, _MI_SIMPLECART_QUESTION_LABEL_DSC);
        $this->initVar('help', XOBJ_DTYPE_SOURCE, '', false, null, '', false, _MI_SIMPLECART_QUESTION_HELP, _MI_SIMPLECART_QUESTION_HELP_DSC);
        $this->initVar('format', XOBJ_DTYPE_TXTBOX, SimplecartQuestionFormat::TEXT, true, 32, '', false, _MI_SIMPLECART_QUESTION_FORMAT);
        $this->initVar('options', XOBJ_DTYPE_SOURCE, '', false, null, '', false, _MI_SIMPLECART_QUESTION_OPTIONS, _MI_SIMPLECART_QUESTION_OPTIONS_DSC);
        $this->initVar('is_required', XOBJ_DTYPE_INT, 0, false, null, '', false, _MI_SIMPLECART_QUESTION_REQUIRED);
        $this->initVar('weight', XOBJ_DTYPE_INT, 0, false, null, '', false, _MI_SIMPLECART_QUESTION_WEIGHT);
        $this->initVar('active', XOBJ_DTYPE_INT, 1, false, null, '', false, _MI_SIMPLECART_QUESTION_ACTIVE);
        $this->initVar('system_key', XOBJ_DTYPE_TXTBOX, '', false, 64);

        $this->setControl('campaign_id', array('name' => 'select', 'itemHandler' => 'question', 'method' => 'getCampaignOptions', 'module' => 'simplecart'));
        $this->setControl('label', 'textarea');
        $this->setControl('help', 'textarea');
        $this->setControl('options', 'textarea');
        $this->setControl('format', array('name' => 'select', 'itemHandler' => 'question', 'method' => 'getFormatArray', 'module' => 'simplecart'));
        $this->setControl('is_required', array('name' => 'select', 'itemHandler' => 'question', 'method' => 'getRequiredArray', 'module' => 'simplecart'));
        $this->setControl('active', 'yesno');
        $this->setControl('weight', 'text');

        $this->hideFieldFromForm('question_id');
        $this->hideFieldFromForm('system_key');
        $this->hideFieldFromSingleView('system_key');

        $this->handler->identifierName = 'label';
    }

    public function getCampaignId(): int
    {
        return (int)$this->getVar('campaign_id', 'n');
    }

    public function getFormat(): string
    {
        return (string)$this->getVar('format', 'n');
    }

    public function isChoice(): bool
    {
        return SimplecartQuestionFormat::isChoice($this->getFormat());
    }

    public function isMultiple(): bool
    {
        return SimplecartQuestionFormat::isMultiple($this->getFormat());
    }

    public function isRequired(): bool
    {
        return (int)$this->getVar('is_required', 'n') === 1;
    }

    public function isActive(): bool
    {
        return (int)$this->getVar('active', 'n') === 1;
    }

    public function getWeight(): int
    {
        return (int)$this->getVar('weight', 'n');
    }

    /** @return array<int, string> the options in order, with their language tags */
    public function getOptions(): array
    {
        $lines = preg_split('/\R/', (string)$this->getVar('options', 'n')) ?: [];

        return array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));
    }

    public function getLabelFor(string $language = ''): string
    {
        return SimplecartLanguage::resolve((string)$this->getVar('label', 'n'), $language);
    }

    public function getHelpFor(string $language = ''): string
    {
        return SimplecartLanguage::resolve((string)$this->getVar('help', 'n'), $language);
    }

    /** @return array<int, string> the options in a language, indexed from 1 */
    public function getOptionsFor(string $language = ''): array
    {
        $options = [];

        foreach ($this->getOptions() as $position => $option) {
            $options[$position + 1] = SimplecartLanguage::resolve($option, $language);
        }

        return $options;
    }

    public function getLabelPreview(): string
    {
        return htmlspecialchars($this->getLabelFor(), ENT_QUOTES, 'UTF-8');
    }

    public function getFormatLabel(): string
    {
        return SimplecartQuestionFormat::labels()[$this->getFormat()] ?? $this->getFormat();
    }

    public function getRequiredLabel(): string
    {
        return $this->isRequired() ? _AM_SIMPLECART_QUESTION_REQUIRED : _AM_SIMPLECART_QUESTION_SUPPLEMENTARY;
    }

    public function getCampaignName(): string
    {
        $campaignId = $this->getCampaignId();

        if ($campaignId <= 0) {
            return _AM_SIMPLECART_QUESTION_ALL_ORDERS;
        }

        $campaign = simplecart_getHandler('campaign')->get($campaignId);

        if (! $campaign || $campaign->isNew()) {
            return '-';
        }

        return htmlspecialchars((string)$campaign->getVar('name', 'n'), ENT_QUOTES, 'UTF-8');
    }
}

class SimplecartQuestionHandler extends icms_ipf_Handler {
    public function __construct(&$db) {
        parent::__construct($db, 'question', 'question_id', 'label', 'help', 'simplecart');
    }

    public function beforeInsert(&$obj) {
        $format = (string)$obj->getVar('format', 'n');

        if (! SimplecartQuestionFormat::isValid($format)) {
            $obj->setErrors(_AM_SIMPLECART_QUESTION_ERR_FORMAT);

            return false;
        }

        if (SimplecartQuestionFormat::isChoice($format) && count($obj->getOptions()) < 1) {
            $obj->setErrors(_AM_SIMPLECART_QUESTION_ERR_OPTIONS);

            return false;
        }

        $obj->setVar('campaign_id', max(0, (int)$obj->getVar('campaign_id', 'n')));
        $obj->setVar('is_required', (int)$obj->getVar('is_required', 'n') === 1 ? 1 : 0);
        $obj->setVar('active', (int)$obj->getVar('active', 'n') === 1 ? 1 : 0);
        $obj->setVar('weight', (int)$obj->getVar('weight', 'n'));

        return true;
    }

    public function beforeUpdate(&$obj) {
        return $this->beforeInsert($obj);
    }

    /** @return array<string, string> */
    public function getFormatArray(): array
    {
        return SimplecartQuestionFormat::labels();
    }

    /** @return array<int, string> */
    public function getRequiredArray(): array
    {
        return [1 => _AM_SIMPLECART_QUESTION_REQUIRED, 0 => _AM_SIMPLECART_QUESTION_SUPPLEMENTARY];
    }

    /** @return array<int, string> */
    public function getCampaignOptions(): array
    {
        $options = [0 => _AM_SIMPLECART_QUESTION_ALL_ORDERS];
        $campaigns = simplecart_getHandler('campaign')->getCampaignArray();
        unset($campaigns[0]);

        return $options + $campaigns;
    }

    /** @return array<int|string, string> */
    public function getCampaignFilter(): array
    {
        return ['default' => _AM_SIMPLECART_QUESTION_FILTER_ALL] + $this->getCampaignOptions();
    }

    /**
     * The active questions for a cart: the ones for every order plus those of each campaign in the cart
     *
     * @param array<int|string, array<string, mixed>> $cart
     * @return array<int, SimplecartQuestion>
     */
    public function getForCart(array $cart): array
    {
        $campaignIds = $this->getCampaignIds($cart);
        $questions = [];

        foreach ($this->getObjects(null, false, true) as $question) {
            if (! $question->isActive()) {
                continue;
            }

            $campaignId = $question->getCampaignId();

            if ($campaignId > 0 && ! in_array($campaignId, $campaignIds, true)) {
                continue;
            }

            $questions[] = $question;
        }

        usort($questions, function (SimplecartQuestion $a, SimplecartQuestion $b): int {
            return [$a->getCampaignId(), $a->getWeight(), (int)$a->getVar('question_id', 'n')]
                <=> [$b->getCampaignId(), $b->getWeight(), (int)$b->getVar('question_id', 'n')];
        });

        return $questions;
    }

    /**
     * What the checkout form needs to show the questions for a cart, escaped for an HTML page
     *
     * @param array<int|string, array<string, mixed>> $cart
     * @param array<int, string> $values what was filled in, by question id
     * @param array<int, string> $errors messages by question id
     * @return array<int, array{
     *     field: string,
     *     label: string,
     *     help: string,
     *     required: bool,
     *     kind: string,
     *     input_type: string,
     *     value: string,
     *     options: array<int, array{value: int, label: string, selected: bool}>,
     *     error: string
     * }>
     */
    public function getFormRows(array $cart, array $values = [], array $errors = [], string $language = ''): array
    {
        $rows = [];

        foreach ($this->getForCart($cart) as $question) {
            $questionId = (int)$question->getVar('question_id', 'n');
            $value = (string)($values[$questionId] ?? '');
            $picked = explode(',', $value);
            $options = [];

            foreach ($question->getOptionsFor($language) as $position => $label) {
                $options[] = [
                    'value' => $position,
                    'label' => SimplecartLanguage::escapeHtml($label),
                    'selected' => in_array((string)$position, $picked, true),
                ];
            }

            $rows[] = [
                'field' => SimplecartAnswerValidator::fieldName($questionId),
                'label' => SimplecartLanguage::escapeHtml($question->getLabelFor($language)),
                'help' => SimplecartLanguage::escapeHtml($question->getHelpFor($language)),
                'required' => $question->isRequired(),
                'kind' => SimplecartQuestionFormat::formKind($question->getFormat()),
                'input_type' => SimplecartQuestionFormat::inputType($question->getFormat()),
                'value' => SimplecartLanguage::escapeHtml($value),
                'options' => $options,
                'error' => SimplecartLanguage::escapeHtml((string)($errors[$questionId] ?? '')),
            ];
        }

        return $rows;
    }

    public function findBySystemKey(string $systemKey): ?SimplecartQuestion
    {
        foreach ($this->getObjects(null, false, true) as $question) {
            if ((string)$question->getVar('system_key', 'n') === $systemKey) {
                return $question;
            }
        }

        return null;
    }

    public function deleteByCampaign(int $campaignId): void
    {
        if ($campaignId <= 0) {
            return;
        }

        $criteria = new icms_db_criteria_Compo();
        $criteria->add(new icms_db_criteria_Item('campaign_id', $campaignId));
        $this->deleteAll($criteria);
    }

    /**
     * @param array<int|string, array<string, mixed>> $cart
     * @return array<int, int>
     */
    private function getCampaignIds(array $cart): array
    {
        $productHandler = simplecart_getHandler('product');
        $campaignIds = [];

        foreach ($cart as $key => $item) {
            $productId = (int)($item['product_id'] ?? $item['id'] ?? $key);
            $product = $productId > 0 ? $productHandler->get($productId) : null;

            if (! $product || $product->isNew()) {
                continue;
            }

            $campaignId = (int)$product->getVar('campaign_id', 'n');

            if ($campaignId > 0) {
                $campaignIds[$campaignId] = $campaignId;
            }
        }

        return array_values($campaignIds);
    }
}
?>
