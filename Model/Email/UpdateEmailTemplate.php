<?php
/**
 * NitroPack
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the nitropack.io license that is
 * available through the world-wide-web at this URL:
 * https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Site Optimization
 * @subcategory Performance
 * @package     NitroPack_NitroPack
 * @author      NitroPack Inc.
 * @copyright   Copyright (c) NitroPack (https://www.nitropack.io/)
 * @license     https://github.com/NitroPack/magento2-extension/blob/716247d40d2de7b84f222c6a93761d87b6fe5b7b/LICENSE
 */
namespace NitroPack\NitroPack\Model\Email;

use Magento\Email\Model\Template;
use Magento\Email\Model\Template\Config as TemplateConfig;
use Magento\Framework\Exception\MailException;

/**
 * Class UpdateEmailTemplate - Update Email Template
 * @package NitroPack\NitroPack\Model\Email
 * @extends TransportBuilder
 * @since 2.8.0
 * */
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
