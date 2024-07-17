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

namespace NitroPack\NitroPack\Controller\Adminhtml\Cache;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use NitroPack\NitroPack\Controller\Adminhtml\StoreAwareAction;
use NitroPack\NitroPack\Api\NitroServiceInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Class Purge - Purge Controller to clear the NitroPack cache
 * @extends StoreAwareAction
 * @package NitroPack\NitroPack\Controller\Adminhtml\Cache
 * @since 2.0.0
 */
class Purge extends StoreAwareAction
{
    /**
     * @var JsonFactory
     * */
    protected $resultJsonFactory;
    /**
     * @var NitroServiceInterface
     * */
    protected $nitro;

    /**
     * @param Context $context
     * @param NitroServiceInterface $nitro
     * */
    public function __construct(
        Context $context,
        NitroServiceInterface $nitro
    ) {
        parent::__construct($context, $nitro);
        $this->resultJsonFactory = $this->_objectManager->create(JsonFactory::class);
        $this->nitro = $nitro;
    }

    protected function nitroExecute()
    {
        try {
            $purgeResult = $this->nitro->purgeCache(
                null,
                null,
                \NitroPack\SDK\PurgeType::LIGHT_PURGE,
                'Manual cache purge via Magento Dashboard.'
            );
            $resultMsg = $purgeResult ? 'Cache was purged successfully' : 'Cache was NOT purged successfully';
            return $this->resultJsonFactory->create()->setData(array(
                'purged' => $purgeResult,
                'message' => $resultMsg
            ));
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData(array(
                'purged' => false,
                'message' => 'Cache was NOT purged successfully: ' . $e->getMessage()
            ));
        }
    }
}
