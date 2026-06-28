/**
 * Undo
 *
 * Single action:
 * 1. User clicks "delete"
 * 2. Notification appears for 5 seconds
 * 3. Token row is faded out
 * 4a User clicks on notification:
 *    - Action is cancelled
 *    - Token row is faded in
 * 4b User does not click on notification:
 *    - Action gets executed 5 seconds after the delete click
 *    - Token row gets removed
 *
 *
 * Multiple actions:
 * 1. User clicks "delete"
 * 2. Notification appears for 5 seconds
 * 3. User clicks "delete"
 * 4. Notification appears for 5 seconds
 * 5a User clicks on notification: All pending actions are cancelled
 * 5b User does not click on notification
 *    - Action 1 gets executed 5 seconds after the first click
 *    - Action 2 gets executed 5 seconds after the second click
 */
OC.grauphel = {
    simpleUndo: function(undoTask) {
        var notifier = $('#notification');
        var timeout = 5;
        notifier.off('click');
        notifier.text('Token has been deleted. Click to undo.');
        notifier.fadeIn();

        $('#' + undoTask.elementId).fadeOut();

        OC.grauphel.startGuiTimer(timeout, notifier);
        var timer = setTimeout(
            function() {
                var dataid = timer.toString();
                OC.grauphel.executeTask(notifier.data(dataid), true);
                notifier.removeData(dataid);
            },
            timeout * 1000
        );
        var dataid = timer.toString();
        notifier.data(dataid, undoTask);

        notifier.on('click', function() {
            for (var id in notifier.data()) {
                clearTimeout(parseInt(id));
                notifier.off('click');
                OC.grauphel.restore(notifier.data(id));
                notifier.removeData(id);
            }
        });
    },

    executeTask: function(task, async) {
        //console.log("execute task: ", task);
        jQuery.ajax({
            url:   task.url,
            type:  task.method,
            async: async
        });
    },

    restore: function(undoTask) {
        $('#' + undoTask.elementId).fadeIn();

        var notifier = $('#notification');
        var timeout = 5;
        notifier.off('click');
        notifier.text('Token has been restored.');

        OC.grauphel.startGuiTimer(timeout, notifier);
        notifier.on('click', function() {
            clearTimeout(OC.grauphel.guiTimer);
            notifier.fadeOut();
        });
    },

    executeAllTasks: function() {
        var notifier = $('#notification');
        for (var id in notifier.data()) {
            clearTimeout(parseInt(id));
            OC.grauphel.executeTask(notifier.data(id), false);
            notifier.removeData(id);
        }
    },

    guiTimer: null,

    startGuiTimer: function(timeout, notifier) {
        if (OC.grauphel.guiTimer !== null) {
            clearTimeout(OC.grauphel.guiTimer);
        }
        OC.grauphel.guiTimer = setTimeout(
            function() {
                notifier.fadeOut();
                notifier.off('click');
            },
            timeout * 1000
        );
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('#grauphel-tokens .delete');

    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            const form = event.currentTarget.closest('form');
            const undoTask = {
                'method': 'DELETE',
                'url': form ? form.action : '',
                'elementId': event.currentTarget.dataset.token
            };

            // Execute the custom app logic
            if (typeof OC.grauphel !== 'undefined') {
                OC.grauphel.simpleUndo(undoTask);
            }
        });
    });

    // In case a user deletes tokens and leaves the page within the 5 seconds
    window.addEventListener('beforeunload', function(e) {
        if (typeof OC.grauphel !== 'undefined') {
            OC.grauphel.executeAllTasks();
        }
    });
});

/* Nextcloud deprecated templated UI's in favor of Vue. But until we can port
 * to it, we work-around it and re-implement the settings-button section. */
document.addEventListener('DOMContentLoaded', () => {
	const button = document.querySelector('.settings-button');
	const appSettings = document.querySelector('#app-settings');

	if (button && appSettings) {
		button.addEventListener('click', () => {
			// Toggle the 'open' class on the PARENT div, as required by core app.scss
			appSettings.classList.toggle('open');
		});
	}
});
