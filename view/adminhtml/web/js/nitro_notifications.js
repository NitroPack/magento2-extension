require(['jquery'], function ($) {

    $(document).on('click','#closebutton',function(){
        $('#nitropack-notification').remove();
    });

    window.Notification = (_ => {
        var status = true;
        var timeout;
        var remainingTime = 0;

        var display = (msg, type) => {
            if (!status) return;
            clearTimeout(timeout);
            var notification = $(
                $('#template-nitropack-notification-'.concat(type)).html()
                    .replace(/{message}/g, msg)
            ).attr('id', 'nitropack-notification');
            $('body').append(notification);
            if (remainingTime > 0) {
                timeout = setTimeout(_ => {
                    $('#nitropack-notification').remove();
                }, remainingTime);
            } else {
                timeout = setTimeout(_ => {
                    $('#nitropack-notification').remove();
                }, 1500);
            }

            notification.on('mouseenter', function() {
                clearTimeout(timeout);
                remainingTime = Math.max(0, remainingTime - (new Date() - startTime));
            });

            var startTime;
            notification.on('mouseleave', function() {
                startTime = new Date();
                remainingTime = 1500 - (new Date() - startTime);
                timeout = setTimeout(_ => {
                    $('#nitropack-notification').remove();
                }, remainingTime);
            });
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
