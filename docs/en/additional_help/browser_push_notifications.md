Most modern web browsers like Mozilla Firefox, Google Chrome and Microsoft Edge provide a _Notification API_.

openITCOCKPIT can use this API to send a push notification to your browser. You can use this as a notification method
for all objects that are monitored by the system.

## Requirements

- Modern web browser
- openITCOCKPIT needs to be open in one tab (Browser window can be in the background or task bar)
- Contact needs to be assigned to a user.
- `Push notifications to browser` needs to be enabled in contact configuration
- `openitcockpit-websocket` service needs to be running on the openITCOCKPIT server

## Setup push notifications

### Browser permissions

Before openITCOCKPIT can send you notifications, your web browser will ask you to grant permissions for browser
notifications. This is a security feature of all browsers, to avoid than random websites will send you spam messages.

**You can always grant or revoke the permissions.**

In this example, we show the process using Mozilla Firefox.

#### Grant permissions

The question, if you want to receive notifications of openITCOCKPIT, will popup automatically.

![Grant browser notification permissions](/img/docs/notifications/browser_ask_for_notification_permissions.png)
{.img-fluid}

#### Revoke permissions

To revoke the permissions, click on `Show site information` in the browser address bar.

![Revoke browser notification permissions](/img/docs/notifications/revoke_broker_permissions.png){.img-fluid}

If you revoked permissions, because you don't want to get any push notifications of openITCOCKPIT, please also disable
`Push notifications to browser` in your contact configuration.

### Create or edit an existing contact

To recive push notifications, you need to link your user account with a contact of the monitoring engine. By default,
all contacts don't belong to any user that exists in the openITCOCKPIT interface.

1. Select the user that should get browser notifications.
2. Enable `Push notifications to browser` for hosts and/or services.

The command `host-notify-by-browser-notification` and/or `service-notify-by-browser-notification` will be selected
automatically.

If the commands are missing on your system, please read the **Troubleshooting** part of this article.

You can also add other notification commands like notify by email and browser notifications and so on.

![Create contact with push notifications](/img/docs/notifications/create_push_contact.png){.img-fluid}

After you edited the contact, please make sure to "Refresh your monitoring configuration":

![Refresh monitoring configuration](/img/docs/notifications/refresh_monitoring_configuration.png){.img-fluid}

### Notification examples

Every notification will container an icon for host or services. The current state is represented by the color of the
icon. By clicking on the notification, a new browser tab will navigate you to the corresponding host or service. The
notification will disappear automatically after a few seconds.

![Example push notification for hosts and services](/img/docs/notifications/example_browser_notifications.png)
{.img-fluid}

_Notice: The notification design is provided by the operating system. Notifications may be look different on different
operating systems. There is no way to use a different style for the notifications._

#Troubleshooting

- Connectivity issues. If your browser lost connection to the openITCOCKPIT server, it could happen that you will not
  receive push notifications. Refresh the page to resolve the issue.

- `openitcockpit-websocket` service not running. This background daemon needs to be up and running. Also your web server
  configuration or reverse proxy configuration could be cause an issue. You can check the developer tools of your
  browser, to check if there is a connectivity issue:
  Issue (`openitcockpit-websocket` not running):

![Connection Error](/img/docs/notifications/push_connection_error.png){.img-fluid} Successfully connected:

![Connected successfully](/img/docs/notifications/push_connection_successfully.png){.img-fluid}

- Some applications may suppress browser notifications. We notice this behavior on Microsoft Windows 7 in combination
  with `Snipping Tool`. A refresh of the openITCOCKPIT page resolved the issue.

- Too many notifications could also result in a suppressing issue. Try to reduce the amount of receive push
  notifications.

- Check if an update for your web browser is available.

- Does the notifications work in Internet Explorer? No.
