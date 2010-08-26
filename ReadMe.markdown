# Entries Number plugin for ExpressionEngine 2.x

Entries Number allows you to lookup the number of entries in a particular channel based on certain criteria (category, author, etc.) and then either output the number or use it in EE conditionals.

## Installation

If you download the source from GitHub, make sure to rename the root folder to `entries_number`.  Your final folder should look like this:

    entries_number/
       config.php
       pi.entries_number.php
       ReadMe.markdown

You can then upload the entries\_number folder to your `system/expressionengine/third_party` folder.

Alternatively, you can clone the repository directly in place:

    cd /PATH/TO/SITE/system/expressionengine/third_party
    git clone git://github.com/onecrayon/entries-number.ee2_addon.git entries_number

## Example Usage

    {exp:entries_number category="6" channel="not channel1|channel4" site="1"}
        {if entries_number == 0 OR entries_number > 1}
            <p>There are {entries_number} items</p>
        {if:else}
            <p>There is {entries_number} item.</p>
        {/if}
    {/exp:entries_number}

## Parameters

**`category` (optional)**  
Allows you to specify category id number 
(the id number of each category is displayed in the Control Panel).
You can stack categories using pipe character to get entries 
with any of those categories, e.g. `category="3|6|8"`. Or use "not" 
(with a space after it) to exclude categories, e.g. `category="not 4|5|7"`.
Also you can use "&" symbol to get entries each of which was posted into all 
specified categories, e.g. `category="3&6&8"`.

**`channel` (optional)**  
Allows you to specify channel name.
You can use the pipe character to get entries from any of those 
channels, e.g. `channel="channel1|channel2|channel3"`.
Or you can add the word "not" (with a space after it) to exclude channels,
e.g. `channel="not channel1|channel2|channel3"`.

**`author_id` (optional)**  
Allows you to specify author id number.
You can use the pipe character to get entries posted by any of those 
authors, e.g. `author_id="5|11|18"`.
Or you can add the word "not" (with a space after it) to exclude authors,
e.g. `author_id="not 1|9"`.

**`site` (optional)**  
Allows you to specify site id number.
You can stack site id numbers using pipe character to get entries 
from any of those sites, e.g. `site="1|3"`. Or use "not" 
(with a space after it) to exclude sites, e.g. `site="not 1|2"`.

**`status` (optional)**  
Allows you to specify status of entries.
You can stack statuses using pipe character to get entries 
having any of those statuses, e.g. `status="open|draft"`. Or use "not" 
(with a space after it) to exclude statuses, 
e.g. `status="not submitted|processing|closed"`.

**`url_title` (optional)**  
Allows you to specify url\_title of an entry.

**`entry_id` (optional)**  
Allows you to specify entry id number of an entry.

**`show_expired` (optional)**  
Allows you to specify if you wish expired entries
to be counted. If the value is "yes", expired entries will be counted; if the
value is "no", expired entries will not be counted. Default value is "yes".

**`invalid_input` (optional)**  
Accepts two values: "alert" and "silence".
Default value is "silence". If the value is "alert", then in cases when some
parameter's value is invalid plugin exits and PHP alert is being shown;
if the value is "silence", then in cases when some parameter's value
is invalid plugin finishes its work without any alert being shown. 
Set this parameter to "alert" for development, and to "silence" for deployment.

**`required_field` (optional)**  
Allows you to specify which custom field should not be
empty. Pipe character is supported; "not" operator is not supported. E.g. if we have 
`required_field="custom_field1|custom_field2"`, then only those entries will be counted
which have at least one of these fields not empty.
