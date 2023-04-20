<?php $is_useful = false; ?>

<link rel="stylesheet" href="/assets/admin_page.css" />

<div class="card-container">

<?php if ($TEMPLATE['access_security']): ?>
<?php $is_useful = true; ?>

<a class="card" href="/site/admin/users">
<h2> Users </h2>
<p>
Create user accounts on the site and edit authentication configuration.
</p>
</a>

<a class="card" href="/site/admin/groups">
<h2> Groups </h2>
<p>
Create and edit groups to assign membership to users and grant security capabilities.
</p>
</a>

<a class="card" href="/site/admin/auth_config">
<h2> Authentication </h2>
<p>
Edit configuration for different ways to authenticate on this site.
</p>
</a>
<?php endif; ?>

<?php if ($TEMPLATE['access_themes']): ?>
<?php $is_useful = true; ?>

<a class="card" href="/site/admin/theme">
<h2> Theme </h2>
<p>
Map colors from a color palette to application-defined colors to style your site.
</p>
</a>

<a class="card" href="/site/admin/color_palette">
<h2> Color Palette </h2>
<p>
	Create and edit color palettes which are sets of colors that constrain
which colors a theme can use. 
</p>
</a>
<?php endif; ?>

</div>

<?php if (!$is_useful): ?>
Sorry, you don't have access to any admin tools.
<?php endif; ?>
