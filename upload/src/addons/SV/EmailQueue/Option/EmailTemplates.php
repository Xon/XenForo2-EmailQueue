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
        $templateRepo = \XF::repository('XF:Style');

        $masterStyle = $styleRepo->getMasterStyle();
        $emailTemplates = $templateRepo->findEffectiveTemplatesInStyle($masterStyle, 'email')
                                       ->fetch();

        $selectedTemplates = [];
        $additionalTemplates = [];
        $values = \array_fill_keys($option->option_value, true);
        /** @var \XF\Entity\Template $emailTemplate */
        foreach($emailTemplates as $key => $emailTemplate)
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

        return self::getTemplate('sv_emailqueue_option_email_templates', $option, $htmlParams, [
            'selectedTemplates'   => $selectedTemplates,
            'additionalTemplates' => $additionalTemplates,
        ]);
    }

    public static function validateOption(array &$value, Option $option)
    {
        $existingTemplates = isset($value['existing_templates']) ? $value['existing_templates'] : [];
        $newTemplates = isset($value['new_templates']) ? $value['new_templates'] : [];

        $selectedTemplates = array_merge($existingTemplates, $newTemplates);

        if (!$selectedTemplates)
        {
            $value = [];

            return true;
        }

        /** @var \XF\Repository\Style $styleRepo */
        $styleRepo = \XF::repository('XF:Style');
        /** @var \XF\Repository\Template $templateRepo */
        $templateRepo = \XF::repository('XF:Style');

        $masterStyle = $styleRepo->getMasterStyle();
        $emailTemplateTitles = $templateRepo->findEffectiveTemplatesInStyle($masterStyle, 'email')
                                            ->pluckFrom('title')
                                            ->fetch()
                                            ->toArray();
        $emailTemplateTitles = \array_fill_keys($emailTemplateTitles, true);
        $value = [];

        foreach ($selectedTemplates AS $selectedTemplate)
        {
            if (isset($emailTemplateTitles[$selectedTemplate]))
            {
                $value[] = $selectedTemplate;
            }
        }

        if (!$value)
        {
            return false;
        }

        return true;
    }
}