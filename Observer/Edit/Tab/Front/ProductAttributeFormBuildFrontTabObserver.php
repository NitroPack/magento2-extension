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
namespace NitroPack\NitroPack\Observer\Edit\Tab\Front;

use Magento\Config\Model\Config\Source;
use Magento\Framework\Module\Manager;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ProductAttributeFormBuildFrontTabObserver - Observer for adding a new tab in the product edit page in the admin panel
 * @implements ObserverInterface
 * @package NitroPack\NitroPack\Observer\Edit\Tab\Front
 * @since 2.0.0
 * */
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
