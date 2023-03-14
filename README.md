# bassline
php web framework for individual developers running multi-app
websites

## Purpose

TL;DR "wordpress for developers"

Ideally, a single website would have a single purpose. A blog
goes on a blog website, a wiki goes on a wiki website, and so
on.  In practice, as a hobby developer who cannot justify
purchasing several domain names or web servers for low-traffic
web applications, it's an attractive option to put the apps
under the same domain on the same server to save time and
money invested in the hobby projects. 

Following the wiki/blog example, it would be fairly easy to
set up an nginx server to route to either a mediawiki app or a
wordpress app for each of those purposes.  However, these are
both full scale solutions that are targeted at casual computer
(non-developer) users to be able to add content to their
sites, and are not primarly targeted at developers making
custom apps for their sites.  Enter bassline.

Bassline is intended to solve some basic problems like
authentication and theming that are common across web
applications and provide a system targeted at developers to
create custom applications that don't need to worry about
solving these common problems.

## Name

The author was originally describing bassline as a **"web
engine"** to the wide audience of himself.  You can hold your
political opinions on the matter, but people often use
**"web"** and "inter**net**" interchangably.  A **"net
engine"** evokes imagery of a fishing boat cruising with an
engine on it.  That boat would probably have a **wake** behind
it.  People **wake** up in the **morning**.  The traditional
Orthodox Christian **morning** prayers have a phrase "Teach me
to walk **uprightly**, in the way of Christ's commandments".
People also play the **upright bass**.  Bass players lay down
sick **basslines**.  Since the bassline is the foundation of a
song and this project is the foundation of a simple website,
the name was obvious and fitting.  
