<?php

namespace NitroPack\NitroPack\Model\System\Message;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Data\Form\FormKey;


class VarnishMismatchNotification implements MessageInterface
{
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var FlagManager
     */
    private $flagManager;


    public function __construct(
        FlagManager $flagManager,
        RequestInterface $request
    )
    {
        $this->flagManager = $flagManager;
        $this->request = $request;

    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return 'nitro_varnish_mismatch_message';
    }

    /**
     * Check whether the message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {

        $dismissedMessages = $this->flagManager->getFlagData('nitro_varnish_mismatch_message') ;
        return !is_null($dismissedMessages) && !in_array($this->getIdentity(), $dismissedMessages) ;

    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        return __('The provided Reverse Proxy settings are not correct. They are saved but NitroPack may not work correctly.');
    }

    /**
     * Retrieve system message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }

}
