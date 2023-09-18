<?php

namespace NitroPack\NitroPack\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\Http;

class RemoveQueryParam implements ObserverInterface
{
    protected $request;

    public function __construct(Http $request)
    {
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
      // Get the request object
        $request = $this->request;
       // Check if the query parameter you want to remove is present
        $yourQueryParam = $request->getParam('ignorenitro');

        if (!empty($yourQueryParam)) {
           // Remove the query parameter
            $request->setParam('ignorenitro', null);
        }
    }
}
