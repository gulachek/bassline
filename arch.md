# Architecture

The setup of this system is that a **site** is built of several
**applications**. These applications are plugins depending on
the **engine**.

The site administrator is responsible for creating a
configuration php file that interfaces with the engine via the
`Config` class. The config file should return an instance of the
configuration object so that the engine can `require()` it and
use the object for serving content.

The path to this configuration file should be set up as a
$\_SERVER php variable on the web server (via something like
nginx or apache configuration).  The web server (like nginx)
config should point to the `vendor/bin/serve.php` script as the
entry point for serving content for the site, assuming that the
engine was installed by the site via composer's default
`vendor/bin` mechanism.

Given that the **site** interfaces with the **engine** via the
`Config` class, it needs to reference the **engine** API. It
will also tell the **engine** which **applications** are served
in the **site**.  Since these **applications** are developed
against the `App` class, they, too, each reference the
**engine** API.  Hence, there are 3 independent references to
the **engine** API in this example.  However, clearly only one
version of the **engine** can drive requests. As such, the `App`
class requires that each **application** define an
`engineVersion` that the **application** is compatible with.
This way, the engine that the configuration specifies can run
and validate against each **application** if it is able to
support the **application** and report errors to the admin if
not.
