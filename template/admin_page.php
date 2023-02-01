<link rel="stylesheet" href="/static/admin_page.css" />

<?php if ($TEMPLATE['access_users']): ?>
<a class="card" href="/site/admin/users">
<h2> Users </h2>
<p>
Create user accounts on the site and edit authentication configuration.
</p>
</a>
<?php endif; ?>

<?php if ($TEMPLATE['access_groups']): ?>
<a class="card" href="/site/admin/groups">
<h2> Groups </h2>
<p>
Create and edit groups to assign membership to users and grand security capabilities.
</p>
</a>
<?php endif; ?>

<a class="card" href="/shell/theme">
<h2> Theme </h2>
<p>
Map colors from a color palette to application-defined colors to style your site.
</p>
</a>

<a class="card" href="/shell/color_palette">
<h2> Color Palette </h2>
<p>
	Create and edit color palettes which are sets of colors that constrain
which colors a theme can use. 
</p>
</a>
