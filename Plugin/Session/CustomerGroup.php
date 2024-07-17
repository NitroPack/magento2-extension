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

namespace NitroPack\NitroPack\Plugin\Session;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;

/**
 * Class CustomerGroup - Customer Group
 * @package NitroPack\NitroPack\Plugin\Session
 * @since 2.4.0
 * */
class CustomerGroup
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    )
    {
        $this->context = $context;
    }

    /**
     * @param Session $subject
     * @param $result
     * @return mixed|null
     */
    public function afterGetCustomerGroupId(Session $subject, $result)
    {
        if ($this->context->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP)) {
            return $this->context->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP);
        }

        return $result;
    }


}
