<?php

namespace NitroPack\NitroPack\Model\Email;

use Laminas\Mime\Mime;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\TemplateInterface;
use Magento\Framework\Phrase;
use Laminas\Mime\PartFactory;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Exception\LocalizedException;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    protected $templateIdentifier;
    protected $templateModel;
    protected $templateVars;
    protected $templateOptions;
    protected $transport;
    protected $templateFactory;
    protected $objectManager;
    protected $message;
    protected $_senderResolver;
    protected $mailTransportFactory;
    private $messageData = [];
    private $emailMessageInterfaceFactory;
    private $mimeMessageInterfaceFactory;
    private $mimePartInterfaceFactory;
    private $addressConverter;
    protected $attachments = [];
    protected $partFactory;

    /**
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param MessageInterfaceFactory|null $messageFactory
     * @param EmailMessageInterfaceFactory|null $emailMessageInterfaceFactory
     * @param MimeMessageInterfaceFactory|null $mimeMessageInterfaceFactory
     * @param MimePartInterfaceFactory|null $mimePartInterfaceFactory
     * @param AddressConverter|null $addressConverter
     */
    public function __construct(
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory = null,
        EmailMessageInterfaceFactory $emailMessageInterfaceFactory = null,
        MimeMessageInterfaceFactory $mimeMessageInterfaceFactory = null,
        MimePartInterfaceFactory $mimePartInterfaceFactory = null,
        AddressConverter $addressConverter = null
    ) {
        $this->templateFactory = $templateFactory;
        $this->objectManager = $objectManager;
        $this->_senderResolver = $senderResolver;
        $this->mailTransportFactory = $mailTransportFactory;
        $this->emailMessageInterfaceFactory = $emailMessageInterfaceFactory ?: $this->objectManager
            ->get(EmailMessageInterfaceFactory::class);
        $this->mimeMessageInterfaceFactory = $mimeMessageInterfaceFactory ?: $this->objectManager
            ->get(MimeMessageInterfaceFactory::class);
        $this->mimePartInterfaceFactory = $mimePartInterfaceFactory ?: $this->objectManager
            ->get(MimePartInterfaceFactory::class);
        $this->addressConverter = $addressConverter ?: $this->objectManager
            ->get(AddressConverter::class);
        $this->partFactory = $objectManager->get(PartFactory::class);
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $emailMessageInterfaceFactory,
            $mimeMessageInterfaceFactory,
            $mimePartInterfaceFactory,
            $addressConverter
        );
    }

    /**
     * @param $address
     * @param $name
     * @return $this|TransportBuilder
     */
    public function addCc($address, $name = '')
    {
        $this->addAddressByType('cc', $address, $name);

        return $this;
    }

    /**
     * @param $address
     * @param $name
     * @return $this|TransportBuilder
     */
    public function addTo($address, $name = '')
    {
        $this->addAddressByType('to', $address, $name);

        return $this;
    }

    /**
     * @param $address
     * @return $this|TransportBuilder
     */
    public function addBcc($address)
    {
        $this->addAddressByType('bcc', $address);

        return $this;
    }

    /**
     * @param $email
     * @param $name
     * @return $this|TransportBuilder
     */
    public function setReplyTo($email, $name = null)
    {
        $this->addAddressByType('replyTo', $email, $name);

        return $this;
    }

    /**
     * @param $from
     * @return $this|TransportBuilder
     * @throws MailException
     */
    public function setFrom($from)
    {
        return $this->setFromByScope($from);
    }

    /**
     * @param $from
     * @param $scopeId
     * @return $this|TransportBuilder
     * @throws MailException
     */
    public function setFromByScope($from, $scopeId = null)
    {
        $result = $this->_senderResolver->resolve($from, $scopeId);
        $this->addAddressByType('from', $result['email'], $result['name']);

        return $this;
    }

    /**
     * @param $templateIdentifier
     * @return $this|TransportBuilder
     */
    public function setTemplateIdentifier($templateIdentifier)
    {
        $this->templateIdentifier = $templateIdentifier;

        return $this;
    }

    /**
     * @param $templateModel
     * @return $this|TransportBuilder
     */
    public function setTemplateModel($templateModel)
    {
        $this->templateModel = $templateModel;
        return $this;
    }

    /**
     * @param $templateVars
     * @return $this|TransportBuilder
     */
    public function setTemplateVars($templateVars)
    {
        $this->templateVars = $templateVars;

        return $this;
    }

    /**
     * @param $templateOptions
     * @return $this|TransportBuilder
     */
    public function setTemplateOptions($templateOptions)
    {
        $this->templateOptions = $templateOptions;

        return $this;
    }

    /**
     * @return \Magento\Framework\Mail\TransportInterface
     * @throws LocalizedException
     */
    public function getTransport()
    {
        try {
            $this->prepareMessage();
            $mailTransport = $this->mailTransportFactory->create(['message' => clone $this->message]);
        } finally {
            $this->reset();
        }

        return $mailTransport;
    }

    /**
     * @return $this|TransportBuilder
     */
    protected function reset()
    {
        $this->messageData = [];
        $this->templateIdentifier = null;
        $this->templateVars = null;
        $this->templateOptions = null;
        return $this;
    }

    /**
     * @return TemplateInterface
     */
    protected function getTemplate()
    {
        return $this->templateFactory->get($this->templateIdentifier, $this->templateModel)
            ->setVars($this->templateVars)
            ->setOptions($this->templateOptions);
    }

    /**
     * @return $this|TransportBuilder
     * @throws LocalizedException
     */
    protected function prepareMessage()
    {
        $template = $this->getTemplate();
        $content = $template->processTemplate();
        switch ($template->getType()) {
            case TemplateTypesInterface::TYPE_TEXT:
                $part['type'] = MimeInterface::TYPE_TEXT;
                break;

            case TemplateTypesInterface::TYPE_HTML:
                $part['type'] = MimeInterface::TYPE_HTML;
                break;

            default:
                throw new LocalizedException(
                    new Phrase('Unknown template type')
                );
        }
        $mimePart = $this->mimePartInterfaceFactory->create(['content' => $content]);
        $parts = count($this->attachments) ? array_merge([$mimePart], $this->attachments) : [$mimePart];
        $this->messageData['body'] = $this->mimeMessageInterfaceFactory->create(
            ['parts' => $parts]
        );

        $this->messageData['subject'] = html_entity_decode(
            (string) $template->getSubject(),
            ENT_QUOTES
        );
        $this->message = $this->emailMessageInterfaceFactory->create($this->messageData);

        return $this;
    }

    /**
     * @param $addressType
     * @param $email
     * @param $name
     * @return void
     */
    private function addAddressByType($addressType, $email, $name = null): void
    {
        if (is_string($email)) {
            $this->messageData[$addressType][] = $this->addressConverter->convert($email, $name);
            return;
        }
        $convertedAddressArray = $this->addressConverter->convertMany($email);
        if (isset($this->messageData[$addressType])) {
            $this->messageData[$addressType] = array_merge(
                $this->messageData[$addressType],
                $convertedAddressArray
            );
        }
    }

    /**
     * @param $content
     * @param $fileName
     * @param $fileType
     * @return $this
     */
    public function addAttachment($content, $fileName, $fileType): self
    {
        $attachmentPart = $this->partFactory->create();
        $attachmentPart->setContent($content)
            ->setType($fileType)
            ->setFileName($fileName)
            ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
            ->setEncoding(Mime::ENCODING_BASE64);
        $this->attachments[] = $attachmentPart;

        return $this;
    }
}
