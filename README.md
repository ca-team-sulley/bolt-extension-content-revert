Content Revert Bolt Extension
=============================

[![Build Status](https://img.shields.io/travis/ca-team-sulley/bolt-extension-content-revert/master.svg?style=flat-square)](https://travis-ci.org/ca-team-sulley/bolt-extension-content-revert)

The purpose of this extension is to allow users to revert content to a previous version.

Configuring
===========

Enabling tracking
-----------------

Version tracking should be turned on in the config.yml, to do that:

 1. Open `app/config/config.yml`
 2. Find the `changelog` section
 3. Set the `enabled` property from `false` to `true`
 4. Save the file and exit

Content changes will now be tracked by bolt, and for as long as you don't clear the log, you
will be able to revert to any prior state which has been tracked.

Skipping hidden fields
----------------------

In some content types, you may have some hidden fields that contain information that should not
be reverted.  Through the extension configuration file, you may enable skipping these fields, so
as not to cause any issues.  The reversion service will check if the field is either of a type
`hidden`, or contains a `hidden` class to support older Bolt versions, and not make any changes
for these fields.

To use this feature, you may do the following:

 1. Open `app/config/extensions/content-revert.cainc.yml`
 2. Set the `skip_hidden_fields` property from `false` to `true`
 3. Save the file and exit

Permissions
===========

Only users in a role with the `changelogrecordsingle` permission may revert from a tracked change.
