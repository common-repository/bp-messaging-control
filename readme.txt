=== BuddyPress Messaging Control ===
Contributors: nuprn1, etivite
Donate link: paypal.me/GeorgeChaplin
Tags: buddypress, messages, messaging, private message, mentions
Requires at least: PHP 5.2, WordPress 3.2.1 BuddyPress 1.5.1
Tested up to: 6.6
Stable tag: 1.8.0
Plugin URI: www.wordpress.org/plugins/bp-messaging-control/

This plugin is a Swiss Army Knife for messaging, It allows the site admin to place restrictions on public and private messages including general rules and quotas per role, message length and control over notification emails.

== Description ==

This plugin is perfect if you want to use messaging as a way of differentiating or monetizing your BP Site. It's also good for controlling internal spam. You can control access to messaging and who the user can message. It also allows quotas to be set for each role and maximum message size. The options available are:

Based on each role you can disable or set limits on messaging.

Private Messaging:

* Messaging Disabled - No access to messaging for this role.
* Admin Only - The role can only message site admin.
* Reply Only - The role can only message users who have previously sent them a message. Admin messages excluded from limitation.
* Full Messaging - The role has unrestricted access to messaging.
* Messaging quota; 1 to unlimited emails per month, week or day. Messages to admin not counted. 
* Message Character Limit: Enforce maximum number of characters per message.
* Notification Email content length restrictions: Allows you to display only the first few words of the message so users will visit the site to view the message.

Public Messaging ( @Mentions and Activity Updates )

* @Mentions Disabled - No access to public messaging for this role.
* @Mentions Admin Only - The role can only public message site admin.
* @Mentions Reply Only - The role can only public message users who have previously sent them a public message.
* @Mentions all - The role has unrestricted access to pubic messaging.
* @Mentions quota; 5, 10, 25, 50, 100, 250, 500, 1000, unlimited emails per month, week or day. Messages to admin not counted. 
* Activity Updates including Public Messages: Enforce maximum number of characters per Activity Update or Comment.
* Notification Email content length restrictions: Allows you to display only the first few words of the message so users will visit the site to view the message.

Notification Emails

* Set size limit for the usermessage token - the notificaton message content. This is useful to prevent the entire message being sent in the notification meaning users will need to visit the site to read the full message.
* Enable Admin notification of user deletions.

This plugin needs BuddyPress to work and it supports both BP Legacy and BP Nouveau themes.

This plugin controls all aspects of BP messaging and makes sure the user only sees the messaging options they are entitled to.

== Installation ==

1. Upload the full directory into your wp-content/plugins directory
2. Activate the plugin at the plugin administration page
3. Adjust settings via the Messaging Control admin page

== Frequently Asked Questions ==




== Changelog ==

= 1.8.0 =

* 21/07/2024

* Update: Optimised by removing 40+ function calls. Improved translations and escaping.

= 1.7.1 =

* 19/05/2021

* Fix: Corrected typo preventing user quota from being discovered

= 1.7.0 =

* 19/03/2021

* New: Added feature to send admin notification for deleted users.

= 1.6.7 =

* 07/01/2021

* Fix: Corrected issue with the text_domain loader.

= 1.6.6 =

* 07/05/2019

* Fix: Corrected mentions and private messages count for new users.

= 1.6.5 =

* 06/05/2019

* Fix: Corrected display of private message limit.
* Fix: Corrected enforcement of PM Character limit.

= 1.6.4 =

* 06/05/2019

* Fix: More updates for multisite.

= 1.6.3 =

* 06/05/2017

* Fix: Enabled individual site loading for multisite.

= 1.6.2 =

* 01/05/2019

* Fix: Settings screen CSS and text improvements.

= 1.6.1 =

* 01/05/2019

* Fix: Corrected typo preventing unliited message sent email length.

= 1.6.0 =

* 27/04/2019

* New: Plugin now restricts messaging character length on input and displays the number of characters remaining.
* New: Plugin now controls the length of the content in both private and public message notification emails.
* New: CSS and text improvements to the Settings page.

= 1.5.2 =

* 27/04/2019

* New: Added the ability to limit the length of the usermessage token, used in notifications emails to display the message content.

= 1.5.0 =

* 26/04/2019

* New: Added message character limit restrictions.

= 1.4.9 =

* 26/04/2019

* New: Added more message quota options.

= 1.4.8 =

* 01/04/2019

* Fix: Corrected date calculation so it uses the local server timezone.

= 1.4.7 =

* 28/03/2019

* Fix: corrected error handling negative counts.

= 1.4.6 =

* 28/03/2019

* Fix: Corrected logic error causing quota miscounting.

= 1.4.5 =

* 27/03/2019

* Fix: Corrected error preventing mentions for users with full mentions capability.

= 1.4.4 =

* 25/03/2019

* Fix: Better role checking.

= 1.4.3 =

* 25/03/2019

* Fix: Corrected variable not found errors for non editable roles included in users roles array.

= 1.4.2 =

* 25/03/2019

* Fix: Corrected variable naming errors.

= 1.4.1 =

* 24/03/2019

* Fix: corrected some variable names.
* Fix: corrected variable not found error in check() function

= 1.4.0 =

* 24/03/2019

* New: Added mentions messages quota options.

= 1.3.1 =

* New: added admin only and reply only to @mentions restrictions.

= 1.3.0 =

* New: Added ability to disabled @Mentions for each site role.

= 1.2.1 =

* Fix: Corrected typo causing weekly mail counts to remain static. 

= 1.2.0 =

* 21/03/2019

* New: Added daily and weekly quotas.
* Updated: User compose message improvements.

= 1.1.0

* 21/03/2019

* New: added email quotas per role.

= 1.0.1 =

* 17/03/2019

* Fix: Corrected undefined index error for compose screen.

= 1.0.0 =

* Initial Release.


== Upgrade Notice ==


== Screenshots ==

1. screenshot-1.png - Plugin Settings page 1.
2. screenshot-2.png - Compose Message screen with restriction announcement.
3. screenshot-3.png - Plugin Settings page 2.
