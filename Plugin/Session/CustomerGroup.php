<?php
/**
 * @author NitroPack Inc.
 * @copyright Copyright (c) NitroPack Inc. (https://www.nitropack.io)
 * @package NitroPack for Magento 2
 */

namespace NitroPack\NitroPack\Plugin\Session;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context;

/**
 * class CustomerGroup
 * @package NitroPack\NitroPack\Plugin\Session;
 */
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
