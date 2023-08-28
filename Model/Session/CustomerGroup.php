<?php

namespace NitroPack\NitroPack\Model\Session;

class CustomerGroup
{
    protected $context;
    public function __construct(
        \Magento\Framework\App\Http\Context $context
    ) {
        $this->context = $context;
    }


    public function afterGetCustomerGroupId(\Magento\Customer\Model\Session $subject, $result) {
        if($this->context->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP)){
            return $this->context->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP);
        }
        return $result;

    }


}
