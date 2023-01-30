require(['jquery', 'Magento_Ui/js/modal/alert'], function ($, magentoAlert) {
	window.CacheWarmup = (_ => {
		var config = null;
		var initialConfig = null;
		var visible = false;
		var onSaveCallback = null;
		var persistCallback = null;
		var activeToggleCallback = null;
		var currentStoreCode = null;

		var storeCodeColors = [
			'#50514F',
			'#E85D22',
			'#247BA0',
			'#DEC157',
			'#37A056',
			'#70C1B3'
		];

		var pageTypes = [
			{ type: 'home',	 label: 'Home'},
			{ type: 'product',  label: 'Products'},
			{ type: 'category', label: 'Categories'},
			{ type: 'info',	 label: 'Info pages'},
			{ type: 'contact',  label: 'Contact'}
		];

		var templates = {
			modal:           $('#cacheWarmupModal').html(),
			storeView:       $('#cacheWarmupStoreView').html(),
			storeCodeBadge:  $('#cacheWarmupStoreCodeBadge').html(),
			currency:        $('#cacheWarmupCurrency').html(),
			emptyCurrencies: $('#cacheWarmupEmptyCurrencies').html(),
			pageType:        $('#cacheWarmupPageType').html(),
			switch:          $('#cacheWarmupSwitch').html()
		};

		var renderModal = () => {
			let modal = templates.modal;
			modal = modal.split('{%STORE_VIEWS%}').join(renderStoreViews());
			modal = modal.split('{%CURRENCIES%}').join(renderCurrencies());
			modal = modal.split('{%PAGE_TYPES%}').join(renderPages());
			return modal;
		};

		var renderStoreViews = () => {
			let storeViews = [];
			
			for(let i=0; i<config.storeViews.length; ++i) {
				storeViews.push(renderStoreView(config.storeViews[i]));
			}
			
			return storeViews.join('');
		};

		var renderCurrencies = () => {
			let currencies = [];

			for(let i=0; i<config.storeViews.length; ++i) {
				if (config.storeViews[i].enabled) {
					currencies = currencies.concat(config.storeViews[i].currencies);
				}
			}

			if (currencies.length == 0) {
				return templates.emptyCurrencies;
			}

			currencies = currencies.filter((value, index, self) => {
				// unique currencies only
				return self.indexOf(value) === index;
			});

			currencies.sort();

			currencies = currencies.map((currency) => {
				return renderCurrency(currency, config.currencies[currency]);
			});

			return currencies.join('');
		};

		var renderPages = () => {
			let pages = [];

			for (let i=0; i<pageTypes.length; ++i) {
				pages.push(renderPageType(pageTypes[i].type, pageTypes[i].label, config.pageTypes[pageTypes[i].type]));
			}

			return pages.join('');
		};

		var renderStoreView = (storeView) => {
			let storeViewHtml = templates.storeView;
			let storeSwitch = renderSwitch(storeView.connected, storeView.enabled, {'data-type': 'storeView', 'data-entity': storeView.code}, storeView.connected ? '' : 'This store view is not connected to NitroPack');
			let codeBadge = renderStoreCodeBadge(storeView.code);
			
			storeViewHtml = storeViewHtml.split('{%STORE_NAME%}').join(storeView.name);
			storeViewHtml = storeViewHtml.split('{%STORE_CODE%}').join(codeBadge);
			storeViewHtml = storeViewHtml.split('{%STORE_SWITCH%}').join(storeSwitch);
			
			return storeViewHtml;
		};

		var renderCurrency = (currency, status) => {
			let currencyHtml = templates.currency;
			let currencySwitch = renderSwitch(true, status, {'data-type': 'currency', 'data-entity': currency});
			let storeCodes = [];
			
			for (let i=0; i<config.storeViews.length; ++i) {
				if (config.storeViews[i].currencies.indexOf(currency) != -1) {
					if (config.storeViews[i].connected && config.storeViews[i].enabled) {
						storeCodes.push(config.storeViews[i].code);
					}
				}
			}
			storeCodes = storeCodes.map(renderStoreCodeBadge).join('');

			currencyHtml = currencyHtml.split('{%CURRENCY_LABEL%}').join(currency);
			currencyHtml = currencyHtml.split('{%STORE_CODES%}').join(storeCodes);
			currencyHtml = currencyHtml.split('{%CURRENCY_SWITCH%}').join(currencySwitch);

			return currencyHtml;
		};

		var renderPageType = (type, label, status) => {
			let page = templates.pageType;
			let pageSwitch = renderSwitch(true, status, {'data-type': 'page', 'data-entity': type});

			page = page.split('{%PAGE_LABEL%}').join(label);
			page = page.split('{%PAGE_SWITCH%}').join(pageSwitch);

			return page;
		};

		function stringToHash(string) {
			var hash = 0;
			if (string.length == 0) return hash;
			for (i = 0; i < string.length; i++) {
				char = string.charCodeAt(i);
				hash = ((hash << 5) - hash) + char;
				hash = hash & hash;
			}
			return hash; 
		}

		var renderStoreCodeBadge = (code) => {
			let codeBadge = templates.storeCodeBadge;
			
			let hashed = stringToHash(code);
			let backR = hashed & 255;
			let backG = (hashed & (255 << 8)) >> 8;
			let backB = (hashed & (255 << 16)) >> 16;

			let colorIndex = 0;
			for (;colorIndex<config.storeViews.length;++colorIndex) {
				if (config.storeViews[colorIndex].code == code) break;
			}

			if (colorIndex >= config.storeViews.length) colorIndex = colorIndex % config.storeViews.length;

			let back = storeCodeColors[colorIndex];

			let textR = 255;
			let textG = 255;
			let textB = 255;

			let backColor = storeCodeColors[colorIndex];
			let textColor = 'rgb(' + textR + ',' + textG + ',' + textB + ')';

			let style = 'style="background-color: ' + backColor + '; color: ' + textColor + ';"';

			codeBadge = codeBadge.split('{%STORE_CODE%}').join(code);
			codeBadge = codeBadge.split('customstyle=""').join(style);
			
			return codeBadge;
		};

		var renderSwitch = (enabled, checked, attributes=null, title='') => {
			let switchTmpl = templates.switch;
			let attributesHtml = '';

			if (!enabled) attributesHtml = attributesHtml + 'disabled ';
			if (checked) attributesHtml = attributesHtml + 'checked ';

			if (attributes != null) {
				for (attribute in attributes) {
					attributesHtml = attributesHtml + attribute + '="' + attributes[attribute] + '" ';
				}
			}
	
			switchTmpl = switchTmpl.split('{%SWITCH_TITLE%}').join(title);
			switchTmpl = switchTmpl.split('customattributes=""').join(attributesHtml);

			return switchTmpl;
		};

		var onSwitchToggle = function(event) {
			let target = $(event.target);
			let type = target.data('type');
			let entity = target.data('entity');
			let state = target.is(':checked');

			switch (type) {
				case 'storeView':
					for (let i=0; i<config.storeViews.length; ++i) {
						if (config.storeViews[i].code != entity) continue;
						config.storeViews[i].enabled = state;
						updateCurrencies();
						break;
					}
					break;
				case 'currency':
					config.currencies[entity] = state;
					break;
				case 'page':
					config.pageTypes[entity] = state;
					break;
			}
		};

		var updateCurrencies = () => {
			$('.cacheWarmupCurrencyContainer').html(renderCurrencies());
			$('.cacheWarmupCurrencyContainer [data-toggle="tooltip"]').tooltip();
		};

		var onCloseModal = (modal) => {
			detachHandlers();
			modal.closeModal(true);
		};

		var onSaveButton = (modal) => {
			$('.save-button').html('Saving..');
			return persistCallback(config, successfulSave.bind(null, modal), failedSave.bind(null, modal));
		};

		var successfulSave = (modal, config) => {
			for (let i=0; i<config.storeViews.length; ++i) {
				if (config.storeViews[i].code != currentStoreCode) continue;
				activeToggleCallback(config.storeViews[i].enabled);
				break;
			}
			onCloseModal(modal);
		};

		var failedSave = (modal, config, errors) => {
			// @TODO show errors
			onCloseModal(modal);
		};

		var attachHandlers = () => {
			$('.nitro-cache-warmup').on('change', 'input[type="checkbox"]', onSwitchToggle);
		};

		var detachHandlers = () => {
			$('.nitro-cache-warmup').off('change', 'input[type="checkbox"]', onSwitchToggle);
		};

		var initializeTooltips = () => {
			$('.nitro-cache-warmup [data-toggle="tooltip"]').tooltip();
		};

		return {
			showModal: (cwConfig, activeStoreCode, persistenceCallback, activeStoreToggleCallback) => {
				config = Object.assign({}, cwConfig);
				initialConfig = Object.assign({}, cwConfig);
				currentStoreCode = activeStoreCode;
				persistCallback = persistenceCallback;
				activeToggleCallback = activeStoreToggleCallback;

				magentoAlert({
					content: renderModal(),
					title: 'Configure Cache Warmup',
					responsive: true,
					clickableOverlay: true,
					modalClass: 'nitro nitro-cache-warmup',
					buttons: [{
						text: 'Cancel',
						class: 'btn',
						click: function() { onCloseModal(this); }
					}, {
						text: 'Save',
						class: 'btn btn-primary save-button',
						click: function() { onSaveButton(this); }
					}
					]
				});

				attachHandlers();
				initializeTooltips();
			}
		};
	})();
});