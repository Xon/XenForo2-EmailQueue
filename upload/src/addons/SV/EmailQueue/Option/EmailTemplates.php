<?php

namespace SV\EmailQueue\Option;

use XF\Entity\Option;
use XF\Option\AbstractOption;
use XF\Repository\Style as StyleRepo;
use XF\Repository\Template as TemplateRepo;
use function array_fill_keys;

class EmailTemplates extends AbstractOption
{
    public static function renderOption(Option $option, array $htmlParams): string
    {
        /** @var StyleRepo $styleRepo */
        $styleRepo = \XF::repository('XF:Style');
        /** @var TemplateRepo $templateRepo */
        $templateRepo = \XF::repository('XF:Template');

        $masterStyle = $styleRepo->getMasterStyle();
        /** @var array<string,\XF\Entity\Template> $emailTemplates */
        $emailTemplates = $templateRepo->findEffectiveTemplatesInStyle($masterStyle, 'email')
                                       ->fetch();

        $selectedTemplates = [];
        $additionalTemplates = [];
        $values = $option->option_value;
        foreach ($emailTemplates as $key => $emailTemplate)
        {
            if (isset($values[$emailTemplate->title]))
            {
                $selectedTemplates[$key] = $emailTemplate;
            }
            else
            {
                $additionalTemplates[$key] = $emailTemplate;
            }
        }

        return self::getTemplate('admin:sv_emailqueue_option_email_templates', $option, $htmlParams, [
            'selectedTemplates'   => $selectedTemplates,
            'additionalTemplates' => $additionalTemplates,
        ]);
    }

    /**
     * @param array  $values
     * @param Option $option
     * @param string $optionId
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public static function verifyOption(array &$values, Option $option, string $optionId): bool
    {
        $selectedTemplates = $values['$inverted'] ?? array_keys($values);
        $selectedTemplates = \array_filter($selectedTemplates);

        if (!$selectedTemplates)
        {
            $values = [];

            return true;
        }

        /** @var StyleRepo $styleRepo */
        $styleRepo = \XF::repository('XF:Style');
        /** @var TemplateRepo $templateRepo */
        $templateRepo = \XF::repository('XF:Template');

        $masterStyle = $styleRepo->getMasterStyle();
        $emailTemplateTitles = $templateRepo->findEffectiveTemplatesInStyle($masterStyle, 'email')
                                            ->pluckFrom('title')
                                            ->fetch()
                                            ->toArray();
        $emailTemplateTitles = array_fill_keys($emailTemplateTitles, true);
        $values = [];

        foreach ($selectedTemplates AS $selectedTemplate)
        {
            if ($selectedTemplate && isset($emailTemplateTitles[$selectedTemplate]))
            {
                $values[$selectedTemplate] = true;
            }
        }

        return true;
    }
}