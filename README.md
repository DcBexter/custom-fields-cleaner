# CF Cleaner

![WordPress - CF Cleaner](cf-cleaner-logotype.png "WordPress - CF Cleaner")

Custom Fields Cleaner / remove empty and orphaned CF entries from your WordPress database. Either created by plugins or by yourself.

----------

**[Advanced Custom Fields](http://advancedcustomfields.com/)** is a WordPress plug-in that helps to create advanced, custom fields for better content parsing through custom PHP / SQL queries.

*It works great, but has one disadvantage; it really can mesh up your database after a while / during heavy development.*

**Imagine this;**

You have 10 custom fields for SEO practices (description, OG:share, metadata, etc...). And those SEO fields are binded to all your (10) webpages.

But your client ( *lazy as they are* :p ) doesn't enter SEO information for every page.

In this case, CF saves 10 empty records for every page, resulting in 100 useless database entries. And every time your client updates a page (let's say 5 times, during entering the right content), CF creates 50 empty "revision" entries... per page; so 500 records in total.

Guess what? The site is multi-langual... 5 languages, resulting in 500 x 5 empty records; 2.500 entries without any information... and you can multiply that, because CF creates 2 records, for every single entry (*5.000 useless records after some editing in your back-end...*).

Another thing is, that when you delete an CF field, it related content is not deleted (this can be fine, but in most cases I want both the **field** and the (orphaned / depricated) **content** gone).

And when renaming the fields #ID (which can happen, in early stages of development) the "original" content is saved and cloned to another record... which duplicates **all** entries, created before.

...so after developing and managing your content, you can end up with thousands of useless CF records / entries in your database.

----------

**This is where CF Cleaner comes in;**

It scans for both empty and orphaned records and removes them with one click.

### How to use?

1. Be aware that all your CF fieldname #ID's (the #ID's, not the labels) must have a consistent prefix, like `CF__`  (case insensitive), which will end up in something like `CF__myname` or `CF__4421jaQ` or `CF_title_1_page_3`.
2. Install the plug-in at `wp-uploads/plugins` and activate it, like all plug-ins (see ZIP-remark below).
3. Navigate to `tools/cf cleaner` and see all your empty / orphaned records (when prefixed by `CF__`).
4. When records are found, you can **(re-) check** the database or **clean** it; simply enter `clean` in the inputfield (to prevent accidentally hitting the "CF clean" button) and submit the form.
5. When any records are encountered the plug-in will clean them and show you the results of the SQL cleaning actions.
6. More info - check the forum @ https://support.advancedcustomfields.com/forums/topic/plug-in-acf-cleaner-free/

### ZIP and UNZIP structure

When you download the master-package from Git(Hub), a .zip-file is created on the fly.

Extract the files in this .zip-file and upload them to your sites plugins-structure, like this;

```
<site_root>
    | wp-content
        | plugins
            | cf-cleaner
              - all files (mainly .php)
```

----------

![WordPress - CF Cleaner](https://raw.githubusercontent.com/1n3JgKl9pQ6cUMrW/acf-cleaner/04d796be6194654d382c59419805c93a746c5c65/acf-cleaner-screenshot.png "WordPress - CF Cleaner")

**Remember; do back-up your database before cleaning, since I can not take any responsibility for the final result.**
