<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartLanguage.php';
require_once __DIR__ . '/SimplecartQuestionFormat.php';

/**
 * Everything the admin dashboard needs to filter and chart the orders in the browser
 *
 * The data is read once (orders, items, answers) and joined here, so the number of queries does not
 * grow with the number of orders. It holds no personal data: no name, e-mail, phone or address, and
 * no free-text answer. Only the answers to choice questions are included, because only those can be
 * filtered on.
 */
final class SimplecartDashboardData
{
    public function __construct(
        private object $orders,
        private object $items,
        private object $answers,
        private object $questions,
        private object $campaigns,
        private object $products,
        private int $timezoneOffset = 0
    ) {
    }

    /**
     * @param array<string, string> $statuses status => label
     * @return array<string, mixed>
     */
    public function build(array $statuses, string $unknownLabel, string $language = ''): array
    {
        $itemsByOrder = $this->itemsByOrder();
        $choiceAnswersByOrder = $this->choiceAnswersByOrder($language);
        $orders = [];
        $usedProducts = [];
        $usedCampaigns = [];
        $usedQuestions = [];

        foreach ($this->orders->getObjects() as $order) {
            $orderId = (int)$order->getVar('order_id', 'n');
            $items = [];

            foreach ($itemsByOrder[$orderId] ?? [] as $item) {
                $productId = (int)$item->getVar('product_id', 'n');
                $campaignId = (int)$item->getVar('campaign_id', 'n');
                $name = (string)$item->getVar('product_name', 'n');

                $items[] = [
                    'p' => $productId,
                    'c' => $campaignId,
                    'n' => $name,
                    'q' => (int)$item->getVar('quantity', 'n'),
                    'a' => round((int)$item->getVar('quantity', 'n') * (float)$item->getVar('product_price', 'n'), 2),
                ];

                if ($productId > 0) {
                    $usedProducts[$productId] = $name;
                }

                $usedCampaigns[$campaignId] = true;
            }

            $answers = [];

            foreach ($choiceAnswersByOrder[$orderId] ?? [] as $questionId => $answer) {
                $answers[(string)$questionId] = $answer['values'];
                $usedQuestions[$questionId]['label'] ??= $answer['label'];

                foreach ($answer['values'] as $value) {
                    $usedQuestions[$questionId]['options'][$value] = true;
                }
            }

            $orders[] = [
                'id' => $orderId,
                'date' => gmdate('Y-m-d', (int)$order->getVar('timestamp', 'n') + $this->timezoneOffset),
                'status' => (string)$order->getVar('status', 'n'),
                'total' => round((float)$order->getVar('total_amount', 'n'), 2),
                'items' => $items,
                'answers' => $answers,
            ];
        }

        return [
            'timezone' => $this->timezoneLabel(),
            'unknown' => $unknownLabel,
            'statuses' => $this->statusList($statuses),
            'campaigns' => $this->campaignList(array_keys($usedCampaigns), $unknownLabel),
            'products' => $this->productList($usedProducts, $unknownLabel),
            'questions' => $this->questionList($usedQuestions, $language),
            'orders' => $orders,
        ];
    }

    /**
     * Safe to put inside a script element: a name or option with a tag, ampersand or quote cannot break out of it
     *
     * @param array<string, mixed> $data
     */
    public static function toJson(array $data): string
    {
        $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        return $json === false ? '{}' : $json;
    }

    /**
     * @return array<int, array<int, object>>
     */
    private function itemsByOrder(): array
    {
        $byOrder = [];

        foreach ($this->items->getObjects() as $item) {
            $byOrder[(int)$item->getVar('order_id', 'n')][] = $item;
        }

        return $byOrder;
    }

    /**
     * @return array<int, array<int, array{label: string, values: array<int, string>}>> order id => question id => answer
     */
    private function choiceAnswersByOrder(string $language): array
    {
        $byOrder = [];

        foreach ($this->answers->getObjects() as $answer) {
            $questionId = (int)$answer->getVar('question_id', 'n');

            if ($questionId <= 0 || ! $answer->isChoice()) {
                continue;
            }

            $values = array_values(array_filter(
                array_map('trim', preg_split('/\R/', $answer->getAnswerFor($language)) ?: []),
                static fn (string $value): bool => $value !== ''
            ));

            if ($values === []) {
                continue;
            }

            $byOrder[(int)$answer->getVar('order_id', 'n')][$questionId] = [
                'label' => $answer->getLabelFor($language),
                'values' => $values,
            ];
        }

        return $byOrder;
    }

    /**
     * @param array<string, string> $statuses
     * @return array<int, array{key: string, label: string}>
     */
    private function statusList(array $statuses): array
    {
        $list = [];

        foreach ($statuses as $key => $label) {
            $list[] = ['key' => (string)$key, 'label' => (string)$label];
        }

        return $list;
    }

    /**
     * @param array<int, int> $usedIds
     * @return array<int, array{id: int, name: string}>
     */
    private function campaignList(array $usedIds, string $unknownLabel): array
    {
        $list = [];

        foreach ($usedIds as $id) {
            $id = (int)$id;
            $campaign = $id > 0 ? $this->campaigns->get($id) : null;
            $name = $unknownLabel;

            if ($id > 0) {
                $name = $campaign && ! $campaign->isNew() ? (string)$campaign->getVar('name', 'n') : "#{$id}";
            }

            $list[] = ['id' => $id, 'name' => $name];
        }

        usort($list, static fn (array $a, array $b): int => [$a['id'] === 0, $a['name']] <=> [$b['id'] === 0, $b['name']]);

        return $list;
    }

    /**
     * The current name of a product, and the name it was ordered under when the product is gone
     *
     * @param array<int, string> $orderedNames product id => name on the most recent item
     * @return array<int, array{id: int, name: string}>
     */
    private function productList(array $orderedNames, string $unknownLabel): array
    {
        $list = [];

        foreach ($orderedNames as $id => $orderedName) {
            $product = $this->products->get((int)$id);
            $name = $product && ! $product->isNew() ? (string)$product->getVar('name', 'n') : $orderedName;
            $list[] = ['id' => (int)$id, 'name' => $name];
        }

        usort($list, static fn (array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        $list[] = ['id' => 0, 'name' => $unknownLabel];

        return $list;
    }

    /**
     * @param array<int, array{label?: string, options?: array<string, bool>}> $used
     * @return array<int, array{id: int, label: string, options: array<int, string>}>
     */
    private function questionList(array $used, string $language): array
    {
        $list = [];

        foreach ($used as $id => $info) {
            $question = $this->questions->get((int)$id);
            $label = $question && ! $question->isNew() ? $question->getLabelFor($language) : (string)($info['label'] ?? "#{$id}");
            $options = array_keys($info['options'] ?? []);

            $list[] = ['id' => (int)$id, 'label' => $label, 'options' => array_map('strval', $options)];
        }

        usort($list, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);

        return $list;
    }

    private function timezoneLabel(): string
    {
        $hours = $this->timezoneOffset / 3600;
        $sign = $hours < 0 ? '-' : '+';
        $absolute = abs($hours);
        $whole = (int)floor($absolute);
        $minutes = (int)round(($absolute - $whole) * 60);

        return 'UTC' . $sign . $whole . ($minutes > 0 ? sprintf(':%02d', $minutes) : '');
    }
}
