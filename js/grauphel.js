/**
 * Undo
 *
 * Single action:
 * 1. User clicks "delete"
 * 2. Notification appears for 5 seconds
 * 3. Token row is hidden
 * 4a User clicks on notification:
 *    - Action is cancelled
 *    - Token row is shown again
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
var grauphel = {
    timeout: 5,

    //pending undo tasks, keyed by timer id
    tasks: {},

    guiTimer: null,

    notifier: function() {
        return document.getElementById('grauphel-notification');
    },

    simpleUndo: function(undoTask) {
        var notifier = grauphel.notifier();
        notifier.textContent = 'Token has been deleted. Click to undo.';
        notifier.onclick = function() {
            for (var id in grauphel.tasks) {
                clearTimeout(parseInt(id));
                grauphel.restore(grauphel.tasks[id]);
                delete grauphel.tasks[id];
            }
        };
        notifier.hidden = false;

        document.getElementById(undoTask.elementId).hidden = true;

        grauphel.startGuiTimer();
        var timer = setTimeout(
            function() {
                grauphel.executeTask(grauphel.tasks[timer], false);
                delete grauphel.tasks[timer];
            },
            grauphel.timeout * 1000
        );
        grauphel.tasks[timer] = undoTask;
    },

    executeTask: function(task, keepalive) {
        fetch(task.url, {
            method: task.method,
            headers: {requesttoken: document.head.dataset.requesttoken},
            keepalive: keepalive
        }).then(function() {
            var row = document.getElementById(task.elementId);
            if (row) {
                row.remove();
            }
        });
    },

    restore: function(undoTask) {
        document.getElementById(undoTask.elementId).hidden = false;

        var notifier = grauphel.notifier();
        notifier.textContent = 'Token has been restored.';
        notifier.onclick = function() {
            clearTimeout(grauphel.guiTimer);
            notifier.hidden = true;
        };
        grauphel.startGuiTimer();
    },

    executeAllTasks: function() {
        for (var id in grauphel.tasks) {
            clearTimeout(parseInt(id));
            grauphel.executeTask(grauphel.tasks[id], true);
            delete grauphel.tasks[id];
        }
    },

    startGuiTimer: function() {
        if (grauphel.guiTimer !== null) {
            clearTimeout(grauphel.guiTimer);
        }
        grauphel.guiTimer = setTimeout(
            function() {
                var notifier = grauphel.notifier();
                notifier.hidden = true;
                notifier.onclick = null;
            },
            grauphel.timeout * 1000
        );
    }
};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#grauphel-tokens .delete').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            grauphel.simpleUndo({
                'method': 'DELETE',
                'url': button.closest('form').getAttribute('action'),
                'elementId': button.dataset.token
            });
        });
    });

    //in case a user deletes tokens and leaves the page within the 5 seconds
    window.addEventListener('pagehide', grauphel.executeAllTasks);
});
