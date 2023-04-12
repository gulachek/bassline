# System Colors

Importable cross-app components need to be able to assign
themselves reasonable default colors that respect site theming
by default. This makes everyone's life easier.

Previously, "theme colors" existed only as user-defined (at
runtime) named colors that could then be mapped to in
app-defined semantic colors by the user.  Every theme color
had a background and foreground color following the general
paradigm of [css system
colors](https://www.w3.org/TR/css-color-4/#css-system-colors).

This had two problems.

1. The only colors that could be referenced from code were the
   app-defined semantic colors in a page relevant to that app.
If, for example, a basic text field were designed to work with
theming by default, every single one would somehow need to be
told what a corresponding app-defined semantic color was.
This is inconvenient.
2. Color relationships that are more complex than a simple
   foreground/background scheme had awkward implications on,
for example, defining a custom border color for a text entry
field.  Is the border color a foreground or background? If
one, what does the other one mean?

To solve this, a new feature more closely aligned with CSS
system colors will be employed.  First, a set of colors
defined by the bassline system, intended to closely match the
standard CSS system colors, will exist on every page as CSS
variable colors that can be referenced from code.  This
directly addresses problem 1.

To solve problem 2, the system will not have any relationships
between the theme colors anymore.  There will be no concept of
foreground/background, making the underlying system/structure
simpler and also more natural for cases that need more
complexity like specifying a foreground/background/border
relationship.  Additional colors such as 'error, warning, and
success' will be defined since those are pretty ubiquitous.

It's better to define these as theme colors instead of another
special case of theme mappings to semantic colors because then
semantic colors can be constrained to these theme colors and
change with the system color assignments as they change.

There was previously a nice feature in the
foreground/background relationship to display the contrast
ratio in the editor.  Now, the contrast ratio can be shown for
every color in relationship with the currently selected color,
which might even be better.

It might also be more tedious now to define application colors
and also cause more ambiguity in when an app should define a
new color vs just referencing a system color in CSS.  Naively,
it seems like best practice to evaluate the likelihood that a
user would actually want the color to diverge from the system
color at the site level.  If so, then the app can define a
color that can simply default to the most sensible system
color and if the user chooses to diverge, that's a
possibility.  If the app is too liberal, however, with every
colored item, that would become overwhelming for a theme
designer.

