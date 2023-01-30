require(['jquery'], function ($) {
	window.Notification = (_ => {
		var status = true;
		var timeout;

		var display = (msg, type) => {
			if (!status) return;

			if ($('#nitropack-notification[data-type=' + type + ']').length) {
				var messageElement = $('#nitropack-notification[data-type=' + type + ']').find("#nitropack-notification-message");

				$(messageElement).html(
					$(messageElement).html().concat(' ').concat(msg)
				);
			} else {
				clearTimeout(timeout);

				$('#nitropack-notification').remove();

				$('body').append(
					$('#template-nitropack-notification-'.concat(type)).html()
						.replace(/{message}/g, msg)
				);

				timeout = setTimeout(_ => {
					$('#nitropack-notification').remove();
				}, 3000);
			}
		}

		return {
			setStatus: newStatus => {
				status = newStatus;
			},
			success: msg => {
				display(msg, 'success');
			},
			danger: msg => {
				display(msg, 'danger');
			},
			info: msg => {
				display(msg, 'info');
			},
			warning: msg => {
				display(msg, 'warning');
			}
		}
	})();
});
