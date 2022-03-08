/**
 * @file
 * JavaScript for notification status update.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Attaches the batch behavior for notification.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.flagNotifications = {
    attach: function (context, settings) {
      // console.log('flagNotifications attached');

      // console.log(Drupal.url('/entity/flagging').toAbsolute();
      // console.log(Drupal.url('/entity/flagging'));
      // console.log(Drupal);
      var revisedSentance = '';

      /**
       * Notification status changed to UNREAD => READ.
       */
      $('.notification-msg').once().click(function (event) {
        // event.stopPropagation();
        // var ele = $(this);
        // console.log(ele);
        // var notificationCount = parseInt($('.notification-icon').attr('data-count'));
        // var noticationId = $(this).parent().attr('flag-id');
        // var redirectLocation = $(this).children().attr('data-link');
        //
        // var notiData = {'notiId': noticationId, 'notification_action': 'read'};
        //
        // getCsrfToken(function (csrfToken) {
        //   updateNotification(csrfToken, notiData, redirectLocation);
        //   itemsStatusChanged(ele, notificationCount, 'read');
        // });
        // return false;
      });

      /**
       * Views Notification redirection.
       */
      $('.noti-store-msg').once().click(function (event) {
        if ($(this).parent().attr('class') != 'notification-msg') {
          event.stopPropagation();
          var redirectLocation = $(this).attr('data-link');

          if (redirectLocation === '/') {
            // Do nothing.
          }
          else if (redirectLocation === '') {
            //location.reload();
          }
          else {
            window.location.href = redirectLocation;
          }
          return false;
        }
      });

      /**
       * Notification delete list.
       */
      $('.notification-remove').once().click(function (event) {
        event.stopPropagation();
        var ele = $(this);
        var flagId = ele.parent().attr('flag-id');
        var nfCount = parseInt($('.notification-icon').attr('data-count'));
        var nfData = {'notiId': flagId, 'notification_action': 'delete'};

        console.log(flagId);
        // Request send to delete from list.
        getCsrfToken(function (csrfToken) {
          deleteNotification(csrfToken, flagId, '/');
          // Remove notificaiton item from frontend block.
          itemsStatusChanged(ele, nfCount, 'delete');
        });
      });

      /**
       * Clear-all the notification list.
       */
      $('.clear-all-notification').once().click(function (event) {
        event.stopPropagation();

        $('#flag_notification_list > div > li').each(function( index ) {
          // console.log( index + ": " + $( this ).text() );
          // console.log($( this ).attr('flag-id'));
          var flagId = $( this ).attr('flag-id');
          getCsrfToken(function (csrfToken) {
            deleteNotification(csrfToken, flagId, '/');
          });
        });

        var ele = $(this);
        // Request send to clear all from list.
        getCsrfToken(function (csrfToken) {
          itemsStatusChanged(ele, 0, 'clearall');
        });
      });

      /**
       * Update the notification items status.
       */
      function itemsStatusChanged(ele, notificationCount, action) {
        var remainingCount = notificationCount - 1;

        if (remainingCount <= 0) {
          revisedSentance = 'You have no unread notifications';
          $('.fyi-notification').html(revisedSentance);
          $('#notificationcount').removeClass('notification-icon');
          $('.clear-all-notification').remove();
        }
        else {
          revisedSentance = 'You have ' + remainingCount;
          if (remainingCount <= 1) {
            revisedSentance += ' unread notifications';
          }
          else {
            revisedSentance += ' unread notifications';
          }
          $('.fyi-notification').html(revisedSentance);
          $('#notificationcount').attr('data-count', remainingCount);
        }
        switch (action) {
          case 'delete':
            ele.parent().slideUp().hide('slide', {
              direction: 'right'
            }, 1000);
            break;
          case 'clearall':
            $('.drop-content').html('');
            $('.notify-drop-title').html('You have no unread notifications');
            ele.remove();
            break;
          case 'read':
            ele.parent('li').removeClass('unread');
            ele.parent('li').removeClass('read');
            ele.parent('li').attr('data-read-status', 'read');
            break;
        }
      }

      /**
       * Fetch CSRF token to make POST request.
       */
      function getCsrfToken(callback) {
        $.get(Drupal.url('session/token'))
          .done(function (data) {
            var csrfToken = data;
            callback(csrfToken);
        });
      }


      // /entity/flagging/{flagging}: GET, DELETE
      function deleteNotification(csrfToken, flagId, redirectLocation) {
        console.log('Deleting notification');
        // https://drupal.stackexchange.com/questions/202167/what-is-the-equivalent-of-drupal-settings-basepath
        console.log(drupalSettings);
        // console.log(Drupal.url());
        $.ajax({
          url: drupalSettings.path.baseUrl+'entity/flagging/'+flagId+'?_format=json',
          // url: '/entity/flagging/'+flagId+'?_format=json',
          type: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          // data: JSON.stringify(notiData),
          // dataType: 'json',
          success: function (response) {
            console.log('flag deleted');
            if (redirectLocation === '/') {
              // Do nothing.
            }
            else if (redirectLocation === '') {
              //location.reload();
            }
            else {
              window.location.href = redirectLocation;
            }
          },
          error: function (XMLHttpRequest, textStatus, errorThrown) {
            console.log(errorThrown);
          }
        });
      }
      /**
       * Send request to update notification status.
       */
      function updateNotification(csrfToken, notiData, redirectLocation) {
        $.ajax({
          url: Drupal.url('api/notification_update?_format=json'),
          type: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          data: JSON.stringify(notiData),
          dataType: 'json',
          success: function (response) {
            if (redirectLocation === '/') {
              // Do nothing.
            }
            else if (redirectLocation === '') {
              //location.reload();
            }
            else {
              window.location.href = redirectLocation;
            }
          },
          error: function (XMLHttpRequest, textStatus, errorThrown) {
            console.log(errorThrown);
          }
        });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
