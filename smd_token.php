<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'smd_token';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.2.0';
$plugin['author'] = 'Stef Dawson';
$plugin['author_uri'] = 'https://stefdawson.com/';
$plugin['description'] = 'Generate cryptographically random tokens for use within Textpattern';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * smd_tokem
 *
 * A Textpattern CMS plugin for generating random cryptographic tokens
 *
 * @author Stef Dawson
 * @link   https://stefdawson.com/
 */

if (txpinterface === 'public') {
    if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
            ->register('smd_token')
            ->register('smd_if_token');
    }
}

global $smd_tokens;
$smd_tokens = array();

/**
 * Public tag: generate a random token.
 *
 * @param  array  $atts  Tag attributes
 * @param  string $thing Tag container content
 * @return string        HTML
 */
function smd_token($atts, $thing = null)
{
    global $smd_tokens;

    extract(lAtts(array(
        'display' => 1,
        'length'  => 16,
        'name'    => '',
        'prefix'  => '',
    ), $atts));

    if ($name) {
        $name = sanitizeForUrl($name);

        if (array_key_exists($name, $smd_tokens)) {
            // It exists, so fetch it.
            $token = $smd_tokens[$name];
        } else {
            // Not yet defined, so store it.
            $token = smd_generate_token($length);
            $smd_tokens[$name] = $token;
        }
    } else {
        // Name not given so it's a one-shot token.
        $token = smd_generate_token($length);
    }

    if ($display) {
        return $prefix.$token;
    } else {
        return;
    }
}

/**
 * Tests if the given token exists.
 *
 * @param  array  $atts  Tag attributes
 * @param  string $thing Contained content to execute on true/false
 * @return string|bool   Parsed content or true/false
 */
function smd_if_token($atts, $thing = '')
{
    global $smd_tokens;

    extract(lAtts(array(
        'name' => '',
    ), $atts));

    $result = false;

    if ($name) {
        $name = sanitizeForUrl($name);
        $result = array_key_exists($name, $smd_tokens);
    }

    return !empty($thing) ? parse($thing, $result) : $result;
}

/**
 * Generate a unique cryptographic token of the given length.
 *
 * @param  int $length Number of characters to make the token
 * @return string      The token
 */
function smd_generate_token($length) {
    return Txp::get('\Textpattern\Password\Random')->generate($length);
}

# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. smd_token

Public tag to return a cryptographically secure sequence of characters. Very handy for using inside plugins or CSP headers.

h2. Installation / Uninstallation

"Download this plugin":https://github.com/Bloke/smd_token/releases/latest and then paste the code from the .txt file into the Textpattern _Admin->Plugins_ panel, install and enable the plugin. For bug reports, please "raise an issue":https://github.com/Bloke/smd_token/issues.

To uninstall, delete the plugin from the _Admin->Plugins_ panel.

h2. Usage examples

16-character tokens are default, so to immediately return a 16-character randomly-generated token:

bc. <txp:smd_token />

Display a 24-character randomly-generated token:

bc. <txp:smd_token length="24" />

Display a 12-character randomly-generated token with a prefix:

bc. <txp:smd_token length="12" prefix="nonce-" />

The above example outputs something like @nonce-ab1b7f98175e@.

bc. <txp:smd_token name="my_csp" length="12" prefix="nonce-" />

Outputs a 12-character token with prefix, and stores the token value as 'my_csp' for later use in the same page request. If you don't wish to display the token at this time (e.g. if you're generating it in advance) specify @display="0"@.

To retrieve the previously stored token and display its value:

bc. <txp:smd_token name="my_csp" />

The @length@ is ignored if you retrieve a previously saved token. But the @prefix@ may be altered. Using @display="0"@ in this case is pointless, because you would always need to display a token you've retrieved, but the ability to silence the output is honoured anyway.

To check if a named token has been generated already, use the conditional tag:

bc. <txp:smd_if_token name="my_csp">
    <txp:smd_token name="my_csp" prefix="nonce-" />
<txp:else />
    <txp:smd_token name="my_csp" prefix="nonce-" length="12" />
</txp:smd_if_token>

The @name@ attribute is mandatory. Without it, the tag will always return false.

If used as a single tag, the result will be 1 if the token exists, or empty otherwise.

h2. Author / credits

Written by "Stef Dawson":https://stefdawson.com/contact.

# --- END PLUGIN HELP ---
-->
<?php
}
?>