Bolt.cm Slack inviter
=====================

This is the source code for the Slack invite sender on slack.bolt.cm. It is
probably not of much use to others, but with a small bit of effort, you can
make it work for invites for other Slack workspaces.

Installation
------------

First, clone the repository and install Composer packages

```
git@github.com:bolt/slack-invites.git
cd slack-invites
composer install
```

Then set the `SLACK_TEAM` and `SLACK_TOKEN` in `.env`, and it should work

To configure the theme / CSS, edit the files in `templates/` and `assets/css`.
After making changes to the CSS, remember to build the assets, using Symfony
Encore:

```
./node_modules/.bin/encore production
```

This code was originally based on [slackin](http://rauchg.com/slackin), and is
licensed under the MIT license.
