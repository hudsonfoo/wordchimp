=== WordChimp ===
Contributors: hudsonfoo
Donate link: http://hudsoncs.com/hudsoncs-news/projects/wordchimp/
Tags: mailing list, newsletter, mailchimp
Requires at least: 2.8
Tested up to: 3.2.1
Stable tag: 2.0

Create and send MailChimp campaigns by selecting Wordpress posts directly from the Wordpress administrator backend.

== Description ==

If you love MailChimp and Wordpress, you're in luck. Now you can create and send out MailChimp campaigns by selecting Wordpress posts directly from the Wordpress administrator backend. 

== Installation ==

1. Get a MailChimp API key [ http://kb.mailchimp.com/article/where-can-i-find-my-api-key/ ]
2. Install WordChimp like any other plugin
3. Setup WordChimp by going to Settings > WordChimp
4. Add your MailChimp API key to the settings and change any other preferences you wish. Hit 'Save'.
5. Click on the WordChimp tab on the navigation menu and follow on-screen instructions.

== Screenshots ==
For screen shots, check out http://hudsoncs.com/projects/wordchimp/

== Changelog ==
= 2.0 =
* Completely new templating system. Uses your own custom MailChimp templates or you can choose from the MailChimp gallery. NOTE: Not all MailChimp gallery templates are 100% compatible. Repeatable sections are currently not supported.
* Added ability to change order of posts.
* Updated style to match (most) WordPress best practices.

= 1.7 =
* Fixed expanding of shortcodes in text content. (Thanks again @CPilko)
* Changed statistics 'opens' to 'unique opens'. (Thanks again... again... @CPilko)

= 1.6 =
* Added ability to change security levels required to access WordChimp (See WordPress Capabilities for more information. Thanks Rabab).
* Added ability to use a post excerpt instead of the whole post content. (Thanks Guy Whose Name Escapes Me)
* Removed unnecessary Google Analytics Key setting (doh!)
* Set shortcodes to expand instead of being stripped right before being sent. (Nice one @CPilko)

= 1.4 =
* Added campaign statistics. Thanks @meerblickzimmer!
* Added campaign preview.
* Added 'settings' link to the Plugins page.

= 1.3 =
* Now removes WordPress post's shortcodes before adding to campaign. Thanks Peter!
* Added Default From Email and Default From Name settings.

= 1.2 =
* Fixed WordChimp version. Whoops!

= 1.1 =
* Modified MailChimp API class/function names to help eliminate class name collision for those with more than one plugin using MailChimp API. Avoided using namespaces specifically to ensure that most of us with older versions of PHP could still roll with WordChimp.
* Updated README with screenshots and what not.

= 1.0 =
* First version. Sweet!