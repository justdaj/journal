# Journal
A super simple, self-hosted Journal application that's written in PHP.

*Journal* was created by [Kev Quirk](https://kevquirk.com) and uses the following packages:

* [Simple.css](https://simplecss.org) - to make it look pretty
* [Parsedown](https://parsedown.org) - to add Markdown support

I created this little application for my own use, but figured it might be useful for others. Originally it created a plaintext file and rendered them, but I've since improved the functionality by moving from plaintext files, to an SQLite database.

Here's the features that are currently supported in *Journal*:

* Markdown within journal entries.
* Tagging support for entries.
* Ability to delete entries.
* Entry pagination.
* An *On This Day* page that allows you to look back at previous entries from a week, month, and previous years.

## Hosting requirement

The hosting requirements are *extremely* simple. All that is needed is PHP support and SQLite, both of which are available with most hosting providers.

## Getting started

1. Fork or download the project
2. Upload the project to your hosting provider
3. Enjoy!

Even though *Journal* requires SQLite, no configuration is needed. If no existing database is detected, then a new one will be automatically created when you publish your first entry.

## Note on security

There is currently no security in place to protect your journal from public eyes. So you will need to either put it behind some access control rules, so only certain IP addresses can access it, or put the entire site within a password protected directory.

Most hosting providers offer password protected directories, so it should be straightforward to setup.

Alternatively, the most secure method would be to not expose your Journal to the internet, and just run it locally.

## Roadmap

*Journal* is pretty much feature complete at this point, as it does everything I need it to. I *might* add an option to search entries in the future, but I'm not sure how valuable that would be for my use case.

## Note on the code

This was never designed to be a public project. There's a lot of spaghetti code here, but it works, so it's good enough for me. I'll probably look at cleaning it up at some point, but for now, I'm happy with it.
