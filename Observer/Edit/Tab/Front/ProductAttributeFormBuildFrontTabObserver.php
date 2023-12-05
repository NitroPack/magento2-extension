<?php
/**
 * Product attribute edit form observer
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace NitroPack\NitroPack\Observer\Edit\Tab\Front;

use Magento\Config\Model\Config\Source;
use Magento\Framework\Module\Manager;
use Magento\Framework\Event\ObserverInterface;

/**
 * Observer for Product Attribute Form
 */
class ProductAttributeFormBuildFrontTabObserver implements ObserverInterface
{
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $optionList;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param Manager $moduleManager
     * @param Source\Yesno $optionList
     */
    public function __construct(Manager $moduleManager, Source\Yesno $optionList)
    {
        $this->optionList = $optionList;
        $this->moduleManager = $moduleManager;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->moduleManager->isOutputEnabled('NitroPack_NitroPack')) {
            return;
        }
        /** @var \Magento\Framework\Data\Form\AbstractForm $form */
        $form = $observer->getForm();
        $fieldset = $form->getElement('base_fieldset');
        $fieldset->addField(
            'nitro_purge',
            'select',
            [
                'name' => 'nitro_purge',
                'label' => __("Purge Nitro Cache"),
                'title' => __('Activate this feature to ensure that NitroPack clears your site’s cache whenever this attribute changes for any of your products.'),
                'note' => __(
                    'Activate this feature to ensure that NitroPack clears your site’s cache whenever this attribute changes for any of your products.'
                ),
                'values' => [
                    ['value' => '1', 'label' => __('Yes')],
                    ['value' => '0', 'label' => __('No')],

                ],
            ]
        );


    }
}
