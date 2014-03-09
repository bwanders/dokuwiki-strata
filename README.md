Strata
======

Strata is a semi-structured data plugin for [DokuWiki][dw]. Strata allows you to add data to your wiki pages, and later on query that data to create automatic indices or other overviews.

#### Installation
1. Use the DokuWiki plugin manager to install Strata.
2. Optionally, copy the contents of the ``manual.txt`` file into a wiki page (``wiki:strata`` would be a good location)

#### Quick-start
Below is a very simple example of how to use Strata. You can find more information in the ``manual.txt`` (which you can copy-paste into a wiki page to have the manual available for all users).

Add data to a page with:

    <data person>
    Full Name: John Doe
    Age: 24
    Contact [link]: john.doe@example.org
    </data>

Later on, you can make a list (to get a table, use ``<table>`` instead) of people with:

    <list ?person ?contact>
    ?person is a: person
    ?person Contact [link]: ?contact
    </list>


#### More Information

See the [plugin page][pp] for more information on usage and configuration.

[dw]: https://www.dokuwiki.org
[pp]: https://www.dokuwiki.org/plugin:strata
