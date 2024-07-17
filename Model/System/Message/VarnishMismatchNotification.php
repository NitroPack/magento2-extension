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
namespace NitroPack\NitroPack\Model\System\Message;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\Data\Form\FormKey;

/**
 * Class VarnishMismatchNotification - Varnish Mismatch Notification
 * @package NitroPack\NitroPack\Model\System\Message
 * @implements MessageInterface
 * @since 3.2.0
 * */
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
