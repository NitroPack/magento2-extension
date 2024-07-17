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

use Magento\Framework\App\RequestInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Setup\Validator\RedisConnectionValidator;

/**
 * Class RedisFullPageNotification - Redis Full Page Notification Model
 * @package NitroPack\NitroPack\Model\System\Message
 * @implements MessageInterface
 * @since 3.2.0
 * */
class RedisFullPageNotification implements MessageInterface
{
    /**
     * @var RequestInterface
     * */
    protected $request;
    /**
     * @var FlagManager
     */
    private $flagManager;
    /**
     * @var DirectoryList
     **/
    protected $directoryList;
    /**
     * @var RedisConnectionValidator
     * */
    protected $redisValidator;
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     * */
    protected $deploymentConfig;


    public const FLAG = 'nitro_redis_full_page_message';

    public function __construct(
        FlagManager $flagManager,
        RequestInterface $request,
        DirectoryList $directoryList,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        RedisConnectionValidator $redisValidator
    )
    {
        $this->flagManager = $flagManager;
        $this->request = $request;
        $this->directoryList = $directoryList;
        $this->deploymentConfig = $deploymentConfig;
        $this->redisValidator = $redisValidator;
    }

    /**
     * Retrieve unique system message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return self::FLAG;
    }

    public function getFlag()
    {
        return self::FLAG;
    }
    /**
     * Check whether the message should be shown
     *
     * @return bool
     */
    public function isDisplayed()
    {

        $dismissedMessages = $this->flagManager->getFlagData(self::FLAG);
        return is_null($dismissedMessages) &&   $this->validatedRedisConnection();

    }

    /**
     * Retrieve system message text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        return __('Your environment contain Redis as Full Page Cache. Redis to work properly with the NitroPack extension');
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


    public function validatedRedisConnection()
    {

        if($this->deploymentConfig->get('cache/frontend/page_cache/backend_options')){

        $config = [
            'host' => $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/host') ?  $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/host') : 'localhost',
            'db' => $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/database') ? $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/database')  : 0,
            'port' => $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/port') ? $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/port') : 6379,
            'pass' => $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/password') ? $this->deploymentConfig->get('cache/frontend/page_cache/backend_options/password'): null,

        ];

        if ($this->redisValidator->isValidConnection($config)) {
            return $config;
        }
        }
        return false;
    }
}
