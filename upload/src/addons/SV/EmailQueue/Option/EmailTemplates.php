<?php

namespace SV\EmailQueue\Option;

use XF\Entity\Option;
use XF\Entity\Template;
use XF\Option\AbstractOption;

class EmailTemplates extends AbstractOption
{
    public static function renderOption(Option $option, array $htmlParams)
    {
        /** @var \XF\Repository\Style $styleRepo */
        $styleRepo = \XF::repository('XF:Style');
        /** @var \XF\Repository\Template $templateRepo */
        $templateRepo = \XF::repository('XF:Template');

        $masterStyle = $styleRepo->getMasterStyle();
        $emailTemplates = $templateRepo->findEffectiveTemplatesInStyle($masterStyle, 'email')
                                       ->fetch();

        $selectedTemplates = [];
        $additionalTemplates = [];
        $values = $option->option_value;
        /** @var \XF\Entity\Template $emailTemplate */
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
     */
    public static function verifyOption(/** @noinspection PhpUnusedParameterInspection */
        array &$values, Option $option, $optionId)
    {
        $selectedTemplates = isset($values['$inverted']) ? $values['$inverted'] : array_keys($values);
        $selectedTemplates = \array_filter($selectedTemplates);

        if (!$selectedTemplates)
        {
            $values = [];

            return true;
        }

        /** @var \XF\Repository\Style $styleRepo */
        $styleRepo = \XF::repository('XF:Style');
        /** @var \XF\Repository\Template $templateRepo */
        $templateRepo = \XF::repository('XF:Template');

        $masterStyle = $styleRepo->getMasterStyle();
        $emailTemplateTitles = $templateRepo->findEffectiveTemplatesInStyle($masterStyle, 'email')
                                            ->pluckFrom('title')
                                            ->fetch()
                                            ->toArray();
        $emailTemplateTitles = \array_fill_keys($emailTemplateTitles, true);
        $values = [];

        foreach ($selectedTemplates AS $selectedTemplate)
        {
            if (isset($emailTemplateTitles[$selectedTemplate]))
            {
                $values[$selectedTemplate] = true;
            }
        }

        return true;
    }
}