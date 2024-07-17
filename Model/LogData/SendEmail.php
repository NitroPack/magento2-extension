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

namespace NitroPack\NitroPack\Model\LogData;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Api\LogContentInterface;
use NitroPack\NitroPack\Api\SendEmailInterface;
use NitroPack\NitroPack\Model\Email\TransportBuilder;


/**
 * Class SendEmail - Send Email Model
 * @implements SendEmailInterface
 * @package NitroPack\NitroPack\Model\LogData
 * @since 3.0.0
 * */
class SendEmail implements SendEmailInterface
{
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var LogContentInterface
     */
    private $logContentInterface;

    /**
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param StateInterface $state
     * @param LogContentInterface $logContentInterface
     */
    public function __construct(
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface        $state,
        LogContentInterface   $logContentInterface
    )
    {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->logContentInterface = $logContentInterface;
    }

    /**
     * @param $toEmail
     * @param $data
     * @return string|null
     * @throws LocalizedException
     * @throws MailException
     * @throws NoSuchEntityException
     */
    public function send($toEmail, $data): ?string
    {
        $templateId = 'nitropack_report';

        $templateVars = [
            'data' => [
                'subject' => $data['subject'],
                'content' => $data['content']
            ]
        ];

        $attachmentContent = $data['attachment']['content'] ?? '';
        $attachmentFileName = $data['attachment']['filename'] ?? 'NitroPack-Report.txt';
        $attachmentFileType = $data['attachment']['filetype'] ?? 'text/plain';

        $storeId = $this->storeManager->getStore()->getId();

        $storeScope = ScopeInterface::SCOPE_STORE;

        $templateOptions = [
            'area' => Area::AREA_FRONTEND,
            'store' => $storeId
        ];

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFromByScope('general')
            ->addTo($toEmail)
            ->AddAttachment($attachmentContent, $attachmentFileName, $attachmentFileType)
            ->getTransport();

        $transport->sendMessage();

        return '';
    }
}
