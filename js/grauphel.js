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

$(document).ready(function() {
    $('#grauphel-tokens .delete').click(
        function (event) {
            event.preventDefault();

            var undoTask = {
                'method': 'DELETE',
                'url': $(this).parent('form').attr('action'),
                'elementId': $(this).data('token')
            };
            OC.grauphel.simpleUndo(undoTask);
            return false;
        }
    );

    //in case a user deletes tokens and leaves the page within the 5 seconds
    window.onbeforeunload = function(e) {
        OC.grauphel.executeAllTasks();
    };
});
