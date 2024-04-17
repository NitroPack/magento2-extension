<?php

namespace NitroPack\NitroPack\Observer\Email;

use Magento\Framework\Mail\Message;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Store\Model\StoreManagerInterface;
use NitroPack\NitroPack\Logger\Logger;
use Magento\User\Model\UserFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\HTTP\Client\Curl;

class WelcomeEmail implements ObserverInterface
{
    /**
     * @var UrlInterface
     * */
    protected $urlBuilder;

    /**
     * @var TransportInterfaceFactory
     * */
    protected $transportFactory;
    /**
     * @var SenderResolver
     * */
    protected $senderResolver;
    /**
     * @var StoreManagerInterface
     **/
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var UserFactory
     * */
    protected $userFactory;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * */
    protected $serializer;

    public function __construct(
        TransportInterfaceFactory                        $transportFactory,
        SenderResolver                                   $senderResolver,
        StoreManagerInterface                            $storeManager,
        Logger                                           $logger,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        UrlInterface              $urlBuilder,
        UserFactory                                      $userFactory
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
        $this->senderResolver = $senderResolver;
        $this->transportFactory = $transportFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->userFactory = $userFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->sendEmail();
    }


    public function sendEmail()
    {
        $backendUrl = $this->urlBuilder->getUrl('NitroPack/connect/index/group/1/');
        $sender = $this->senderResolver->resolve(
            'general',
            $this->storeManager->getStore()->getId()
        );
        $nitroTemplate = $this->getNitropackTemplate();
        $storeName = $this->storeManager->getStore()->getName();

        if (isset($nitroTemplate['content']) && isset($nitroTemplate['subject'])) {
            $content = $nitroTemplate['content'];
            $content = str_replace('{{{name}}}', $storeName, $content);
            $content = str_replace('https://{{{extension_connect_url}}}',$backendUrl, $content);
            // Create a message
            $message = new Message();
            // Set the sender (From) address
            $message->setFrom($sender['email'], $sender['name']);
            // Set the recipient (To) address
            $message->addTo($this->getOwnerEmail($sender['email']), $storeName);

            // Todo Set the Welcome email content
            $message->setSubject($nitroTemplate['subject']);
            $message->setMessageType('text/html');
            // Todo Get the content form sendgrid api provide by Alex

            $message->setBody($content);
            // Create a transport
            $transport = $this->transportFactory->create(['message' => clone $message]);
            try {
                // Send the email
                $transport->sendMessage();
                $this->logger->info("Email sent successfully!");
            } catch (\Exception $e) {
                $this->logger->info("Error sending email: " . $e->getMessage());
            }
        }
    }

    public function getOwnerEmail($email)
    {
        if (strpos($email, 'no-reply') !== false || strpos($email, 'noreply') !== false) {
            $userData = $this->userFactory->create()->getCollection()->addFieldToFilter('is_active', 1);
            if ($userData->getSize() > 0)
                return $userData->getFirstItem()->getEmail();

        }
        return $email;
    }

    /**
     * Get Template Data from Nitropack
     * @return bool
     */
    public function getNitropackTemplate()
    {
        try {
            $curl = new Curl();
            $curl->get("https://nitropack.io/mailer/template");
            return $this->serializer->unserialize($curl->getBody());

        } catch (\Exception $e) {
            return false;
        }
    }
}
