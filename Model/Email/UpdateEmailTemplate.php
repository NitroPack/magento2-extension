<?php

namespace NitroPack\NitroPack\Model\Email;

use Magento\Email\Model\Template;
use Magento\Email\Model\Template\Config as TemplateConfig;
use Magento\Framework\Exception\MailException;

class UpdateEmailTemplate
{
    private $templateId = 'nitropack_welcome_email_template'; // Your custom template identifier

    /**
     * @var TemplateConfig
     */
    private $templateConfig;

    /**
     * @var Template
     */
    private $templateModel;

    public function __construct(
        TemplateConfig $templateConfig,
        Template       $templateModel
    )
    {
        $this->templateConfig = $templateConfig;
        $this->templateModel = $templateModel;
    }

    /**
     * Update the email template HTML content.
     *
     * @param string $html
     * @throws MailException
     */
    public function updateTemplateHtml($html)
    {
        try {
            $template = $this->templateConfig->getTemplate($this->templateId);
            $template->setTemplateText($html)->save();
        } catch (\Exception $e) {
            throw new MailException(__('Unable to update the email template.'));
        }
    }

}
