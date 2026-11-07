<?php
if (!defined('ICMS_ROOT_PATH')) { die('ImpressCMS root path not defined'); }

require_once __DIR__ . '/SimplecartEmailText.php';

/**
 * What the e-mails to a customer share: the language of the order and the answers to its questions
 */
trait OrderEmailTrait
{
    private string $language = '';

    private SimplecartEmailText $text;

    private function initLanguage(object $order): void
    {
        $this->language = $order->getLanguage();
        $this->text = SimplecartEmailText::forLanguage($this->language);
    }

    private function t(string $key): string
    {
        return $this->text->get($key);
    }

    private function formatDate(): string
    {
        $timestamp = (int)$this->order->getVar('timestamp', 'n');

        return $timestamp > 0 ? date($this->t('DATE_FORMAT'), $timestamp) : '';
    }

    private function getAnswersText(): string
    {
        $rows = $this->order->getAnswerRows($this->language);

        if ($rows === []) {
            return '';
        }

        $text = str_repeat('-', 70) . "\n";
        $text .= $this->t('ANSWERS_HEADING') . "\n";
        $text .= str_repeat('-', 70) . "\n";

        foreach ($rows as $row) {
            $text .= "{$row['label']}: {$row['value']}\n";
        }

        return $text . "\n";
    }

    private function getAnswersHtml(): string
    {
        $rows = $this->order->getAnswerRows($this->language);

        if ($rows === []) {
            return '';
        }

        $html = '';

        foreach ($rows as $row) {
            $label = htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8');
            $value = nl2br(htmlspecialchars($row['value'], ENT_QUOTES, 'UTF-8'));
            $html .= "<tr><td style=\"padding:2px 8px 2px 0;vertical-align:top;\"><strong>{$label}</strong></td><td>{$value}</td></tr>";
        }

        $heading = htmlspecialchars($this->t('ANSWERS_HEADING'), ENT_QUOTES, 'UTF-8');

        return "<h2 style=\"font-size:18px;\">{$heading}</h2><table>{$html}</table>";
    }
}
