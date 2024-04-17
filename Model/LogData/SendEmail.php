<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
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
 * class SendEmail
 * @package NitroPack\NitroPack\Model\LogData
 */
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
