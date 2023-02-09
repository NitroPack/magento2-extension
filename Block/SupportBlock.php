<?php

namespace NitroPack\NitroPack\Block;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

class SupportBlock extends Template
{
    /**
     * @param Context $context
     * @param array $data
     * */
    public function __construct(
        Context $context, // required as part of the Magento\Backend\Block\Template constructor
        array $data = [] // required as part of the Magento\Backend\Block\Template constructor
    )
    {
        parent::__construct($context, $data);
    }
}
